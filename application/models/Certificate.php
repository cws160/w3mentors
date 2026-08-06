<?php

/**
 * This class is used to handle certificates
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class Certificate extends MyAppModel
{
    const CERTIFICATE_NO_PREFIX = '';

    const TYPE_QUIZ_EVALUATION = 1;
    const TYPE_COURSE_COMPLETION = 2;
    const TYPE_COURSE_EVALUATION = 3;

    private $id;
    private $code;
    private $userId;
    private $langId;

    /**
     * Initialize certificate
     *
     * @param int    $id
     * @param string $code
     * @param int    $userId
     * @param int    $langId
     */
    public function __construct(int $id, string $code, int $userId = 0, int $langId = 0)
    {
        $this->id = $id;
        $this->code = $code;
        $this->userId = $userId;
        $this->langId = $langId;
    }

    /**
     * Get Types
     *
     * @param int $key
     * @param bool $active
     * @return string|array
     */
    public static function getTypes(int $key = null, bool $active = true)
    {
        $arr = [];
        $srch = CertificateTemplate::getSearchObject(MyUtility::getSiteLangId());
        if ($active == true) {
            $srch->addCondition('certpl_status', '=', AppConstant::ACTIVE);
        }
        $templates = FatApp::getDb()->fetchAll($srch->getResultSet());
        if ($templates) {
            foreach ($templates as $template) {
                switch ($template['certpl_code']) {
                    case 'course_completion_certificate':
                        $arr[static::TYPE_COURSE_COMPLETION] = Label::getLabel('LBL_COURSE_COMPLETION');
                        break;
                    case 'evaluation_certificate':
                        $arr[static::TYPE_QUIZ_EVALUATION] = Label::getLabel('LBL_QUIZ_EVALUATION');
                        break;
                    case 'course_evaluation_certificate':
                        $arr[static::TYPE_COURSE_EVALUATION] = Label::getLabel('LBL_COURSE_EVALUATION');
                        break;
                }
            }
        }
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Generate Certificate
     *
     * @param string $content
     * @return bool
     */
    public function generate(string $content)
    {
        if (!$content = $this->setupTemplate($content)) {
            return false;
        }
        
        if (!$data = $this->getData()) {
            return false;
        }
        $data['certificate_number'] = Certificate::CERTIFICATE_NO_PREFIX . uniqid();
        if (!$content = $this->formatContent($content, $data)) {
            return false;
        }
        if (!$this->create($content)) {
            return false;
        }
        if (!$this->setupId($data['certificate_number'])) {
            return false;
        }
        if (!$this->setupMetaTags($data)) {
            return false;
        }
        return true;
    }

    /**
     * Generate Certificate & Preview
     *
     * @param string $content
     * @return bool
     */
    public function generatePreview(string $content)
    {
        if (!$content = $this->setupTemplate($content)) {
            return false;
        }
        if (!$data = $this->getPreviewData()) {
            return false;
        }
        if (!$content = $this->formatContent($content, $data)) {
            return false;
        }
        if (!$this->create($content, true)) {
            return false;
        }
        return true;
    }

    /**
     * Generate & save certificate id
     *
     * @return bool
     */
    public function setupId($certificateNumber)
    {
        if ($this->code == 'evaluation_certificate' || $this->code == 'course_evaluation_certificate') {
            $quiz = new QuizAttempt($this->id);
            $quiz->setFldValue('quizat_certificate_number', $certificateNumber);
            if (!$quiz->save()) {
                $this->error = Label::getLabel('LBL_AN_ERROR_HAS_OCCURRED_WHILE_GENERATING_CERTIFICATE!');
                return false;
            }
        } elseif ($this->code == 'course_completion_certificate') {
            $course = new OrderCourse($this->id);
            $course->setFldValue('ordcrs_certificate_number', $certificateNumber);
            if (!$course->save()) {
                $this->error = Label::getLabel('LBL_AN_ERROR_HAS_OCCURRED_WHILE_GENERATING_CERTIFICATE!');
                return false;
            }
        } else {
            $this->error = Label::getLabel('LBL_INVALID_CERTIFICATE_TYPE');
            return false;
        }
        return true;
    }

    /**
     * Get & setup certificate template
     *
     * @param string $content
     * @return string|bool
     */
    private function setupTemplate(string $content)
    {
        $srch = CertificateTemplate::getSearchObject($this->langId);
        $srch->addCondition('certpl_code', '=', $this->code);
        if (!$template = FatApp::getDb()->fetch($srch->getResultSet())) {
            $this->error = Label::getLabel('LBL_CERTIFICATE_TEMPLATE_NOT_FOUND');
            return false;
        }
        $template = json_decode($template['certpl_body'], true);
        $content = str_replace(
            ['{heading}', '{content-1}', '{learner}', '{content-2}', '{trainer}', '{certificate-number}'],
            [
                $template['heading'],
                $template['content_part_1'],
                $template['learner'],
                $template['content_part_2'],
                $template['trainer'],
                $template['certificate_number'],
            ],
            $content
        );
        return $content;
    }

    /**
     * Generate Certificate Image & PDF
     *
     * @param string $token
     * @return bool
     */
    public function create($content, $preview = false)
    {
        ini_set('memory_limit', '-1');
        $filename = 'certificate' . $this->id . '.pdf';
        $mpdf = new \Mpdf\Mpdf([
            'orientation' => 'L',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'mirrorMargins' => 0,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'tempDir' => CONF_UPLOADS_PATH
        ]);
        $mpdf->SetDirectionality(Language::getAttributesById($this->langId, 'language_direction'));
        $mpdf->WriteHTML($content);
        $path = $this->getFilePath() . $filename;
        if ($preview == false) {
            $mpdf->Output(CONF_UPLOADS_PATH . $path, \Mpdf\Output\Destination::FILE);
            $fileType = ($this->code == 'course_completion_certificate') ? Afile::TYPE_COURSE_CERTIFICATE_PDF : Afile::TYPE_QUIZ_CERTIFICATE_PDF;
            if (!$this->saveFile($fileType, $filename, $path)) {
                $this->error = Label::getLabel('LBL_AN_ERROR_HAS_OCCURRED_WHILE_GENERATING_CERTIFICATE!');
                return false;
            }
        } else {
            $mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
        }
        return true;
    }

    /**
     * Get Course & User details for certificate
     *
     * @param int     $ordcrsId
     * @return array
     */
    public function getDataForCertificate(int $ordcrsId)
    {
        $srch = new OrderCourseSearch($this->langId, $this->userId, 0);
        $srch->joinTable(CourseLanguage::DB_TBL, 'INNER JOIN', 'clang.clang_id = course.course_clang_id', 'clang');
        $srch->joinTable(CourseLanguage::DB_TBL_LANG, 'LEFT JOIN', 'clang.clang_id = clanglang.clanglang_clang_id AND clanglang.clanglang_lang_id = ' . $this->langId, 'clanglang');
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addMultipleFields([
            'crspro_completed',
            'IFNULL(clanglang.clang_name, clang.clang_identifier) AS course_clang_name',
            'learner.user_lang_id',
            'ordcrs_certificate_number',
            'course_duration'
        ]);
        $srch->addCondition('ordcrs_id', '=', $ordcrsId);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get formatted certificate content
     *
     * @param array $data
     * @return array
     */
    public function formatContent(string $content, array $data)
    {
        $title = ($this->code == 'evaluation_certificate') ? $data['quilin_title'] : $data['course_title'];
        $title = htmlentities(stripslashes(mb_convert_encoding($title, 'UTF-8')), ENT_QUOTES);
        $content = str_replace(
            [
                '{learner_name}',
                '{teacher_name}',
                '{course_name}',
                '{course_language}',
                '{course_completed_date}',
                '{certificate_number}',
                '{course_duration}',
                '{quiz_name}',
                '{quiz_completed_date}',
                '{quiz_duration}',
                '{quiz_score}',
                '{course_score}',
            ],
            [
                ucwords($data['learner_first_name'] . ' ' . $data['learner_last_name']),
                ucwords($data['teacher_first_name'] . ' ' . $data['teacher_last_name']),
                '<span class=\"courseNameJs\">' . $title . '</span>',
                isset($data['course_clang_name']) ? $data['course_clang_name'] : '',
                isset($data['completed_date']) ? MyDate::showDate($data['completed_date']) : '',
                $data['certificate_number'],
                isset($data['course_duration']) ? CommonHelper::convertDuration($data['course_duration']) : '',
                '<span class=\"courseNameJs\">' . $title . '</span>',
                isset($data['completed_date']) ? MyDate::showDate($data['completed_date']) : '',
                isset($data['quiz_duration']) ? CommonHelper::convertDuration($data['quiz_duration'], true, true) : '',
                isset($data['quizat_scored']) ? MyUtility::formatPercent($data['quizat_scored']) : '',
                isset($data['quizat_scored']) ? MyUtility::formatPercent($data['quizat_scored']) : '',
            ],
            $content
        );
        return $content;
    }

    /**
     * Save created files data
     *
     * @param int $type
     * @param string $filename
     * @param string $path
     * @return bool
     */
    public function saveFile(int $type, string $filename, string $path)
    {
        $record = new TableRecord(Afile::DB_TBL);
        $record->assignValues([
            'file_type' => $type,
            'file_record_id' => $this->id,
            'file_name' => $filename,
            'file_path' => $path,
            'file_order' => 0,
            'file_added' => date('Y-m-d H:i:s')
        ]);
        if (!$record->addNew()) {
            $this->error = $record->getError();
            return false;
        }
        /* delete old file */
        $fileId = $record->getId();
        $stmt = [
            'vals' => [$type, 0, $this->id, $fileId],
            'smt' => 'file_type = ? AND file_lang_id = ? AND file_record_id = ? AND file_id != ?'
        ];
        FatApp::getDb()->deleteRecords(Afile::DB_TBL, $stmt);
        return true;
    }

    /**
     * Get certificate replacers data
     *
     * @return array
     */
    private function getData()
    {
        switch ($this->code) {
            case 'course_completion_certificate':
                $srch = new OrderCourseSearch($this->langId, $this->userId, 0);
                $srch->joinTable(
                    CourseLanguage::DB_TBL, 'INNER JOIN', 'clang.clang_id = course.course_clang_id', 'clang'
                );
                $srch->joinTable(
                    CourseLanguage::DB_TBL_LANG,
                    'LEFT JOIN',
                    'clang.clang_id = clanglang.clanglang_clang_id AND clanglang.clanglang_lang_id = ' . $this->langId,
                    'clanglang'
                );
                $srch->applyPrimaryConditions();
                $srch->addSearchListingFields();
                $srch->addMultipleFields([
                    'crspro_completed as completed_date', 'IFNULL(clanglang.clang_name, clang.clang_identifier) AS course_clang_name',
                    'learner.user_lang_id', 'ordcrs_certificate_number AS cert_number', 'course_duration',
                    'ordcrs_course_id', 'order_user_id'
                ]);
                $srch->addCondition('ordcrs_id', '=', $this->id);
                $data = FatApp::getDb()->fetch($srch->getResultSet());
                break;
            case 'course_evaluation_certificate':
                $srch = new SearchBase(QuizAttempt::DB_TBL);
                $srch->joinTable(QuizLinked::DB_TBL, 'INNER JOIN', 'quizat_quilin_id = quilin_id');
                $srch->joinTable(Course::DB_TBL, 'INNER JOIN', 'quilin_record_id = crs.course_id', 'crs');
                $srch->joinTable(Course::DB_TBL_LANG, 'INNER JOIN', 'crsdetail.course_id = crs.course_id', 'crsdetail');
                $srch->joinTable(CourseLanguage::DB_TBL, 'INNER JOIN', 'clang_id = crs.course_clang_id', 'clang');
                $srch->joinTable(
                    CourseLanguage::DB_TBL_LANG,
                    'LEFT JOIN',
                    'clang.clang_id = clanglang.clanglang_clang_id AND clanglang.clanglang_lang_id = ' . $this->langId,
                    'clanglang'
                );
                $srch->addMultipleFields([
                    'crsdetail.course_title', 'quizat_updated as completed_date', 'quizat_scored',
                    'quizat_certificate_number', 'quizat_user_id', 'quilin_user_id', 'quizat_id',
                    'IFNULL(clanglang.clang_name, clang.clang_identifier) AS course_clang_name'
                ]);
                $srch->addCondition('quizat_id', '=', $this->id);
                $data = FatApp::getDb()->fetch($srch->getResultSet());
                $learner = User::getAttributesById($data['quizat_user_id'], [
                    'user_first_name as learner_first_name', 'user_last_name as learner_last_name'
                ]);
                $teacher = User::getAttributesById($data['quilin_user_id'], [
                    'user_first_name as teacher_first_name', 'user_last_name as teacher_last_name'
                ]);
                $data = $data + $learner + $teacher;
                break;
            case 'evaluation_certificate':
                $data = QuizAttempt::getById($this->id);
                $data['completed_date'] = $data['quizat_updated'];
                $learner = User::getAttributesById($data['quizat_user_id'], [
                    'user_first_name as learner_first_name', 'user_last_name as learner_last_name'
                ]);
                $teacher = User::getAttributesById($data['quilin_user_id'], [
                    'user_first_name as teacher_first_name', 'user_last_name as teacher_last_name'
                ]);
                $data['quiz_duration'] = strtotime($data['quizat_updated']) - strtotime($data['quizat_started']);
                $data = $data + $learner + $teacher;
                break;
        }

        return $data;
    }

    /**
     * Get preview dummy data
     *
     * @return array
     */
    private function getPreviewData()
    {
        return [
            'learner_first_name' => 'Martha',
            'learner_last_name' => 'Christopher',
            'teacher_first_name' => 'John',
            'teacher_last_name' => 'Doe',
            'quilin_title' => 'English Language Learning - Beginners',
            'course_title' => 'English Language Learning - Beginners',
            'course_clang_name' => 'English',
            'cert_number' => static::CERTIFICATE_NO_PREFIX . 'h34uwh9e72w',
            'certificate_number' => static::CERTIFICATE_NO_PREFIX . 'h34uwh9e72w',
            'completed_date' => date('Y-m-d'),
            'quiz_duration' => 900,
            'course_duration' => 900,
            'quizat_scored' => 85,
        ];
    }

    /**
     * function to get path for file uploading
     *
     * @return string
     */
    private function getFilePath()
    {
        $uploadPath = CONF_UPLOADS_PATH;
        $filePath = date('Y') . '/' . date('m') . '/';
        if (!file_exists($uploadPath . $filePath)) {
            mkdir($uploadPath . $filePath, 0777, true);
        }
        return $filePath;
    }

    /**
     * Setup meta tags for generated certificate
     *
     * @param array $data
     * @return bool
     */
    private function setupMetaTags(array $data)
    {
        switch ($this->code) {
            case 'evaluation_certificate':
                /* get user name */
                $username = User::getAttributesById($data['quizat_user_id'], "CONCAT(user_first_name, ' ', user_last_name)");
                $content = $data['quilin_title'] . ' | ' . ucwords($username) . ' | ' . FatApp::getConfig('CONF_WEBSITE_NAME_' . $this->langId);
                $action = 'evaluation';
                $recordId = $data['quizat_id'];
                $type = MetaTag::META_GROUP_QUIZ_CERTIFICATE;
                break;
            case 'course_evaluation_certificate':
                /* get user name */
                $username = User::getAttributesById($data['quizat_user_id'], "CONCAT(user_first_name, ' ', user_last_name)");
                $content = $data['course_title'] . ' | ' . ucwords($username) . ' | ' . FatApp::getConfig('CONF_WEBSITE_NAME_' . $this->langId);
                $action = 'evaluation';
                $recordId = $data['quizat_id'];
                $type = MetaTag::META_GROUP_COURSE_EVALUATION_CERTIFICATE;
                break;
            case 'course_completion_certificate':
                /* get user name */
                $username = User::getAttributesById($data['order_user_id'], "CONCAT(user_first_name, ' ', user_last_name)");
                $content = $data['course_title'] . ' | ' . ucwords($username) . ' | ' . FatApp::getConfig('CONF_WEBSITE_NAME_' . $this->langId);
                $action = 'view';
                $recordId = $data['ordcrs_id'];
                $type = MetaTag::META_GROUP_COURSE_CERTIFICATE;
                break;
            default:
                $this->error = Label::getLabel('LBL_INVALID_TYPE');
                return false;
        }

        $meta = new MetaTag();
        $meta->assignValues([
            'meta_controller' => 'Certificates',
            'meta_action' => $action,
            'meta_type' => $type,
            'meta_record_id' => $recordId,
            'meta_identifier' => $content . ' ' . $this->id
        ]);
        if (!$meta->save()) {
            $this->error = $meta->getError();
            return false;
        }
        $url = MyUtility::makeFullUrl('Certificates', $action, [$recordId], CONF_WEBROOT_FRONTEND);
        if (
            !FatApp::getDb()->insertFromArray(
                MetaTag::DB_LANG_TBL,
                [
                    'metalang_meta_id' => $meta->getMainTableRecordId(),
                    'metalang_lang_id' => $this->langId,
                    'meta_title' => $content,
                    'meta_og_url' => $url,
                    'meta_og_title' => $content
                ]
            )
        ) {
            $this->error = $meta->getError();
            return false;
        }
        return true;
    }
}
