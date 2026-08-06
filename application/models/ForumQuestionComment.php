<?php

/**
 * This class is used for Forum Question Comment
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumQuestionComment extends MyAppModel
{

    private $userId;
    private $queId;

    const DB_TBL = 'tbl_forum_question_comments';
    const DB_TBL_PREFIX = 'fquecom_';

    /**
     * Initialize ForumQuestionComment Class
     *
     * @param int $forumQueCommId
     */
    public function __construct(int $userId, int $queId = 0, int $forumQueCommId = 0)
    {
        parent::__construct(static::DB_TBL, 'fquecom_id', $forumQueCommId);
        $this->userId = $userId;
        $this->queId = $queId;
    }

    public static function getSearchObject(): object
    {
        die(__FILE__ . 'Use Comments Search class (ForumQuestionCommentSearch)');
        $srch = new SearchBase(ForumQuestionComment::DB_TBL, 'fqc');
        $srch->joinTable(ForumQuestion::DB_TBL, 'INNER JOIN', 'fque_id = fquecom_fque_id');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = fquecom_user_id');
        $srch->joinTable(ForumStat::DB_TBL, 'LEFT JOIN', 'fquecom_id = fstat_record_id AND fstat_record_type = ' . ForumReaction::REACT_TYPE_COMMENT);
        return $srch;
    }

    /* To Confirm whether a user can mark question comment as accept or unaccept */
    public function canUserMarkComment(): bool
    {
        $srch = new ForumQuestionCommentSearch($this->queId);
        $srch->joinTable(ForumQuestion::DB_TBL, 'INNER JOIN', 'fque_id = fquecom_fque_id');
        $srch->addCondition('fquecom_id', '=', $this->mainTableRecordId);
        $srch->applyPrimaryConditions();
        $srch->addCondition('fque_status', 'IN', [ForumQuestion::FORUM_QUE_PUBLISHED, ForumQuestion::FORUM_QUE_RESOLVED]);
        $srch->addMultipleFields(['fque_user_id', 'fquecom_fque_id', 'fque_status', 'fquecom_deleted']);
        $srch->doNotCalculateRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($row)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        if ($this->userId != $row['fque_user_id']) {
            $this->error = Label::getLabel('LBL_You_are_not_the_owner_of_this_question');
            return false;
        }
        return true;
    }

    public function sendPostCommentNotifications(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications()) {
            return true;
        }
        if ($data['by_user_id'] == $data['author_id']) {
            return true;
        }
        $authUser = User::getAttributesById($data['author_id'], ['user_first_name', 'user_last_name', 'user_email', 'user_lang_id', 'user_is_teacher']);
        $byUser = User::getAttributesById($data['by_user_id'], ['user_first_name', 'user_last_name']);
        $data['posted_by_full_name'] = $byUser['user_first_name'] . ' ' . ($byUser['user_last_name'] ?? '');
        $data['author_full_name'] = $authUser['user_first_name'] . ' ' . ($authUser['user_last_name'] ?? '');
        $data['author_email'] = $authUser['user_email'];
        $data['author_lang_id'] = $authUser['user_lang_id'];
        $this->sendPostCommentEmail($data);
        $data['user_type'] = (1 == $authUser['user_is_teacher']) ? User::TEACHER : User::LEARNER;
        $this->sendPostCommentSysNotification($data);
        return true;
    }

    public function sendPostCommentSysNotification(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('SN')) {
            return true;
        }
        $slug = ForumQuestion::getAttributesById($data['fquecom_fque_id'],'fque_slug');
        $notify = new Notification($data['author_id'], Notification::TYPE_FORUM_QUE_COMMENT_POSTED_TO_AUTHOR);
        $staus = $notify->sendNotification([
            '{posted-by}' => $data['posted_by_full_name'],
            '{que-title}' => $data['que_title'],
            '{link}' => MyUtility::makeFullUrl('Forum', 'View', [$slug], CONF_WEBROOT_FRONTEND),
                ], $data['user_type']);
        return true;
    }

    public function sendPostCommentEmail(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        if ($data['by_user_id'] == $data['author_id']) {
            return true;
        }
        $mail = new FatMailer($data['author_lang_id'], 'posted_new_comment');
        $mail->setVariables([
            '{posted_by_full_name}' => $data['posted_by_full_name'],
            '{author_full_name}' => $data['author_full_name'],
            '{question_title}' => $data['que_title'],
            '{question_view_link}' => $data['que_link']
        ]);
        $mail->sendMail([$data['author_email']]);
        return true;
    }

    public function sendCommentAcceptStatusNotifications(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications()) {
            return true;
        }
        if ($data['by_user_id'] == $data['author_id']) {
            return true;
        }
        $byUser = User::getAttributesById($data['by_user_id'], ['user_first_name', 'user_last_name', 'user_lang_id', 'user_email', 'user_is_teacher']);
        $data['posted_by_full_name'] = $byUser['user_first_name'] . ' ' . ($byUser['user_last_name'] ?? '');
        $data['user_lang_id'] = $byUser['user_lang_id'];
        $data['user_email'] = $byUser['user_email'];
        $this->sendCommentAcceptedEmail($data);
        $data['user_type'] = (1 == $byUser['user_is_teacher']) ? User::TEACHER : User::LEARNER;
        $this->sendCommentAcceptedSysNotification($data);
        return true;
    }

    public function sendCommentAcceptedSysNotification(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('SN')) {
            return true;
        }
        $slug = ForumQuestion::getAttributesById($this->queId,'fque_slug');
        $notify = new Notification($data['by_user_id'], Notification::TYPE_FORUM_QUE_COMMENT_ACCEPTED_TO_USER);
        $staus = $notify->sendNotification([
            '{que-title}' => $data['question_title'],
            '{link}' => MyUtility::makeFullUrl('Forum', 'View', [$slug], CONF_WEBROOT_FRONTEND),
                ], $data['user_type']);
        return true;
    }

    public function sendCommentAcceptedEmail(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        if ($data['by_user_id'] == $this->userId) {
            return true;
        }
        $mail = new FatMailer($data['user_lang_id'], 'comment_accepted_to_commented_by_user');
        $mail->setVariables([
            '{posted_by_full_name}' => $data['posted_by_full_name'],
            '{question_title}' => $data['question_title'],
            '{question_view_link}' => $data['question_view_link']
        ]);
        $mail->sendMail([$data['user_email']]);
        return true;
    }

}
