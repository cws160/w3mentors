<?php

/**
 * This Controller is used for handling course learning process
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CertificatesController extends DashboardController
{
    /**
     * Initialize Tutorials
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Generate Certificate
     *
     * @param int $progressId
     * @return void
     */
    public function index($progressId)
    {
        if (!Course::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
        if ($progressId < 1) {
            FatUtility::exitWithErrorCode(404);
        }
        $data = CourseProgress::getAttributesById($progressId, ['crspro_completed', 'crspro_ordcrs_id']);
        /* return if course not completed */
        if (empty($data['crspro_completed'])) {
            FatUtility::exitWithErrorCode(404);
        }

        $srch = new OrderCourseSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->applyPrimaryConditions();
        $srch->addCondition('ordcrs.ordcrs_id', '=', $data['crspro_ordcrs_id']);
        $srch->addCondition('ordcrs.ordcrs_status', '!=', OrderCourse::CANCELLED);
        $srch->addMultipleFields([
            'ordcrs.ordcrs_id',
            'ordcrs.ordcrs_course_id',
            'orders.order_user_id',
            'ordcrs.ordcrs_certificate_number',
            'course_quilin_id',
            'course.course_id',
            'course_certificate',
            'course_certificate_type',
            'ordcrs_status', 'ordcrs_reviewed', 'crspro_progress',
            'crspro_completed', 'course_cate_id', 'course_subcate_id'
        ]);
        $ordcrsData = $srch->fetchAndFormat();
        $ordcrsData = current($ordcrsData);
        if (empty($ordcrsData)) {
            FatUtility::exitWithErrorCode(404);
        }

        /* return if course do not offer certificate */
        if ($ordcrsData['can_download_certificate'] === false) {
            FatUtility::exitWithErrorCode(404);
        }

        $code = 'course_completion_certificate';
        $certificateType = Certificate::TYPE_COURSE_COMPLETION;
        $id = $ordcrsData['ordcrs_id'];
        $certificateNo = $ordcrsData['ordcrs_certificate_number'];
        if ($ordcrsData['course_certificate_type'] == Certificate::TYPE_COURSE_EVALUATION) {
            $certificateNo = $ordcrsData['quizat_certificate_number'];
            $code = 'course_evaluation_certificate';
            $id = $ordcrsData['quizat_id'];
            $certificateType = Certificate::TYPE_COURSE_EVALUATION;
        }

        /* check if certificate already generated */
        if (empty($certificateNo)) {
            $db = FatApp::getDb();
            $db->startTransaction();
            /* get certificate html */
            $content = $this->getContent($certificateType);
            $cert = new Certificate($id, $code, $this->siteUserId, $this->siteLangId);
            if (!$cert->generate($content)) {
                $db->rollbackTransaction();
                FatUtility::dieWithError($cert->getError());
            }

            $db->commitTransaction();
        }

        $action = ($ordcrsData['course_certificate_type'] == Certificate::TYPE_COURSE_EVALUATION) ? 'evaluation' : 'view';
        FatApp::redirectUser(
            MyUtility::makeUrl('Certificates', $action, [$id], CONF_WEBROOT_FRONTEND)
        );
    }

    /**
     * Generate Certificate for quiz
     *
     * @param int $id
     */
    public function quiz(int $id)
    {
        if ($id < 0 || $_SESSION['certificate_type'] != Certificate::TYPE_QUIZ_EVALUATION) {
            FatUtility::exitWithErrorCode(404);
        }
        $quiz = new QuizAttempt($id, $this->siteUserId);
        if (!$quiz->validate(QuizAttempt::STATUS_COMPLETED)) {
            FatUtility::exitWithErrorCode(404);
        }
        $data = $quiz->getData();
        if ($data['quizat_active'] == AppConstant::NO) {
            FatUtility::exitWithErrorCode(404);
        }
        if (!$quiz->canDownloadCertificate()) {
            FatUtility::exitWithErrorCode(404);
        }

        /* check if certificate already generated */
        if (empty($data['quizat_certificate_number'])) {
            /* get content */
            $content = $this->getContent(Certificate::TYPE_QUIZ_EVALUATION);

            /* generate */
            $db = FatApp::getDb();
            $db->startTransaction();
            $cert = new Certificate($id, 'evaluation_certificate', $this->siteUserId, $this->siteLangId);
            if (!$cert->generate($content)) {
                $db->rollbackTransaction();
                FatUtility::dieWithError($cert->getError());
            }
            $db->commitTransaction();
        }
        FatApp::redirectUser(MyUtility::makeUrl('Certificates', 'evaluation', [$id], CONF_WEBROOT_FRONTEND));
    }

    /**
     * Get html content for certificate
     *
     * @param string $code
     * @return string
     */
    private function getContent($certificateType)
    {
        $backgroundImg = $logoImg = CONF_INSTALLATION_PATH . 'public/images/noimage.jpg';

        /* get background and logo images */
        $imageData = (new Afile(Afile::TYPE_CERTIFICATE_BACKGROUND_IMAGE, 0))->getFile($certificateType, false);
        if (isset($imageData['file_path']) && file_exists(CONF_UPLOADS_PATH . $imageData['file_path'])) {
            $backgroundImg = CONF_UPLOADS_PATH . $imageData['file_path'];
        }

        $imageData = (new Afile(Afile::TYPE_CERTIFICATE_LOGO, $this->siteLangId))->getFile(0, false);
        if (isset($imageData['file_path']) && file_exists(CONF_UPLOADS_PATH . $imageData['file_path'])) {
            $logoImg = CONF_UPLOADS_PATH . $imageData['file_path'];
        }

        $this->set('backgroundImg', $backgroundImg);
        $this->set('logoImg', $logoImg);
        $this->set('layoutDir', Language::getAttributesById($this->siteLangId, 'language_direction'));

        return  $this->_template->render(false, false, 'certificates/generate.php', true);
    }
}
