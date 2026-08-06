<?php

/**
 * This class is used to define Forum Report Issue Reasons
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumReportedQuestion extends MyAppModel
{
    private $userId = 0;
    private $queId = 0;

    const DB_TBL = 'tbl_forum_question_reported';
    const DB_TBL_PREFIX = 'fquerep_';

    /**
     * Initialize ForumTag Class
     *
     * @param int $reasonId
     */
    public function __construct(int $userId, int $queId, int $repId = 0)
    {
        parent::__construct(static::DB_TBL, 'fquerep_id', $repId);
        $this->userId = FatUtility::int($userId);
        $this->queId = FatUtility::int($queId);
    }

    public static function getSearchObject(int $langId): Object
    {
        $srch = new SearchBased(static::DB_TBL, 'frmRepQue');
        
        $srch->joinTable(ForumQuestion::DB_TBL, 'INNER JOIN', 'fque_id = fquerep_fque_id');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = fquerep_user_id');
        
        $srch->joinTable(ForumReportIssueReason::DB_TBL, 'INNER JOIN', 'fquerep_frireason_id = frireason_id');
        $srch->joinTable(ForumReportIssueReason::DB_TBL_LANG, 'LEFT JOIN', 'frireason_id = frireasonlang_frireason_id AND frireasonlang_lang_id = ' . $langId);

        return $srch;
    }

    public function getReportDetail(int $langId, array $attr = []): array
    {
        if (empty($attr)) {
            $attr = ['fquerep_id'];
        }
        
        $srch = static::getSearchObject($langId);
        $srch->addMultipleFields($attr);
        $srch->addCondition('fquerep_fque_id', '=', $this->queId);
        $srch->addCondition('fquerep_user_id', '=', $this->userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet()) ?? [];
    }

    public function sendStatusUpdateNotifications(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications()) {
            return true;
        }

        $authUser = User::getAttributesById($data['author_id'], ['user_first_name', 'user_last_name', 'user_email', 'user_lang_id', 'user_is_teacher']);
        $byUser = User::getAttributesById($data['by_user_id'], ['user_first_name', 'user_last_name', 'user_email', 'user_lang_id', 'user_is_teacher']);

        $data['auth_user_lang_id'] = $authUser['user_lang_id'];
        $data['auth_full_name'] = $authUser['user_first_name'] . ' ' . ($authUser['user_last_name'] ?? '');
        $data['user_email'] = $authUser['user_email'];
        $data['rep_user_full_name'] = $byUser['user_first_name'] . ' ' . ($byUser['user_last_name'] ?? '');
        $data['rep_user_lang_id'] = $byUser['user_lang_id'];
        $data['status_txt'] = Label::getLabel('LBL_question_reported_request_status_approved');
                
        if (ForumQuestion::QUEST_REPORTED_CANCELLED == $data['fquerep_status']) {
            $data['status_txt'] = Label::getLabel('LBL_question_reported_request_status_cancelled');
        }

        $this->sendStatusUpdateEmailToAuthor($data);
        
        $data['user_type'] = (1 == $authUser['user_is_teacher']) ? User::TEACHER : User::LEARNER;
        $this->sendStatusUpdateNotificationToAuthor($data);

        $data['rep_user_lang_id'] = $byUser['user_lang_id'];

        $data['user_email'] = $byUser['user_email'];
        $data['user_type'] = (1 == $byUser['user_is_teacher']) ? User::TEACHER : User::LEARNER;
        $this->sendStatusUpdateEmailToReporter($data);
        $this->sendStatusUpdateNotificationToReporter($data);

        return true;
    }

    public function sendStatusUpdateNotificationToAuthor(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('SN')) {
            return true;
        }

        $notify = new Notification($data['author_id'], Notification::TYPE_FORUM_QUE_SPAM_STATUS_UPDATE_TO_AUTHOR);
        $notify->sendNotification([
            '{que-title}' => $data['fque_title'],
            '{status-txt}' => $data['status_txt'],
            '{adm-comments}' => $data['admin_comments'],
        ], $data['user_type']);

        return true;
    }

    public function sendStatusUpdateEmailToAuthor(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }

        $mail = new FatMailer($data['auth_user_lang_id'], 'author_question_reported_request_status_change');
        $mail->setVariables([
            '{reported_by_full_name}' => $data['rep_user_full_name'],
            '{author_full_name}' => $data['auth_full_name'],
            '{status}' => $data['status_txt'],
            '{question_title}' => $data['fque_title'],
            '{admin_comments}' => nl2br($data['admin_comments'])
        ]);

        $mail->sendMail([$data['user_email']]);
        return true;
    }

    public function sendStatusUpdateNotificationToReporter(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('SN')) {
            return true;
        }

        $notify = new Notification($data['by_user_id'], Notification::TYPE_FORUM_QUE_SPAM_STATUS_UPDATE_TO_REP_USER);
        $notify->sendNotification([
            '{que-title}' => $data['fque_title'],
            '{status-txt}' => $data['status_txt'],
            '{adm-comments}' => $data['admin_comments'],
        ], $data['user_type']);

        return true;

        return true;
    }

    public function sendStatusUpdateEmailToReporter(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        
        $mail = new FatMailer($data['rep_user_lang_id'], 'rep_by_question_reported_request_status_change');

        $mail->setVariables([
            '{reported_by_full_name}' => $data['rep_user_full_name'],
            '{author_full_name}' => $data['auth_full_name'],
            '{status}' => $data['status_txt'],
            '{question_title}' => $data['fque_title'],
            '{admin_comments}' => nl2br($data['admin_comments'])
        ]);

        $mail->sendMail([$data['user_email']]);
        return true;
    }
}
