<?php

use Google\Client as GoogleClient;

/**
 * This class is used to handle Notification
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class Notification extends FatModel
{
    public const DB_TBL = "tbl_notifications";
    public const DB_TBL_PREFIX = "notifi_";
    public const TYPE_ADMINISTRATOR = 1;
    /* Lessons */
    public const TYPE_LESSON_SCHEDULED = 2;
    public const TYPE_LESSON_RESCHEDULED = 3;
    public const TYPE_LESSON_CANCELLED = 4;
    public const TYPE_LESSON_COMPLETED = 5;
    /* Issues */
    public const TYPE_ISSUE_REPORTED = 6;
    public const TYPE_ISSUE_RESOLVED = 7;
    public const TYPE_ISSUE_ESCALATED = 8;
    public const TYPE_ISSUE_CLOSED = 9;
    /* Wallet */
    public const TYPE_WALLET_CREDIT = 10;
    public const TYPE_WALLET_DEBIT = 11;
    /* Other */
    public const TYPE_REDEEM_GIFTCARD = 12;
    public const TYPE_WITHDRAW_REQUEST = 13;
    public const TYPE_TEACHER_APPROVAL = 14;
    public const TYPE_TEACHER_DECLINED = 30;
    public const TYPE_CHANGE_PASSWORD = 15;
    /* CLASS */
    public const TYPE_CLASS_CANCELLED = 16;
    /* order */
    public const TYPE_ORDER_PAID = 17;
    public const TYPE_ORDER_CANCELLED = 18;
    public const TYPE_SUBSCRIPTION_CANCELLED = 19;
    public const TYPE_PACKAGE_CANCELLED = 20;
    /* Forum */
    public const TYPE_FORUM_QUE_PUB_TO_SUBSC_TAG_USER = 21;
    public const TYPE_FORUM_QUE_REPUB_TO_SUBSC_TAG_USER = 22;
    public const TYPE_FORUM_TAG_REQ_STATUS_UPDATE_TO_USER = 23;
    public const TYPE_FORUM_QUE_SPAM_REPORTED_TO_AUTHOR = 24;
    public const TYPE_FORUM_QUE_SPAM_STATUS_UPDATE_TO_AUTHOR = 25;
    public const TYPE_FORUM_QUE_SPAM_STATUS_UPDATE_TO_REP_USER = 26;
    public const TYPE_FORUM_QUE_COMMENT_POSTED_TO_AUTHOR = 27;
    public const TYPE_FORUM_QUE_COMMENT_ACCEPTED_TO_USER = 28;
    public const TYPE_NEW_MSG_RECEIVED = 29;

    /* Wallet */
    const TYPE_REWARD_POINT_CREDIT = 31;
    const TYPE_REWARD_POINT_DEBIT = 32;
    /* Affiliate */
    const TYPE_SIGNUP_COMMISSSION_CREDIT_TO_AFFILIATE = 33;
    const TYPE_ORDER_COMMISSSION_CREDIT_TO_AFFILIATE = 34;
    /* Subscription Plan */
    const TYPE_SUB_PLAN_CANCELLED = 35;
    const TYPE_SUB_PLAN_RENEWED = 36;
    const TYPE_SUB_PLAN_EXPIRED = 37;
    const TYPE_SUB_PLAN_INACTIVE = 38;
    const TYPE_SUB_PLAN_RENEWAL_FAILED = 39;
    const TYPE_SUB_PLAN_RENEWAL_REMINDER = 40;
    const TYPE_SUB_PLAN_PURCHASED = 41;
    /* Recurring Lessons */
    const TYPE_RECURRING_LESSON_REMINDER = 42;
    const TYPE_RECURRING_LESSON_RENEWAL_FAILED = 43;
    const TYPE_RECURRING_LESSON_COMPLETED = 44;
    const TYPE_RECURRING_LESSON_SUBJECT_INACTIVE = 45;
    /* Quiz */
    const TYPE_QUIZ_ATTACHED = 46;
    const TYPE_QUIZ_REMOVED = 47;
    const TYPE_QUIZ_COMPLETED = 48;
    const TYPE_QUIZ_EVALUATION_SUBMITTED = 49;
    /* Course Edit Request */
    const TYPE_COURSE_EDIT_REQUEST_STATUS_UPDATE = 50;
    const TYPE_COURSE_MODIFICATIONS_COMPLETED = 51;

    public const FCM_URL = 'https://fcm.googleapis.com/fcm/send';

    private $userId;
    private $token;
    private $type;
    private $title;
    private $desc;

    /**
     * Initialize Notification
     *
     * @param int $userId
     * @param int $type
     */
    public function __construct(int $userId, int $type = 0)
    {
        $this->userId = $userId;
        $this->type = $type;
        $this->token = null;
    }

    /**
     * Send Notification
     *
     * @param array $vars
     * @return bool
     */
    public function sendNotification(array $vars = [], int $userType = 0): bool
    {
        $this->setTitleDesc($vars);
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues([
            'notifi_user_id' => $this->userId,
            'notifi_user_type' => $userType,
            'notifi_type' => $this->type,
            'notifi_title' => $this->title,
            'notifi_desc' => $this->desc,
            'notifi_link' => $vars['{link}'] ?? '',
            'notifi_added' => date('Y-m-d H:i:s'),
        ]);
        if (!$record->addNew()) {
            $this->error = $record->getError();
            return false;
        }
        if (!empty($this->token)) {
            $this->pushNotification();
        }
        return true;
    }

    public function sendMessageNotification(int $senderId, string $message)
    {
        $this->setTitleDesc([]);
        $user = User::getAttributesById($senderId, ['user_first_name', 'user_last_name']);
        $this->title = $user['user_first_name'] . ' ' . $user['user_last_name'];
        $this->desc = $message;
        if (!empty($this->token)) {
            $this->pushNotification($senderId);
        }
    }

    public function pushNotification($senderId = null)
    {
        $firebaseJsonKey = FatApp::getConfig('CONF_SERVICE_ACCOUNT_FIREBASE_JSON', FatUtility::VAR_STRING, '');
        if (trim($firebaseJsonKey) == '') {
            return false;
        }
        $config = json_decode($firebaseJsonKey, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($config['project_id'])) {
            return false;
        }
        $accessToken = $this->getAccessToken($config);
        
        $url = "https://fcm.googleapis.com/v1/projects/{$config['project_id']}/messages:send";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ];

        $payloadData = [
            'message' => [
                'token' => $this->token,
                'data' => [
                    'title' => mb_substr(trim($this->title), 0, 80, 'utf-8'), 
                    'body' => $this->desc,
                ],
                'notification' => [
                    'title' => mb_substr(trim($this->title), 0, 80, 'utf-8'), 
                    'body' => $this->desc,
                ],
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 100);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        curl_close($ch);
    }

    /**
     * Set Title & Description
     *
     * @param int $userId
     * @param array $vars
     */
    private function setTitleDesc(array $vars)
    {
        $user = User::getDetail($this->userId);
        $this->token = $user['user_device_token'] ?? '';
        $langId = FatUtility::int($user['user_lang_id'] ?? 1);
        switch ($this->type) {
            case static::TYPE_LESSON_SCHEDULED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_LESSON_SCHEDULED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_LESSON_SCHEDULED', $langId);
                break;
            case static::TYPE_LESSON_RESCHEDULED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_LESSON_RESCHEDULED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_LESSON_RESCHEDULED', $langId);
                break;
            case static::TYPE_LESSON_CANCELLED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_LESSON_CANCELLED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_LESSON_CANCELLED', $langId);
                break;
            case static::TYPE_CLASS_CANCELLED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_CLASS_CANCELLED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_CLASS_CANCELLED', $langId);
                break;
            case static::TYPE_LESSON_COMPLETED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_LESSON_COMPLETED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_LESSON_COMPLETED', $langId);
                break;
            case static::TYPE_ISSUE_REPORTED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_ISSUE_REPORTED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_ISSUE_REPORTED', $langId);
                break;
            case static::TYPE_ISSUE_RESOLVED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_ISSUE_RESOLVED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_ISSUE_RESOLVED', $langId);
                break;
            case static::TYPE_ISSUE_ESCALATED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_ISSUE_ESCALATED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_ISSUE_ESCALATED', $langId);
                break;
            case static::TYPE_ISSUE_CLOSED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_ISSUE_CLOSED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_ISSUE_CLOSED', $langId);
                break;
            case static::TYPE_WALLET_CREDIT:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_WALLET_CREDIT', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_WALLET_CREDIT', $langId);
                break;
            case static::TYPE_WALLET_DEBIT:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_WALLET_DEBIT', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_WALLET_DEBIT', $langId);
                break;
            case static::TYPE_REDEEM_GIFTCARD:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_REDEEM_GIFTCARD', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_REDEEM_GIFTCARD', $langId);
                break;
            case static::TYPE_WITHDRAW_REQUEST:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_WITHDRAW_REQUEST', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_WITHDRAW_REQUEST', $langId);
                break;
            case static::TYPE_TEACHER_APPROVAL:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_TEACHER_APPROVAL', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_TEACHER_APPROVAL', $langId);
                break;
            case static::TYPE_TEACHER_DECLINED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_TEACHER_DECLINED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_TEACHER_DECLINED', $langId);
                break;
            case static::TYPE_CHANGE_PASSWORD:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_CHANGE_PASSWORD', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_CHANGE_PASSWORD', $langId);
                break;
            case static::TYPE_ORDER_PAID:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_ORDER_PAID', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_ORDER_PAID', $langId);
                break;
            case static::TYPE_ORDER_CANCELLED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_ORDER_CANCELLED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_ORDER_CANCELLED', $langId);
                break;
            case static::TYPE_SUBSCRIPTION_CANCELLED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_RECURRING_LESSON_CANCELLED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_RECURRING_LESSON_CANCELLED', $langId);
                break;
            case static::TYPE_PACKAGE_CANCELLED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_PACKAGE_CANCELLED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_PACKAGE_CANCELLED', $langId);
                break;
            case static::TYPE_FORUM_QUE_PUB_TO_SUBSC_TAG_USER:
                $title = Label::getLabel('NOTIFI_TITLE_FORUM_QUE_PUB_TO_SUBSC_TAG_USER', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_FORUM_QUE_PUB_TO_SUBSC_TAG_USER_{que-title}_{auth-name}', $langId);
                break;
            case static::TYPE_FORUM_QUE_REPUB_TO_SUBSC_TAG_USER:
                $title = Label::getLabel('NOTIFI_TITLE_FORUM_QUE_REPUB_TO_SUBSC_TAG_USER', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_FORUM_QUE_REPUB_TO_SUBSC_TAG_USER_{que-title}_{auth-name}', $langId);
                break;
            case static::TYPE_FORUM_TAG_REQ_STATUS_UPDATE_TO_USER:
                $title = Label::getLabel('NOTIFI_TITLE_FORUM_TAG_REQ_STATUS_UPDATE_USER', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_FORUM_TAG_REQ_STATUS_UPDATE_USER_{tag-title}_{req-status}', $langId);
                break;
            case static::TYPE_FORUM_QUE_SPAM_REPORTED_TO_AUTHOR:
                $title = Label::getLabel('NOTIFI_TITLE_FORUM_QUE_REPORT_SPAM_TO_AUTHOR', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_FORUM_QUE_REPORT_SPAM_TO_AUTHOR_{que-title}', $langId);
                break;
            case static::TYPE_FORUM_QUE_SPAM_STATUS_UPDATE_TO_AUTHOR:
                $title = Label::getLabel('NOTIFI_TITLE_FORUM_QUE_SPAM_STATUS_UPDATE_TO_AUTHOR', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_FORUM_QUE_SPAM_STATUS_UPDATE_TO_AUTHOR_{que-title}_{status-txt}_{adm-comments}', $langId);
                break;
            case static::TYPE_FORUM_QUE_SPAM_STATUS_UPDATE_TO_REP_USER:
                $title = Label::getLabel('NOTIFI_TITLE_FORUM_QUE_SPAM_STATUS_UPDATE_TO_REP_USER', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_FORUM_QUE_SPAM_STATUS_UPDATE_TO_REP_USER_{que-title}_{status-txt}_{adm-comments}', $langId);
                break;
            case static::TYPE_FORUM_QUE_COMMENT_POSTED_TO_AUTHOR:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_FORUM_QUE_COMMENT_POSTED_TO_AUTHOR', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_FORUM_QUE_COMMENT_POSTED_TO_AUTHOR_{posted-by}_{que-title}', $langId);
                break;
            case static::TYPE_FORUM_QUE_COMMENT_ACCEPTED_TO_USER:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_FORUM_QUE_COMMENT_ACCEPTED_TO_USER', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_FORUM_QUE_COMMENT_ACCEPTED_TO_USER_{que-title}', $langId);
                break;
            case static::TYPE_NEW_MSG_RECEIVED:
                $title = Label::getLabel('NOTIFI_TITLE_NEW_MESSAGE_RECEIVED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_NEW_MESSAGE_RECEIVED', $langId);
                break;
            case static::TYPE_QUIZ_ATTACHED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_QUIZ_ATTACHED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_QUIZ_ATTACHED', $langId);
                break;
            case static::TYPE_QUIZ_REMOVED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_QUIZ_REMOVED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_QUIZ_REMOVED', $langId);
                break;
            case static::TYPE_REWARD_POINT_CREDIT:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_REWARD_POINT_CREDIT', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_REWARD_POINT_CREDIT', $langId);
                break;
            case static::TYPE_SIGNUP_COMMISSSION_CREDIT_TO_AFFILIATE:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_SIGNUP_COMMISSSION_CREDIT_TO_AFFILIATE', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_SIGNUP_COMMISSSION_{rewards}_CREDIT_TO_AFFILIATE_{message}', $langId);
                break;
            case static::TYPE_ORDER_COMMISSSION_CREDIT_TO_AFFILIATE:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_ORDER_COMMISSSION_CREDIT', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_ORDER_COMMISSSION_AMOUNT_CREDIT_{message}', $langId);
                break;
            case static::TYPE_SUB_PLAN_CANCELLED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_SUBSCRIPTION_PLAN_CANCELLED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_SUBSCRIPTION_PLAN_CANCELLED', $langId);
                break;
            case static::TYPE_SUB_PLAN_EXPIRED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_SUBSCRIPTION_PLAN_EXPIRED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_SUBSCRIPTION_PLAN_EXPIRED', $langId);
                break;
            case static::TYPE_SUB_PLAN_RENEWED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_SUBSCRIPTION_PLAN_RENEWED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_SUBSCRIPTION_PLAN_RENEWED', $langId);
                break;
            case static::TYPE_SUB_PLAN_INACTIVE:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_SUBSCRIPTION_PLAN_INACTIVE', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_SUBSCRIPTION_PLAN_INACTIVE', $langId);
                break;
            case static::TYPE_SUB_PLAN_RENEWAL_FAILED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_SUBSCRIPTION_PLAN_RENEWAL_FAILED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_SUBSCRIPTION_PLAN_RENEWAL_FAILED', $langId);
                break;
            case static::TYPE_SUB_PLAN_RENEWAL_REMINDER:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_SUBSCRIPTION_PLAN_RENEWAL_REMINDER', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_SUBSCRIPTION_PLAN_RENEWAL_REMINDER', $langId);
                break;
            case static::TYPE_SUB_PLAN_PURCHASED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_SUBSCRIPTION_PLAN_PURCHASED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_SUBSCRIPTION_PLAN_PURCHASED', $langId);
                break;
            case static::TYPE_RECURRING_LESSON_COMPLETED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_RECURRING_LESSON_COMPLETED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_RECURRING_LESSON_COMPLETED', $langId);
                break;
            case static::TYPE_RECURRING_LESSON_REMINDER:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_RECURRING_LESSON_RENEWAL_REMINDER', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_RECURRING_LESSON_RENEWAL_REMINDER', $langId);
                break;
            case static::TYPE_RECURRING_LESSON_RENEWAL_FAILED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_RECURRING_LESSON_RENEWAL_FAILED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_RECURRING_LESSON_RENEWAL_FAILED', $langId);
                break;
            case static::TYPE_RECURRING_LESSON_SUBJECT_INACTIVE:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_RECURRING_LESSON_SUBJECT_INACTIVE', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_RECURRING_LESSON_SUBJECT_INACTIVE', $langId);
                break;
            case static::TYPE_QUIZ_COMPLETED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_QUIZ_COMPLETED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_QUIZ_COMPLETED', $langId);
                break;
            case static::TYPE_QUIZ_EVALUATION_SUBMITTED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_QUIZ_EVALUATION_SUBMITTED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_QUIZ_EVALUATION_SUBMITTED', $langId);
                break;
            case static::TYPE_COURSE_EDIT_REQUEST_STATUS_UPDATE:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_COURSE_EDIT_REQUEST_STATUS_UPDATE', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_COURSE_EDIT_REQUEST_STATUS_UPDATE', $langId);
                break;
            case static::TYPE_COURSE_MODIFICATIONS_COMPLETED:
                $title = Label::getLabel('NOTIFI_TITLE_TYPE_COURSE_MODIFICATIONS_COMPLETED', $langId);
                $desc = Label::getLabel('NOTIFI_DESC_TYPE_COURSE_MODIFICATIONS_COMPLETED', $langId);
                break;
        }
        $this->title = str_replace(array_keys($vars), $vars, $title);
        $this->desc = str_replace(array_keys($vars), $vars, $desc);
    }

    /**
     * Read Notifications
     *
     * @param array $notificationIds
     * @return bool
     */
    public function markRead(array $notificationIds): bool
    {
        $notifiIds = array_filter(FatUtility::int($notificationIds));
        $query = 'UPDATE ' . static::DB_TBL . ' SET  notifi_read="' . date('Y-m-d H:i:s') .
            '"  WHERE notifi_id IN (' . implode(",", $notifiIds) . ') and notifi_user_id = ' . $this->userId;
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Read Notifications
     *
     * @param array $notificationIds
     * @return bool
     */
    public function markUnRead(array $notificationIds): bool
    {
        $notifiIds = array_filter(FatUtility::int($notificationIds));
        $query = 'UPDATE ' . static::DB_TBL . ' SET  notifi_read=null WHERE notifi_id IN (' .
            implode(",", $notifiIds) . ') and notifi_user_id = ' . $this->userId;
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Delete notifications
     *
     * @param array $notificationIds
     * @return bool
     */
    public function remove(array $notificationIds): bool
    {
        $notifiIds = array_unique(array_filter(FatUtility::int($notificationIds)));
        $query = 'DELETE FROM ' . static::DB_TBL . ' WHERE notifi_id IN (' .
            implode(",", $notifiIds) . ') and notifi_user_id = ' . $this->userId;
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Unread Count
     *
     * @param int $userType
     * @return int
     */
    public function getUnreadCount(int $userType = 0): int
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition("notifi_user_id", '=', $this->userId);
        if (!empty($userType)) {
            $srch->addCondition("notifi_user_type", 'IN', [0, $userType]);
        }
        $srch->addCondition("notifi_read", 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addFld('COUNT(notifi_id) as unread_count');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(100);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return FatUtility::int($row['unread_count'] ?? 0);
    }

    /**
     * Fetch Access Token for the Firebase Push Notifications
     */
    private function getAccessToken($config)
    {
        $client = new GoogleClient();
        $client->setAuthConfig($config);
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
        $client->refreshTokenWithAssertion();
        $authToken = $client->getAccessToken();
        return $authToken['access_token'];
    }
}
