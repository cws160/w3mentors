<?php

/**
 * This class is used for Forum Tags
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumTagRequest extends MyAppModel
{

    private $reqId;
    private $userId;
    private $langId;

    const DB_TBL = 'tbl_forum_tag_requests';
    const DB_TBL_PREFIX = 'ftagreq_';
    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;

    /**
     * Initialize ForumTag Class
     *
     * @param int $reqId
     */
    public function __construct(int $userId, int $langId, int $reqId = 0)
    {
        parent::__construct(static::DB_TBL, 'ftagreq_id', $reqId);
        $this->userId = $userId;
        $this->langId = $langId;
        $this->reqId = $reqId;
    }

    public function getDetail(array $attrs = []): array
    {
        $data = ForumTagRequest::getAttributesById($this->mainTableRecordId, $attrs);
        if (false === $data) {
            return [];
        }
        return $data;
    }

    public function validateRequest($keyword): bool
    {
        if (1 > mb_strlen($keyword)) {
            $this->error = Label::getLabel('ERR_Forum_tag_request_invalid');
            return false;
        }
        $reqtestedTag = $this->checkInRequests($keyword);
        if (!empty($reqtestedTag)) {
            if (ForumTagRequest::STATUS_APPROVED == $reqtestedTag['ftagreq_status']) {
                $this->error = Label::getLabel('ERR_Forum_tag_request_already_sent_and_approved');
                return false;
            }
            /* if someone sent an approval request already and in pending approval */
            if (ForumTagRequest::STATUS_PENDING == $reqtestedTag['ftagreq_status'] && $this->userId != $reqtestedTag['ftagreq_user_id']
            ) {
                $this->error = Label::getLabel('ERR_Forum_tag_request_already_sent_and_approval_pending');
                return false;
            }
            /* if Logged user sent an approval request already and in pending approval */
            if ($this->reqId == 0 && ForumTagRequest::STATUS_PENDING == $reqtestedTag['ftagreq_status'] && $this->userId == $reqtestedTag['ftagreq_user_id']
            ) {
                $this->error = Label::getLabel('ERR_Forum_tag_request_already_sent_by_you_and_approval_pending');
                return false;
            }
            if ($this->reqId != $reqtestedTag['ftagreq_id'] && $this->userId == $reqtestedTag['ftagreq_user_id']) {
                $this->error = Label::getLabel('ERR_Forum_tag_request_already_sent_by_you');
                return false;
            }
            if (ForumTagRequest::STATUS_REJECTED == $reqtestedTag['ftagreq_status']) {
                $this->error = Label::getLabel('ERR_Forum_tag_request_already_sent_and_rejected');
                return false;
            }
        }
        if (0 < $this->reqId) {
            $record = ForumTagRequest::getAttributesById($this->reqId);
            if (false === $record) {
                $this->error = Label::getLabel('ERR_Invalid_Request');
                return false;
            }
            if (ForumTagRequest::STATUS_APPROVED == $record['ftagreq_status']) {
                $this->error = Label::getLabel('ERR_Your_Forum_tag_request_already_approved');
                return false;
            }
            if (ForumTagRequest::STATUS_REJECTED == $record['ftagreq_status']) {
                $this->error = Label::getLabel('ERR_Your_Forum_tag_request_already_rejected');
                return false;
            }
            if ($this->userId !== FatUtility::int($record['ftagreq_user_id'])) {
                $this->error = Label::getLabel('ERR_Invalid_Request');
                return false;
            }
        }
        return true;
    }

    public function checkInRequests(string $keyword): array
    {
        if (1 > $this->langId) {
            FatUtility::dieJsonError(Label::getLabel('ERR_Forum_tag_request_Invalid_language_selected'));
        }
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('ftagreq_name', '=', $keyword);
        $srch->addCondition('ftagreq_language_id', '=', $this->langId);
        $srch->addMultipleFields(['ftagreq_id', 'ftagreq_name', 'ftagreq_status', 'ftagreq_user_id']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet()) ?? [];
    }

    public function saveRequest(string $name): bool
    {
        $data = [
            'ftagreq_user_id' => $this->userId,
            'ftagreq_language_id' => $this->langId,
            'ftagreq_name' => $name,
            'ftagreq_status' => static::STATUS_PENDING
        ];
        if ($this->mainTableRecordId == 0) {
            $this->setFldValue('ftagreq_added_on', date('Y-m-d H:i:s'));
        }
        $this->assignValues($data);
        if (!parent::save()) {
            $this->error = $this->getError();
            return false;
        }
        return true;
    }

    public static function getStatusArray(int $key = null)
    {
        $arr = [
            static::STATUS_PENDING => Label::getLabel('LBL_Pending'),
            static::STATUS_APPROVED => Label::getLabel('LBL_Approved'),
            static::STATUS_REJECTED => Label::getLabel('LBL_Rejected'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public static function getAllowedStatusArray(): array
    {
        return [
            static::STATUS_APPROVED => Label::getLabel('LBL_Approved'),
            static::STATUS_REJECTED => Label::getLabel('LBL_Rejected'),
        ];
    }

    public function sendRequestNotifications(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications()) {
            return true;
        }
        $this->sendRequestEmail($data);
        return true;
    }

    public function sendRequestEmail(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        $mail = new FatMailer(MyUtility::getSiteLangId(), 'new_question_tag_request_to_admin');
        $mail->setVariables([
            '{user_full_name}' => $data['user_full_name'],
            '{tag_name}' => $data['tag_name'],
        ]);
        $mail->sendMail([FatApp::getConfig('CONF_SITE_OWNER_EMAIL')]);
        return true;
    }

    public function sendStatusUpdateNotifications(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications()) {
            return true;
        }
        $byUser = User::getAttributesById($data['user_id'], ['user_first_name', 'user_last_name', 'user_lang_id', 'user_email', 'user_is_teacher']);
        $data['user_full_name'] = $byUser['user_first_name'] . ' ' . $byUser['user_last_name'] ?? '';
        $data['user_lang_id'] = $byUser['user_lang_id'];
        $data['user_email'] = $byUser['user_email'];
        $this->sendStatusUpdateEmail($data);
        $data['user_type'] = (1 == $byUser['user_is_teacher'] ) ? User::TEACHER : User::LEARNER;
        $this->sendStatusUpdateSysNotification($data);
        return true;
    }

    public function sendStatusUpdateSysNotification(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('SN')) {
            return true;
        }
        $notify = new Notification($data['user_id'], Notification::TYPE_FORUM_TAG_REQ_STATUS_UPDATE_TO_USER);
        $staus = $notify->sendNotification([
            '{tag-title}' => $data['tag_name'],
            '{req-status}' => $data['request_status'],
                ], $data['user_type']);
        return true;
    }

    public function sendStatusUpdateEmail(array $data): bool
    {
        if (true !== ForumUtility::canSendNotifications('EM')) {
            return true;
        }
        $mail = new FatMailer($data['user_lang_id'], 'tag_request_status_update_to_user');
        $mail->setVariables([
            '{user_full_name}' => $data['user_full_name'],
            '{tag_name}' => $data['tag_name'],
            '{request_status}' => $data['request_status'],
        ]);
        $mail->sendMail([$data['user_email']]);
        return true;
    }

}
