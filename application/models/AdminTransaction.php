<?php

/**
 * This class is used to record admin transactions
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminTransaction extends MyAppModel
{

    const DB_TBL = 'tbl_admin_transactions';
    const DB_TBL_PREFIX = 'admtxn_';
    /* Types */
    const TYPE_CREDIT = 1;
    const TYPE_DEBIT = 2;

    private $recordType;
    private $recordId;

    /**
     * Initialize Transaction
     * 
     * @param int $recordType
     * @param int $recordId
     */
    public function __construct(int $recordId, int $recordType)
    {
        $this->recordType = $recordType;
        $this->recordId = $recordId;
        parent::__construct(static::DB_TBL, 'admtxn_id', 0);
    }

    /**
     * Save Transaction
     * 
     * @param float $amount
     * @param string $comment
     * @return bool
     */
    public function store(float $amount, string $comment): bool
    {
        $transactionData = [
            'admtxn_record_type' => $this->recordType,
            'admtxn_record_id' => $this->recordId,
            'admtxn_amount' => $amount,
            'admtxn_comment' => $comment,
            'admtxn_datetime' => date('Y-m-d H:i:s')
        ];
        $this->assignValues($transactionData);
        return $this->addNew();
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
            AppConstant::LESSON => Label::getLabel('ADMTXN_LESSON'),
            AppConstant::GCLASS => Label::getLabel('ADMTXN_GROUP_CLASS'),
            AppConstant::COURSE => Label::getLabel('ADMTXN_COURSE'),
            AppConstant::SUBPLAN => Label::getLabel('ADMTXN_SUBSCRIPTION_PLAN'),
        ];
        return AppConstant::returArrValue($arr, $key);
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
            static::TYPE_CREDIT => Label::getLabel('LBL_Credit'),
            static::TYPE_DEBIT => Label::getLabel('LBL_Debit')
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

    public function logEarningTxn(float $earnings, string $comment = ''): bool
    {
        if ($earnings == 0) {
            return true;
        }
        $vars = [$this->recordId, static::getTypes($this->recordType)];
        $label = Label::getLabel('LBL_EARNINGS_ON_{RECORDTYPE}_ID_:_{RECORDID}');
        if($comment == '') {
            $comments = str_ireplace(['{RECORDID}', '{RECORDTYPE}'], $vars, $label);
        } else {
            $comments = $comment;
        }
        return $this->store($earnings, self::formatComments($comments));
    }

}
