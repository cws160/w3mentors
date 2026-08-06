<?php

/**
 * This class is used to handle Transaction
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Transaction extends MyAppModel
{

    const DB_TBL = 'tbl_user_transactions';
    const DB_TBL_PREFIX = 'usrtxn_';
    /* TXN Types */
    const TYPE_LESSON_ORDERED = 1;
    const TYPE_SUBSCR_ORDERED = 2;
    const TYPE_GCLASS_ORDERED = 3;
    const TYPE_PACKAG_ORDERED = 4;
    const TYPE_COURSE_ORDERED = 5;
    const TYPE_WALLET_ORDERED = 6;
    const TYPE_GFTCRD_ORDERED = 7;
    const TYPE_LEARNER_REFUND = 8;
    const TYPE_TEACHER_PAYMENT = 9;
    const TYPE_MONEY_WITHDRAW = 10;
    const TYPE_MONEY_DEPOSIT = 11;
    const TYPE_GFCARD_REDEEM = 12;
    const TYPE_SUPPORT_DEBIT = 13;
    const TYPE_SUPPORT_CREDIT = 14;
    const TYPE_REWARD_POINTS_REDEEMED = 15;
    const TYPE_REFERRAL_ORDER_COMMISSION = 16;
    const TYPE_REFERRAL_SIGNUP_COMMISSION = 17;
    const TYPE_SUBPLAN_ORDERED = 18;
    const TYPE_SUBPLAN_REFUND = 19;

    private $userId;
    private $txnType;

    const CREDIT_TYPE = 1;
    const DEBIT_TYPE = 2;

    /**
     * Initialize Transaction
     * 
     * @param int $userId
     * @param int $txnType
     */
    public function __construct(int $userId, int $txnType = 0)
    {
        $this->userId = $userId;
        $this->txnType = $txnType;
        parent::__construct(static::DB_TBL, 'usrtxn_id', 0);
    }

    /**
     * Wallet Credit
     * 
     * @param float $amount
     * @param string $comment
     * @return bool
     */
    public function credit(float $amount, string $comment): bool
    {
        return $this->store(abs($amount), $comment);
    }

    /**
     * Wallet Debit 
     * 
     * @param float $amount
     * @param string $comment
     * @return bool
     */
    public function debit(float $amount, string $comment): bool
    {
        return $this->store(FatUtility::float(-1 * abs($amount)), $comment);
    }

    /**
     * Save Transaction
     * 
     * @param float $amount
     * @param string $comment
     * @return bool
     */
    private function store(float $amount, string $comment): bool
    {
        $transactionData = [
            'usrtxn_type' => $this->txnType,
            'usrtxn_user_id' => $this->userId,
            'usrtxn_amount' => $amount,
            'usrtxn_comment' => $comment,
            'usrtxn_datetime' => date('Y-m-d H:i:s')
        ];
        $this->assignValues($transactionData);
        if (!$this->addNew()) {
            return false;
        }
        $sql = 'UPDATE ' . UserSetting::DB_TBL .
                ' SET user_wallet_balance = user_wallet_balance + ' .
                $amount . ' WHERE user_id = ' . $this->userId;
        if (!FatApp::getDb()->query($sql)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Get user by  txnId
     * 
     * @param int $txnId
     * @return null|array
     */
    public static function getUserByTxnId(int $txnId)
    {
        $srch = new SearchBase(static::DB_TBL, 'usrtxn');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = usrtxn.usrtxn_user_id', 'user');
        $srch->joinTable(UserSetting::DB_TBL, 'LEFT JOIN', 'usrset.user_id = user.user_id', 'usrset');
        $srch->addMultipleFields(['usrtxn_amount', 'usrtxn_comment', 'user_first_name', 'user_last_name', 'user_lang_id', 'user_email']);
        $srch->addCondition('usrtxn.usrtxn_id', '=', $txnId);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Send TXN Email
     * 
     * @param int $txnId
     * @return bool
     */
    public function sendEmail(): bool
    {
        $txnId = $this->getMainTableRecordId();
        $txn = static::getUserByTxnId($txnId);
        if (empty($txn)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $langId = FatUtility::int($txn['user_lang_id']);
        if (0 >= $langId) {
            $langId = MyUtility::getSiteLangId();
        }
        $txnTypeLabel = Label::getLabel('LBL_CREDITED', $langId);
        if ($txn["usrtxn_amount"] < 0) {
            $txnTypeLabel = Label::getLabel('LBL_DEBITED', $langId);
        }
        $vars = [
            '{user_first_name}' => $txn["user_first_name"],
            '{user_last_name}' => $txn["user_last_name"],
            '{user_full_name}' => $txn["user_first_name"] . ' ' . $txn["user_last_name"],
            '{txn_id}' => Transaction::formatTxnId($txnId),
            '{txn_type}' => $txnTypeLabel,
            '{txn_amount}' => MyUtility::formatMoney($txn['usrtxn_amount']),
            '{txn_comments}' => nl2br($txn["usrtxn_comment"]),
        ];
        $mail = new FatMailer($langId, 'account_credited_debited');
        $mail->setVariables($vars);
        if (!$mail->sendMail([$txn["user_email"]])) {
            $this->error = $mail->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Types
     * 
     * @param int $key
     * @return string|array
     */
    public static function getTypes(int $key = null)
    {
        $arr = [
            static::TYPE_LESSON_ORDERED => Label::getLabel('TXN_LESSON_ORDERED'),
            static::TYPE_SUBSCR_ORDERED => Label::getLabel('TXN_SUBSCR_ORDERED'),
            static::TYPE_GCLASS_ORDERED => Label::getLabel('TXN_GCLASS_ORDERED'),
            static::TYPE_PACKAG_ORDERED => Label::getLabel('TXN_PACKAG_ORDERED'),
            static::TYPE_COURSE_ORDERED => Label::getLabel('TXN_COURSE_ORDERED'),
            static::TYPE_WALLET_ORDERED => Label::getLabel('TXN_WALLET_ORDERED'),
            static::TYPE_GFTCRD_ORDERED => Label::getLabel('TXN_GFTCRD_ORDERED'),
            static::TYPE_MONEY_WITHDRAW => Label::getLabel('TXN_MONEY_WITHDRAW'),
            static::TYPE_MONEY_DEPOSIT => Label::getLabel('TXN_MONEY_DEPOSIT'),
            static::TYPE_GFCARD_REDEEM => Label::getLabel('TXN_GIFTCARD_REDEEM'),
            static::TYPE_TEACHER_PAYMENT => Label::getLabel('TXN_TEACHER_PAYMENT'),
            static::TYPE_LEARNER_REFUND => Label::getLabel('TXN_STUDENT_REFUND'),
            static::TYPE_SUPPORT_CREDIT => Label::getLabel('TXN_SUPPORT_CREDIT'),
            static::TYPE_SUPPORT_DEBIT => Label::getLabel('TXN_SUPPORT_DEBIT'),
            static::TYPE_REWARD_POINTS_REDEEMED => Label::getLabel('TXN_REWARD_POINTS_REDEEMED'),
            static::TYPE_REFERRAL_SIGNUP_COMMISSION => Label::getLabel('TXN_REFERRAL_SIGNUP_COMMISSION'),
            static::TYPE_REFERRAL_ORDER_COMMISSION => Label::getLabel('TXN_REFERRAL_ORDER_COMMISSION'),
            static::TYPE_SUBPLAN_REFUND => Label::getLabel('TXN_SUBSCRIPTION_PLAN_REFUND'),
            static::TYPE_SUBPLAN_ORDERED => Label::getLabel('TXN_SUBSCRIPTION_PLAN_ORDERED')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Format TXN Id
     * 
     * @param int $txnId
     * @return string
     */
    public static function formatTxnId(int $txnId): string
    {
        return "TXN" . "-" . str_pad($txnId, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Get Credit Debit Types
     * 
     * @param int $key
     * @return string|array
     */
    public static function getCreditDebitTypes(int $key = null)
    {
        $arr = [
            static::CREDIT_TYPE => Label::getLabel('LBL_Credit'),
            static::DEBIT_TYPE => Label::getLabel('LBL_Debit')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Format Comments
     * 
     * @param string $comments
     * @return string
     */
    public static function formatComments(string $comments): string
    {
        $comments = preg_replace('/<\/?a[^>]*>/', '', $comments);
        return CommonHelper::htmlEntitiesDecode($comments);
    }

}
