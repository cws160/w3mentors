<?php

/**
 * This class is used to handle Payment Methods
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class PaymentMethod extends MyAppModel
{

    const DB_TBL = 'tbl_payment_methods';
    const DB_TBL_PREFIX = 'pmethod_';
    /* Method Types */
    const TYPE_PAYIN = 1;
    const TYPE_PAYOUT = 2;
    const BANK_TRANSFER = 'BankTransferPay';

    /**
     * Initialize PaymentMethod
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'pmethod_id', $id);
        $this->objMainTableRecord->setSensitiveFields(['pmethod_code', 'pmethod_type']);
    }

    /**
     * Get Type Array
     * 
     * @param int $key
     * @return array
     */
    public static function getTypeArray(int $key = null): array
    {
        $arr = [
            self::TYPE_PAYIN => Label::getLabel('LBL_Payin'),
            self::TYPE_PAYOUT => Label::getLabel('LBL_Payout')
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Search Object
     * 
     * @param bool $isActive
     * @return SearchBase
     */
    public static function getSearchObject(bool $isActive = true)
    {
        $srch = new SearchBase(static::DB_TBL);
        if ($isActive == true) {
            $srch->addCondition('pmethod_active', '=', AppConstant::ACTIVE);
        }
        $srch->addOrder('pmethod_active', 'DESC');
        $srch->addOrder('pmethod_order', 'ASC');
        return $srch;
    }

    /**
     * Get By Code
     * 
     * @param string $code
     * @return null|array
     */
    public static function getByCode(string $code)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('pmethod_code', '=', $code);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet()) ?? false;
    }

    /**
     * Get All Payment Methods
     * 
     * @return array
     */
    public static function getAll(): array
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addOrder('pmethod_active', 'DESC');
        $srch->addOrder('pmethod_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Get Payins
     * 
     * @param bool $withWallet
     * @param bool $active
     * @return array
     */
    public static function getPayins(bool $withWallet = true, bool $active = true): array
    {
        $srch = new SearchBase(static::DB_TBL, 'pmethod');
        $srch->addMultipleFields(['pmethod_id', 'pmethod_code']);
        $srch->addCondition('pmethod_type', '=', static::TYPE_PAYIN);
        if (!$withWallet) {
            $srch->addCondition('pmethod_code', '!=', WalletPay::KEY);
        }
        if ($active) {
            $srch->addCondition('pmethod_active', '=', AppConstant::YES);
        }
        $srch->addOrder('pmethod_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $arr = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        foreach ($arr as $key => $value) {
            $arr[$key] = Label::getLabel('LBL_' . $value);
        }
        return $arr;
    }

    /**
     * Get Payouts
     * 
     * @return array
     */
    public static function getPayouts(): array
    {
        $srch = new SearchBase(static::DB_TBL, 'pm');
        $srch->addMultipleFields(['pmethod_id', 'pmethod_code', 'pmethod_fees']);
        $srch->addCondition('pmethod_active', '=', AppConstant::YES);
        $srch->addCondition('pmethod_type', '=', self::TYPE_PAYOUT);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'pmethod_code');
    }

    public function getTransactionFee(): array
    {
        $srch = new SearchBase(static::DB_TBL, 'pmethod');
        $srch->addMultipleFields(['pmethod_id', 'pmethod_code', 'pmethod_fees']);
        $srch->addCondition('pmethod_id', '=', $this->mainTableRecordId);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $arr = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($arr['pmethod_fees'])) {
            return json_decode($arr['pmethod_fees'], true);
        }
        return [];
    }

}
