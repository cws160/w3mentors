<?php

/**
 * This class is used to handle Withdraw Request
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class WithdrawRequest extends MyAppModel
{

    const DB_TBL = 'tbl_user_withdrawal_requests';
    const DB_TBL_PREFIX = 'withdrawal_';
    const STATUS_PENDING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_DECLINED = 3;
    const STATUS_PAYOUT_SENT = 4;
    const STATUS_PAYOUT_FAILED = 5;

    /**
     * Initialize Withdraw Request
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'withdrawal_id', $id);
    }

    /**
     * Get Statuses
     * 
     * @param int $key
     * @return string|array
     */
    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::STATUS_PENDING => Label::getLabel('LBL_PENDING'),
            static::STATUS_COMPLETED => Label::getLabel('LBL_COMPLETED'),
            static::STATUS_DECLINED => Label::getLabel('LBL_DECLINED'),
            static::STATUS_PAYOUT_SENT => Label::getLabel('LBL_PAYOUT_SENT'),
            static::STATUS_PAYOUT_FAILED => Label::getLabel('LBL_PAYOUT_FAILED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get user by withdrawlRequestId
     * 
     * @param int $withdrawlRequestId
     * @return null|array
     */
    public static function getUserByWithdrawlRequestId(int $withdrawlRequestId)
    {
        $srch = new SearchBase(static::DB_TBL, 'withdrawal');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = withdrawal.withdrawal_user_id', 'user');
        $srch->addMultipleFields([
            'withdrawal_amount', 'withdrawal_comments', 'withdrawal_status', 'user_id', 'withdrawal_request_date',
            'user_first_name', 'user_last_name', 'user_lang_id', 'user_email', 'user_timezone'
        ]);
        $srch->addCondition('withdrawal.withdrawal_id', '=', $withdrawlRequestId);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Format Request Number
     * 
     * @param int $txnId
     * @return string
     */
    public static function formatRequestNumber(int $txnId): string
    {
        $newValue = str_pad($txnId, 7, '0', STR_PAD_LEFT);
        $newValue = "#" . $newValue;
        return $newValue;
    }

    /**
     * Update Status
     * 
     * @param array $data
     * @return bool
     */
    public function updateStatus(array $data): bool
    {
        $this->assignValues($data);
        if (!$this->save()) {
            return false;
        }
        if ($data['withdrawal_status'] == WithdrawRequest::STATUS_COMPLETED) {
            $txn = new Transaction($data['withdrawal_user_id'], Transaction::TYPE_MONEY_WITHDRAW);
            $txnfee = MyUtility::formatMoney($data['withdrawal_transaction_fee']);
            $comment = str_replace('{txnfee}', $txnfee, Label::getLabel('LBL_PAYOUT_SENT_&_TXN_CHARGE_{txnfee}'));
            if (!$txn->debit($data['withdrawal_amount'], $comment)) {
                $this->error = $txn->getError();
                return false;
            }
        }
        if (
                $data['withdrawal_status'] == WithdrawRequest::STATUS_COMPLETED ||
                $data['withdrawal_status'] == WithdrawRequest::STATUS_DECLINED
        ) {
            $this->sendRequestUpdateEmail();
        }
        return true;
    }

    /**
     * Send Withdraw Request Declined Email
     * 
     * @param int $withdrawlRequestId
     * @return bool
     */
    public function sendRequestUpdateEmail(): bool
    {
        $data = static::getUserByWithdrawlRequestId($this->getMainTableRecordId());
        if (empty($data)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $status = Label::getLabel('LBL_APPROVED', $data['user_lang_id']);
        $template = 'approved_withdrawal_request_mail_to_user';
        if ($data['withdrawal_status'] == WithdrawRequest::STATUS_DECLINED) {
            $status = Label::getLabel('LBL_DECLINED', $data['user_lang_id']);
            $template = 'declined_withdrawal_request_mail_to_user';
        }
        $data['withdrawal_request_date'] = MyDate::convert($data['withdrawal_request_date'], $data['user_timezone']);
        $notifiVar = ['{status}' => $status];
        $notifi = new Notification($data['user_id'], Notification::TYPE_WITHDRAW_REQUEST);
        $notifi->sendNotification($notifiVar);
        $vars = [
            '{user_first_name}' => $data["user_first_name"],
            '{user_last_name}' => $data["user_last_name"],
            '{request_date}' => MyDate::showDate($data['withdrawal_request_date'], true, $data['user_lang_id']),
            '{withdrawal_amount}' => MyUtility::formatMoney($data['withdrawal_amount']),
            '{withdrawal_comment}' => nl2br($data["withdrawal_comments"]),
        ];
        $mail = new FatMailer($data['user_lang_id'], $template);
        $mail->setVariables($vars);
        return $mail->sendMail([$data["user_email"]]);
    }

}
