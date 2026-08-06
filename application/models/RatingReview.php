<?php

/**
 * This class is used to handle Rating FReview
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class RatingReview extends MyAppModel
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
        parent::__construct(static::DB_TBL, 'ratrev_id', $id);
        $this->userId = $userId;
        $this->teacherId = $teacherId;
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
        }
        return true;
    }

    public function getDetail()
    {
        $srch = new SearchBase(RatingReview::DB_TBL, 'ratrev');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = ratrev.ratrev_user_id', 'learner');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'teacher.user_id = ratrev.ratrev_teacher_id', 'teacher');
        $srch->addMultipleFields([
            'learner.user_first_name as learner_first_name',
            'learner.user_last_name as learner_last_name',
            'learner.user_deleted as learner_deleted',
            'teacher.user_deleted as teacher_deleted',
            'teacher.user_first_name as teacher_first_name',
            'teacher.user_last_name as teacher_last_name',
            'teacher.user_email as teacher_email',
            'teacher.user_lang_id as teacher_lang_id',
            'ratrev.*'
        ]);
        $srch->addCondition('ratrev_id', '=', $this->mainTableRecordId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Get Feedback Form
     * @return Form
     */
    public static function getFeedbackForm(): Form
    {
        $frm = new Form('feedbackFrm');
        $frm = CommonHelper::setFormProperties($frm);
        $fld = $frm->addSelectBox(Label::getLabel('LBL_RATING'), 'ratrev_overall', [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5],
                '', ['class' => 'star-rating'], Label::getLabel('LBL_SELECT'));
        $fld->requirements()->setRequired(true);
        $fld->setWrapperAttribute('class', 'rating-f');
        $frm->addRequiredField(Label::getLabel('LBL_TITLE'), 'ratrev_title');
        $frm->addHiddenField('', 'ratrev_type_id');
        $frm->addTextArea(Label::getLabel('LBL_DESCRIPTION'), 'ratrev_detail')->requirements()->setRequired();
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SUBMIT'));
        return $frm;
    }

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
    }

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
    }

    /**
     * get teacher reviews
     *
     * @param integer $sorting
     * @param integer $pageno
     * @param integer $pageSize
     * @return array
     */
    public function getReviews(string $sorting = self::SORTBY_NEWEST, int $pageno = 1, int $pageSize = 4): array
    {
        $srch = new SearchBase(RatingReview::DB_TBL, 'ratrev');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id=ratrev.ratrev_user_id', 'learner');
        $srch->addCondition('ratrev.ratrev_status', '=', RatingReview::STATUS_APPROVED);
        $srch->addCondition('ratrev.ratrev_teacher_id', '=', $this->teacherId);
        $srch->addMultipleFields([
            'user_first_name', 'user_last_name', 'ratrev_id', 'ratrev_user_id',
            'ratrev_title', 'ratrev_detail', 'ratrev_overall', 'ratrev_created'
        ]);
        $srch->addOrder('ratrev.ratrev_created', $sorting);
        $srch->setPageSize($pageSize);
        $srch->setPageNumber($pageno);
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

}
