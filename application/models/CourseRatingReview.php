<?php

/**
 * This class is used to handle Course Rating Review
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseRatingReview extends MyAppModel
{

    const DB_TBL = 'tbl_rating_reviews';
    const DB_TBL_PREFIX = 'ratrev_';
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_DECLINED = 2;
    const SORTBY_NEWEST = 'DESC';
    const SORTBY_OLDEST = 'ASC';

    private $userId = 0;
    private $teacherId = 0;

    /**
     * Initialize Rating Review
     * 
     * @param int $teacherId
     * @param int $userId
     * @param int $id
     */
    public function __construct(int $teacherId = 0, int $userId = 0, int $id = 0)
    {
        $this->userId = $userId;
        $this->teacherId = $teacherId;
        parent::__construct(static::DB_TBL, 'ratrev_id', $id);
    }

    /**
     * Get Statues
     * 
     * @param int $key
     * @return string|array
     */
    public static function getStatues(int $key = null)
    {
        $arr = [
            static::STATUS_PENDING => Label::getLabel('STATUS_PENDING'),
            static::STATUS_APPROVED => Label::getLabel('STATUS_APPROVED'),
            static::STATUS_DECLINED => Label::getLabel('STATUS_DECLINED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Sort Array
     * 
     * @param string $key
     * @return string|array
     */
    public static function getSortTypes($key = null)
    {
        $arr = [
            static::SORTBY_NEWEST => Label::getLabel('LBL_SORT_BY_NEWEST'),
            static::SORTBY_OLDEST => Label::getLabel('LBL_SORT_BY_OLDEST'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Rating Aspects
     * 
     * @return array
     */
    public static function getRatingAspects(): array
    {
        return [
            'ratrev_overall' => Label::getLabel('LBL_OVERALL'),
        ];
    }

    /**
     * Add Review
     * 
     * @param int $recordType
     * @param int $recordId
     * @param array $data
     * @return bool
     */
    public function addReview(int $recordType, int $recordId, array $data): bool
    {
        $defaultStatus = FatApp::getConfig('CONF_DEFAULT_REVIEW_STATUS');
        unset($data['ratrev_status']);
        $this->assignValues($data);
        $this->setFldValue('ratrev_user_id', $this->userId);
        $this->setFldValue('ratrev_teacher_id', $this->teacherId);
        $this->setFldValue('ratrev_type', $recordType);
        $this->setFldValue('ratrev_type_id', $recordId);
        $this->setFldValue('ratrev_created', date('Y-m-d H:i:s'));
        $this->setFldValue('ratrev_status', $defaultStatus);
        if (!$this->save()) {
            return false;
        }
        if ($defaultStatus == static::STATUS_APPROVED) {
            (new TeacherStat($this->teacherId))->setRatingReviewCount();
            (new Course($recordId))->setRatingReviewCount();
        }
        return true;
    }

    /**
     * Get Feedback Form
     *
     * @return Form
     */
    public static function getFeedbackForm(): Form
    {
        $frm = new Form('feedbackFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $rateLabel = Label::getLabel('L_RATE');
        $attr = ['class' => 'star-rating'];
        $rationOption = [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5];
        foreach (static::getRatingAspects() as $key => $value) {
            $fld = $frm->addSelectBox($value, $key, $rationOption, '', $attr, $rateLabel);
            $fld->requirements()->setRequired(true);
            $fld->setWrapperAttribute('class', 'rating-f');
        }
        $frm->addRequiredField(Label::getLabel('LBL_TITLE'), 'ratrev_title');
        $frm->addHiddenField('', 'ratrev_type_id');
        $frm->addHiddenField('', 'ordcrs_id');
        $frm->addTextArea(Label::getLabel('LBL_DESCRIPTION'), 'ratrev_detail')->requirements()->setRequired();
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SUBMIT'));
        return $frm;
    }

    /**
     * Send notification to teacher
     *
     * @param array $data
     * @return bool
     */
    public function sendMailToTeacher(array $data)
    {
        $vars = [
            '{learner_name}' => $data['learner_first_name'] . ' ' . $data['learner_last_name'],
            '{teacher_name}' => $data['teacher_first_name'] . ' ' . $data['teacher_last_name'],
        ];
        $mail = new FatMailer($data['teacher_lang_id'], 'feedback_mail_to_teacher');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$data['teacher_email']])) {
            $this->error = $mail->getError();
            return false;
        }
        $this->setFldValue('ratrev_teacher_notify', AppConstant::YES);
        $this->save();
        return true;
    }

    /**
     * Send notification to admin
     *
     * @param array $data
     * @return bool
     */
    public function sendMailToAdmin(array $data)
    {
        $vars = [
            '{learner_name}' => $data['learner_first_name'] . ' ' . $data['learner_last_name'],
            '{teacher_name}' => $data['teacher_first_name'] . ' ' . $data['teacher_last_name'],
        ];
        $mail = new FatMailer(MyUtility::getSiteLangId(), 'feedback_mail_to_admin');
        $mail->setVariables($vars);
        if (!$mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Rating Stats 
     *
     * @param int $courseId
     * @return array
     */
    public function getRatingStats(int $courseId)
    {
        $srch = new SearchBase(CourseRatingReview::DB_TBL, 'ratrev');
        $srch->addCondition('ratrev.ratrev_status', '=', RatingReview::STATUS_APPROVED);
        $srch->addCondition('ratrev.ratrev_type', '=', AppConstant::COURSE);
        $srch->addCondition('ratrev.ratrev_type_id', '=', $courseId);
        $srch->addMultipleFields([
            'ratrev_overall',
            'COUNT(ratrev_id) as rate_count'
        ]);
        $srch->addGroupBy('ratrev_overall');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $reviews = FatApp::getDb()->fetchAllAssoc($srch->getResultSet(), 'ratrev_overall');
        $totalCount = array_sum($reviews);
        $rating = [];
        for ($i = 5; $i >= 1; $i--) {
            $rating[] = [
                'rating' => $i,
                'count' => $reviews[$i] ?? 0,
                'percent' => (isset($reviews[$i])) ? round(($reviews[$i] / $totalCount) * 100, 2) : 0,
            ];
        }
        return $rating;
    }

}
