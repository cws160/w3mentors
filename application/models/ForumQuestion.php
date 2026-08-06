<?php

/**
 * This class is used for Forum Question
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumQuestion extends MyAppModel
{

    private $userId;
    private $langId;

    public const DB_TBL = 'tbl_forum_questions';
    public const DB_TBL_PREFIX = 'fque_';
    public const DB_TBL_QUEST_VIEW = 'tbl_forum_question_views';
    public const DB_TBL_QUEST_VIEW_PREFIX = 'fqueview_';
    public const DB_TBL_QUEST_REPORTED = 'tbl_forum_question_reported';
    public const DB_TBL_QUEST_REPORTED_PREFIX = 'fquerep_';
    public const FORUM_QUE_DRAFT = 0;
    public const FORUM_QUE_PUBLISHED = 1;
    public const FORUM_QUE_RESOLVED = 2;
    public const FORUM_QUE_SPAMMED = 3;
    public const QUEST_REPORTED_PENDING = 0;
    public const QUEST_REPORTED_ACCEPTED = 1;
    public const QUEST_REPORTED_CANCELLED = 2;
    public const QUEST_TITLE_MIN_LENGTH = 10;
    public const QUEST_TITLE_MAX_LENGTH = 150;

    /**
     * Initialize ForumQuestion Class
     *
     * @param int $id
     * @param int $userId
     * @param int $langId
     */
    public function __construct(int $id = 0, int $userId = 0, int $langId = 0)
    {
        $id = FatUtility::int($id);
        parent::__construct(static::DB_TBL, 'fque_id', $id);
        $this->userId = FatUtility::int($userId);
        $this->langId = FatUtility::int($langId);
    }

    /**
     * Get Statuses of Questions
     *
     * return type array
     */
    public static function getQuestionStatusArray(int $key = null)
    {
        $arr = [
            static::FORUM_QUE_DRAFT => Label::getLabel('LBL_Drafted'),
            static::FORUM_QUE_PUBLISHED => Label::getLabel('LBL_Published'),
            static::FORUM_QUE_RESOLVED => Label::getLabel('LBL_Resolved'),
            static::FORUM_QUE_SPAMMED => Label::getLabel('LBL_Spammed'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Statuses of Question to Add
     *
     * return type array
     */
    public static function getAddQuestionStatusArray(): array
    {
        return [
            static::FORUM_QUE_DRAFT => Label::getLabel('LBL_Drafted'),
            static::FORUM_QUE_PUBLISHED => Label::getLabel('LBL_Published'),
        ];
    }

    /**
     * Get Statuses of Question Report
     *
     * return type array
     */
    public static function getReportStatusArray(int $key = null)
    {
        $arr = [
            static::QUEST_REPORTED_PENDING => Label::getLabel('LBL_Pending'),
            static::QUEST_REPORTED_ACCEPTED => Label::getLabel('LBL_Accepted'),
            static::QUEST_REPORTED_CANCELLED => Label::getLabel('LBL_Cancelled')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Save Question
     * 
     * @param array $data
     * @return bool
     */
    public function saveQuestion(array $data): bool
    {
        if (0 < $this->mainTableRecordId) {
            $data['fque_updated_on'] = date('Y-m-d H:i:s');
            unset($data['fque_added_on']);
        } else {
            $data['fque_added_on'] = date('Y-m-d H:i:s');
            $data['fque_updated_on'] = date('Y-m-d H:i:s');
        }

        $this->setFldValue('fque_user_id', $this->userId);
        $this->setFldValue('fque_slug', $this->getSlug($data['fque_slug']));
        $this->assignValues($data);
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    /**
     * Function to validate & create unique slug
     *
     * @param string $title
     * @return string
     */
    private function getSlug(string $title)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('fque_slug', '=', $title);
        $srch->doNotCalculateRecords();
        $srch->addFld('fque_slug');
        $srch->setPageSize(1);
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            return CommonHelper::seoUrl($title) . '-' . $this->getMainTableRecordId();
        }
        return CommonHelper::seoUrl($title);
    }

    public function getData()
    {
        $srch = new ForumQuestionSearch();
        if (0 < $this->langId) {
            $srch->addLanguageCondition($this->langId);
        }
        $srch->addCondition('fque.fque_id', '=', 'mysql_func_' . $this->mainTableRecordId, 'AND', true);
        if (0 < $this->userId) {
            $srch->addUserIdCondition($this->userId);
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!is_array($row)) {
            return [];
        }
        return $row;
    }

    /**
     * Get Tags
     * 
     * @param int $langId
     * @param bool $assoc
     * @return array
     */
    public function getTags(array $queId, int $langId, bool $assoc = true): array
    {
        $srch = new SearchBase(ForumTag::DB_TBL_TAGS_TO_QUESTION);
        $srch->joinTable(ForumTag::DB_TBL, 'INNER JOIN', 'ftag_id = ftagque_ftag_id');
        $srch->addCondition('ftagque_fque_id', 'IN', $queId);
        if (0 < $langId) {
            $srch->addCondition('ftag_language_id', '=', 'mysql_func_' . $langId, 'AND', true);
        }
        $srch->addCondition('ftag_active', '=', 'mysql_func_' . AppConstant::YES, 'AND', true);
        $srch->addCondition('ftag_deleted', '=', 'mysql_func_' . AppConstant::NO, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $srch->addOrder('ftag_name', 'ASC');
        $srch->addOrder('ftag_id', 'DESC');
        if (true == $assoc) {
            $srch->addMultipleFields(['ftag_id', 'ftag_name']);
            return FatApp::getDb()->fetchAllAssoc($srch->getResultSet(), 'ftag_id');
        } else {
            $srch->addMultipleFields(['ftag_name', 'ftag_id', 'ftagque_fque_id']);
            return FatApp::getDb()->fetchAll($srch->getResultSet());
        }
    }

    public function unbindAllTags(): bool
    {
        $stmt = ['smt' => ForumTag::DB_TBL_TAGS_TO_QUESTION_PREFIX . 'fque_id = ?', 'vals' => [$this->mainTableRecordId]];
        if (!FatApp::getDb()->deleteRecords(ForumTag::DB_TBL_TAGS_TO_QUESTION, $stmt)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    public function bindTags(array $tags)
    {
        $db = FatApp::getDb();
        $qry = 'INSERT INTO ' . ForumTag::DB_TBL_TAGS_TO_QUESTION . ' (`ftagque_fque_id`, `ftagque_ftag_id`) VALUES ';
        $vals = '';
        foreach ($tags as $key => $val) {
            $vals .= '(' . $this->mainTableRecordId . ',' . $val . '),';
        }
        $vals = rtrim($vals, ',');
        $qry .= $vals . ';';
        $db->prepareStatement($qry);
        $db->query($qry);
        return true;
    }

    /**
     * Per new session (php session) add view count
     * viewerSessionId string session_id
     * viewerIp string user IP Address
     * return bool true | false
     */
    public function addViewEntry($viewerSessionId, $viewerIp)
    {
        if (1 > $this->mainTableRecordId) {
            return false;
        }
        $record = new TableRecord(static::DB_TBL_QUEST_VIEW);
        $row = static::getUserViewEntry($this->mainTableRecordId, $viewerSessionId);
        if (!empty($row)) {
            if (0 < $this->userId && 0 == $row['fqueview_user_id']) {
                $record->setFldValue('fqueview_user_id', $this->userId);
            }
            $record->setFldValue('fqueview_added_on', date('Y-m-d H:i:s'));
            $record->setFldValue('fqueview_user_ip', $viewerIp);
            $whr = [
                'smt' => 'fqueview_id = ?',
                'vals' => [$row['fqueview_id']]
            ];
            $record->update($whr);
            return false;
        }
        $record->setFldValue('fqueview_user_id', $this->userId);
        $record->setFldValue('fqueview_user_ip', $viewerIp);
        $record->setFldValue('fqueview_session_id', $viewerSessionId);
        $record->setFldValue('fqueview_fque_id', $this->mainTableRecordId);
        $record->setFldValue('fqueview_added_on', date('Y-m-d H:i:s'));
        if (!$record->addNew()) {
            $this->error = $record->getError();
            return false;
        }
        $forumStat = new ForumStat($this->mainTableRecordId, ForumReaction::REACT_TYPE_QUESTION);
        $forumStat->updateQuestionViewCount();
        return true;
    }

    public static function getUserViewEntry($queId, $sessionId)
    {
        if (1 > $queId) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL_QUEST_VIEW);
        $srch->addCondition('fqueview_session_id', 'LIKE', $sessionId);
        $srch->addCondition('fqueview_fque_id', '=', $queId);
        $srch->doNotCalculateRecords();
        $srch->addMultipleFields(['fqueview_id', 'fqueview_user_id', 'fqueview_session_id']);
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!is_array($row)) {
            return [];
        }
        return $row;
    }

    public static function getReportDetailById($reportId, $attr = '')
    {
        if (empty($attr)) {
            $attr = ['fquerep_id'];
        } elseif (is_string($attr)) {
            $attr = [$attr];
        }
        $srch = new SearchBase(ForumQuestion::DB_TBL_QUEST_REPORTED);
        $srch->addCondition('fquerep_id', '=', $reportId);
        $srch->addMultipleFields($attr);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function canUserReport(int $reportedUserId, int $langId): bool
    {
        if (!UserAuth::isUserLogged()) {
            $this->error = Label::getLabel('ERR_Please_Login_to_Account_First');
            return false;
        }
        if (!$this->loadFromDb()) {
            $this->error = Label::getLabel('ERR_Invalid_question');
            return false;
        }
        if ($reportedUserId == $this->getFldValue('fque_user_id')) {
            $this->error = Label::getLabel('ERR_You_can_not_report_your_own_question');
            return false;
        }
        if (static::FORUM_QUE_SPAMMED == $this->getFldValue('fque_status')) {
            $this->error = Label::getLabel('ERR_question_already_marked_as_spammed');
            return false;
        }
        $repQue = new ForumReportedQuestion($reportedUserId, $this->mainTableRecordId, $langId);
        $reporteddata = $repQue->getReportDetail($langId, ['fquerep_status', 'fquerep_id']);
        if (empty($reporteddata)) {
            return true;
        }
        if (static::QUEST_REPORTED_CANCELLED == $reporteddata['fquerep_status']) {
            $this->error = Label::getLabel('ERR_Question_has_already_been_Reported_by_you_and_request_Cancelled');
            return false;
        }
        if (static::QUEST_REPORTED_PENDING == $reporteddata['fquerep_status']) {
            $this->error = Label::getLabel('ERR_Question_has_already_been_Reported_by_you');
            return false;
        }
        return true;
    }

    public function markAsSpam(): bool
    {
        $vals = [
            'fque_status' => static::FORUM_QUE_SPAMMED,
            'fque_comments_allowed' => AppConstant::NO
        ];
        $this->assignValues($vals);
        if (!$this->save()) {
            return false;
        }
        return true;
    }

    public function canUserComment(): bool
    {
        $srch = new ForumQuestionSearch();
        $srch->applyPrimaryConditions();
        $srch->joinWithUsers();
        $srch->addStatusCondition([ForumQuestion::FORUM_QUE_PUBLISHED]);
        $srch->applySearchConditions(['id' => $this->mainTableRecordId]);
        $srch->joinWithStats();
        $srch->addMultipleFields(['fque_id', 'fque_status', 'fque_deleted', 'fque_comments_allowed', 'user_id']);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        if (!is_array($data)) {
            $this->error = Label::getLabel('ERR_Invalid_Request');
            return false;
        }
        if ($data['fque_comments_allowed'] != AppConstant::YES) {
            $this->error = Label::getLabel('LBL_comments_not_allowed');
            return false;
        }
        return true;
    }

    public function sendReportSpamNotifications(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications()) {
            return true;
        }
        $authUser = User::getAttributesById($data['author_id'], ['user_first_name', 'user_last_name', 'user_email', 'user_is_teacher', 'user_lang_id']);
        $byUser = User::getAttributesById($data['by_user_id'], ['user_first_name', 'user_last_name']);
        $data['auth_user_lang_id'] = $authUser['user_lang_id'];
        $data['auth_full_name'] = $authUser['user_first_name'] . ' ' . ($authUser['user_last_name'] ?? '');
        $data['auth_user_email'] = $authUser['user_email'];
        $data['user_type'] = (User::TEACHER == $authUser['user_is_teacher']) ? User::TEACHER : User::LEARNER;
        $data['by_user_full_name'] = $byUser['user_first_name'] . ' ' . ($byUser['user_last_name'] ?? '');
        $this->sendReportSpamEmailToAdmin($data);
        $this->sendReportSpamEmailToAuthor($data);
        $this->sendReportSpamNotificationToAuthor($data);
        return true;
    }

    public function sendReportSpamNotificationToAuthor(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('SN')) {
            return true;
        }
        $notify = new Notification($data['author_id'], Notification::TYPE_FORUM_QUE_SPAM_REPORTED_TO_AUTHOR);
        $staus = $notify->sendNotification([
            '{que-title}' => $data['que_title'],
            '{link}' => MyUtility::makeFullUrl('Forum', 'View', [$this->getFldValue('fque_slug')], CONF_WEBROOT_FRONTEND),
                ], $data['user_type']);
        return true;
    }

    public function sendReportSpamEmailToAuthor(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        $mail = new FatMailer($data['auth_user_lang_id'], 'author_question_spam_reported');
        $mail->setVariables([
            '{reported_by_full_name}' => $data['by_user_full_name'],
            '{author_full_name}' => $data['auth_full_name'],
            '{question_title}' => $data['que_title'],
            '{question_view_link}' => $data['que_link']
        ]);
        $mail->sendMail([$data['auth_user_email']]);
        return true;
    }

    public function sendReportSpamEmailToAdmin(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        $mail = new FatMailer(FatApp::getConfig('CONF_DEFAULT_LANG'), 'admin_question_spam_reported');
        $mail->setVariables([
            '{reported_by_full_name}' => $data['by_user_full_name'],
            '{author_full_name}' => $data['auth_full_name'],
            '{question_title}' => $data['que_title'],
            '{question_view_link}' => $data['que_link']
        ]);
        $mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')]);
        return true;
    }

    public function sendPublishStatusNotifications(bool $republish): bool
    {
        $authUser = User::getAttributesById($this->userId, ['user_first_name', 'user_last_name', 'user_lang_id']);
        $this->loadFromDb();
        $data['author_full_name'] = $authUser['user_first_name'] . ' ' . ($authUser['user_last_name'] ?? '');
        $data['fque_title'] = $this->getFldValue('fque_title');
        $data['que_link'] = MyUtility::makeFullUrl('Forum', 'View', [$this->getFldValue('fque_slug')], CONF_WEBROOT_FRONTEND);
        $this->sendPublishedEmailToAdmin($republish, $data);
        if (true !== ForumUtility::canSendNotifications()) {
            return true;
        }
        $data['author_id'] = $this->userId;
        $queTags = $this->getTags([$this->mainTableRecordId], $this->langId);
        $queTags = array_keys($queTags);
        $subscribedUsers = ForumTag::getSubscribedUsersList($queTags);
        if (1 > count($subscribedUsers)) {
            return true;
        }
        $this->sendPublishedEmailToUsers($republish, $data, $subscribedUsers);
        $this->sendPublishedNotificationToUsers($republish, $data, $subscribedUsers);
        return true;
    }

    public function sendPublishedEmailToAdmin(bool $republish, array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        $tpl = 'question_published_to_admin';
        if (true === $republish) {
            $tpl = 'question_republished_to_admin';
        }
        $mail = new FatMailer(FatApp::getConfig('CONF_DEFAULT_LANG'), $tpl);
        $mail->setVariables([
            '{author_full_name}' => $data['author_full_name'],
            '{question_title}' => $data['fque_title'],
            '{question_view_link}' => $data['que_link']
        ]);
        $mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')]);
        return true;
    }

    public function sendPublishedNotificationToUsers(bool $republish, array $data, array $subscribedUsers): bool
    {
        if (true !== ForumUtility::canSendNotifications('SN')) {
            return true;
        }
        $totalUsersCount = count($subscribedUsers);
        if (1 > $totalUsersCount) {
            return true;
        }
        if (array_key_exists($data['author_id'], $subscribedUsers)) {
            unset($subscribedUsers[$data['author_id']]);
            $totalUsersCount = $totalUsersCount - 1;
        }
        $title = 'NOTIFI_TITLE_FORUM_QUE_PUB_TO_SUBSC_TAG_USER';
        $desc = 'NOTIFI_DESC_FORUM_QUE_PUB_TO_SUBSC_TAG_USER_{que-title}_{auth-name}';
        $type = Notification::TYPE_FORUM_QUE_PUB_TO_SUBSC_TAG_USER;
        if (true === $republish) {
            $title = 'NOTIFI_TITLE_FORUM_QUE_REPUB_TO_SUBSC_TAG_USER';
            $desc = 'NOTIFI_DESC_FORUM_QUE_REPUB_TO_SUBSC_TAG_USER_{que-title}_{auth-name}';
            $type = Notification::TYPE_FORUM_QUE_REPUB_TO_SUBSC_TAG_USER;
        }
        $link = MyUtility::makeUrl('Forum', 'View', [$this->mainTableRecordId], CONF_WEBROOT_URL);
        $userType = User::LEARNER;
        $insertQuery = '';
        $insQryCount = AppConstant::MAX_RECORDS_INSERT_PER_BATCH;
        $inc = 1;
        foreach ($subscribedUsers as $userId => $user) {
            $title = Label::getLabel($title, $user['user_id']);
            $desc = Label::getLabel($desc, $user['user_id']);
            $replacementVars['{auth-name}'] = $data['author_full_name'];
            $replacementVars['{que-title}'] = $data['fque_title'];
            $title = CommonHelper::replaceStringData($title, $replacementVars);
            $desc = CommonHelper::replaceStringData($desc, $replacementVars);
            if (User::TEACHER == $user['user_is_teacher']) {
                $userType = User::TEACHER;
            }
            $insertQuery .= "(
                '',
                '" . $user['user_id'] . "',
                '" . $userType . "',
                '" . $type . "',
                '" . $title . "',
                '" . $desc . "',
                '" . $link . "',
                '" . date('Y-m-d H:i:s') . "'
            ), ";
            if ($insQryCount == $inc) {
                $this->executeInsQuery($this->getInsertNotifyPrefix(), $insertQuery);
                $insertQuery = '';
                $inc = 0;
            }
            $inc++;
        }
        if (($totalUsersCount % $insQryCount) != 0) {
            $this->executeInsQuery($this->getInsertNotifyPrefix(), $insertQuery);
        }
        return true;
    }

    public function sendPublishedEmailToUsers(bool $republish, array $data, array $subscribedUsers): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        if (1 > count($subscribedUsers)) {
            return true;
        }
        $templates = $this->getEmailTemplates($republish);
        if (empty($templates)) {
            return true;
        }
        $layouts = $this->getEmailHeaderFooter();
        $commonVars = $this->getCommonVariablesToSendEmail();
        $this->processUsersToSendEmail($subscribedUsers, $data, $commonVars, $templates, $layouts);
        return true;
    }

    private function getEmailTemplates(bool $republish): array
    {
        $tpl = 'question_published_to_subscribed_tag_users';
        if (true === $republish) {
            $tpl = 'question_republished_to_subscribed_tag_users';
        }
        $srch = new SearchBase(FatMailer::DB_TBL);
        $srch->addCondition('etpl_code', '=', $tpl);
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'etpl_lang_id');
    }

    private function getEmailHeaderFooter(): array
    {
        $srch = new SearchBase(FatMailer::DB_TBL);
        $srch->addCondition('etpl_code', '=', 'emails_header_footer_layout');
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'etpl_lang_id');
    }

    private function processUsersToSendEmail(array $subscribedUsers, array $authorData, array $commonVars, array $templates, array $layouts): bool
    {
        $totalUsersCount = count($subscribedUsers);
        if (array_key_exists($authorData['author_id'], $subscribedUsers)) {
            unset($subscribedUsers[$authorData['author_id']]);
            $totalUsersCount = $totalUsersCount - 1;
        }
        if (1 > $totalUsersCount) {
            return true;
        }
        $insQryCount = AppConstant::MAX_RECORDS_INSERT_PER_BATCH;
        $inc = 1;
        $insertQuery = '';
        $exeInsertQuery = false;
        foreach ($subscribedUsers as $userId => $user) {
            if (!array_key_exists($user['user_lang_id'], $templates)) {
                $totalUsersCount = $totalUsersCount - 1;
                continue;
            }
            $exeInsertQuery = true;
            $replacementVars = [
                '{email_body}' => $templates[$user['user_lang_id']]['etpl_body']
            ];
            $commonVars['{website_name}'] = FatApp::getConfig('CONF_WEBSITE_NAME_' . $user['user_lang_id'], FatUtility::VAR_STRING, '');
            $replacementVars = $replacementVars + $commonVars;
            $replacementVars['{author_full_name}'] = $authorData['author_full_name'];
            $replacementVars['{question_view_link}'] = $authorData['que_link'];
            $replacementVars['{question_title}'] = $authorData['fque_title'];
            $replacementVars['{user_full_name}'] = $user['user_first_name'] . ' ' . $user['user_last_name'];
            $body = CommonHelper::replaceStringData($layouts[$user['user_lang_id']]['etpl_body'], $replacementVars);
            $subject = CommonHelper::replaceStringData($templates[$user['user_lang_id']]['etpl_subject'], $replacementVars);
            $insertQuery .= "(
                '',
                '" . $templates[$user['user_lang_id']]['etpl_code'] . "',
                '" . FatApp::getConfig('CONF_FROM_NAME_' . $user['user_lang_id'], FatUtility::VAR_STRING, '') . "',
                '" . FatApp::getConfig('CONF_FROM_EMAIL') . "',
                '" . $user['user_email'] . "',
                '',
                '',
                '" . $subject . "',
                '" . $body . "',
                '',
                '" . date('Y-m-d H:i:s') . "'
            ), ";
            if ($insQryCount == $inc) {
                $this->executeInsQuery($this->getInsertEmailPrefix(), $insertQuery);
                $insertQuery = '';
                $inc = 0;
            }
            $inc++;
        }
        if (true !== $exeInsertQuery) {
            return true;
        }
        if (($totalUsersCount % $insQryCount) != 0) {
            $this->executeInsQuery($this->getInsertEmailPrefix(), $insertQuery);
        }
        return true;
    }

    private function getCommonVariablesToSendEmail()
    {
        $siteUrl = MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL);
        $socialLinks = '';
        $socialIcons = SocialPlatform::getAll();
        foreach ($socialIcons as $name => $link) {
            $target = empty($link) ? '' : 'target="_blank"';
            $url = empty($link) ? 'javascript:void(0)' : $link;
            $img = MyUtility::makeFullUrl('images', 'sprite.svg', [], CONF_WEBROOT_URL) . '#' . strtolower($name);
            $socialLinks .= '<a style="display:inline-block;vertical-align:top; width:35px; height:35px; margin:0 0 0 5px; border-radius:100%;" href="' .
                    $url . '" ' . $target . '><svg class="icon" style="width: 25px;height: 25px; margin:5px auto 0; display:block;"><use xlink:href="' . $img . '"></use></svg></a>';
        }
        $commonVars = [
            '{website_url}' => $siteUrl,
            '{Company_Logo}' => '<img style="max-width: 160px;" src="' . MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_FRONT_LOGO], CONF_WEBROOT_FRONT_URL) . '" />',
            '{contact_us_url}' => MyUtility::makeFullUrl('contact', '', [], CONF_WEBROOT_FRONT_URL),
            '{notifcation_email}' => FatApp::getConfig('CONF_FROM_EMAIL'),
            '{social_media_icons}' => $socialLinks,
            '{current_date}' => date('M d, Y'),
            '{current_year}' => date('Y'),
        ];
        $fields = [
            'theme_primary_color',
            'theme_secondary_color',
            'theme_secondary_inverse_color'
        ];
        $themeData = Theme::getAttributesById(FatApp::getConfig('CONF_ACTIVE_THEME'), $fields);
        $commonVars['{primary-color}'] = '#' . $themeData['theme_primary_color'];
        $commonVars['{secondary-color}'] = '#' . $themeData['theme_secondary_color'];
        $commonVars['{secondary-inverse-color}'] = '#' . $themeData['theme_secondary_inverse_color'];
        return $commonVars;
    }

    private function executeInsQuery(string $preFix, string $postFix)
    {
        if ('' == $postFix || '' == $preFix) {
            return true;
        }
        $postFix = rtrim($postFix, ', ');
        FatApp::getDb()->query($preFix . $postFix);
        return true;
    }

    private function getInsertEmailPrefix()
    {
        return 'Insert INTO ' . FatMailer::DB_TBL_ARCHIVE . ' (
            `earch_id`,
            `earch_tpl_name`,
            `earch_from_name`,
            `earch_from_email`,
            `earch_to_email`,
            `earch_cc_email`,
            `earch_bcc_email`,
            `earch_subject`,
            `earch_body`,
            `earch_attachemnts`,
            `earch_added`
        ) VALUES ';
    }

    private function getInsertNotifyPrefix()
    {
        return 'Insert INTO ' . Notification::DB_TBL . ' (
            `notifi_id`,
            `notifi_user_id`,
            `notifi_user_type`,
            `notifi_type`,
            `notifi_title`,
            `notifi_desc`,
            `notifi_link`,
            `notifi_added`
        ) VALUES ';
    }

    public static function sanitizeTitle($string)
    {
        $string = preg_replace("/[\s-]+/", " ", $string);
        return trim($string, ' ');
    }

}
