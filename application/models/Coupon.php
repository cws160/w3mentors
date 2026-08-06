<?php

/**
 * This class is used to handle Coupon
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Coupon extends MyAppModel
{

    const DB_TBL = 'tbl_coupons';
    const DB_TBL_PREFIX = 'coupon_';
    const DB_TBL_LANG = 'tbl_coupons_lang';
    const DB_TBL_HISTORY = 'tbl_coupons_history';

    private $langId = 0;

    /**
     * Initialize Coupon
     * 
     * @param int $couponId
     * @param int $langId
     */
    public function __construct(int $couponId = 0, int $langId = 0)
    {
        $this->langId = $langId;
        parent::__construct(static::DB_TBL, 'coupon_id', $couponId);
    }

    /**
     * Save record
     * 
     * @return type
     */
    public function save()
    {
        if ($this->mainTableRecordId == 0) {
            $this->setFldValue('coupon_created', date('Y-m-d H:i:s'));
        } else {
            $this->setFldValue('coupon_updated', date('Y-m-d H:i:s'));
        }
        return parent::save();
    }

    /**
     * Get coupon by Code
     * 
     * @param string $code
     * @return type
     */
    public function getCoupon(string $code)
    {
        $srch = new SearchBase(static::DB_TBL, 'coupon');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'couponlang.couponlang_coupon_id '
                . ' = coupon.coupon_id AND couponlang.couponlang_lang_id = ' . $this->langId, 'couponlang');
        $srch->addMultipleFields([
            'coupon_id', 'coupon_code', 'coupon_min_order', 'coupon_max_discount', 'coupon_discount_type',
            'coupon_discount_value', 'coupon_user_uses', 'coupon_max_uses', 'coupon_used_uses',
            'IFNULL(coupon_title, coupon_identifier) as coupon_title', 'IFNULL(coupon_description, "") as coupon_description'
        ]);
        $srch->addCondition('coupon_used_uses', '<', 'mysql_func_coupon_max_uses', 'AND', true);
        $srch->addCondition('coupon_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('coupon_start_date', '<=', date('Y-m-d H:i:s'));
        $srch->addCondition('coupon_end_date', '>=', date('Y-m-d H:i:s'));
        $srch->addDirectCondition('BINARY coupon_code = "' . $code . '"');
        $srch->doNotCalculateRecords();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        $coupon = FatApp::getDb()->fetch($srch->getResultSet());
        if (empty($coupon)) {
            $this->error = Label::getLabel('LBL_INVALID_COUPON_CODE');
            return false;
        }
        return $coupon;
    }

    /**
     * Get Coupon List
     * 
     * @return array $coupons
     */
    public function getCouponList(): array
    {

        $srch = new SearchBase(static::DB_TBL, 'coupon');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'couponlang.couponlang_coupon_id = coupon.coupon_id AND couponlang.couponlang_lang_id = ' . $this->langId, 'couponlang');
        $srch->addMultipleFields(['coupon_id', 'coupon_code', 'IFNULL(coupon_title, coupon_identifier) as coupon_title', 'IFNULL(coupon_description, "") as coupon_description']);
        $srch->addCondition('coupon.coupon_used_uses', '<', 'mysql_func_coupon_max_uses', 'AND', true);
        $srch->addCondition('coupon.coupon_start_date', '<=', date('Y-m-d H:i:s'));
        $srch->addCondition('coupon.coupon_end_date', '>=', date('Y-m-d H:i:s'));
        $srch->addCondition('coupon.coupon_active', '=', AppConstant::ACTIVE);
        $srch->doNotCalculateRecords();
        $srch->setPageNumber(1);
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'coupon_id');
    }

    /**
     * Validate Coupon
     * 
     * @param string $code
     * @param float $amount
     * @param int $userId
     * @return type
     */
    public function validateCoupon(string $code, float $amount, int $userId)
    {
        if (!$coupon = $this->getCoupon($code)) {
            return false;
        }
        /* Minimum order value */
        if ($amount < $coupon['coupon_min_order']) {
            $error = Label::getLabel('LBL_REQUIRED_MINIMUM_ORDER_AMOUNT_{value}');
            $couponMinOrder = MyUtility::formatMoney($coupon['coupon_min_order']);
            $this->error = str_replace('{value}', $couponMinOrder, $error);
            return false;
        }
        /* Per user uses */
        $perUserUses = $this->getPerUserUses($coupon['coupon_id'], $userId);
        if ($coupon['coupon_user_uses'] <= $perUserUses) {
            $this->error = Label::getLabel('LBL_MAXIMUM_USES_REACHED');
            return false;
        }
        /* Flat/Percent discount */
        if ($coupon['coupon_discount_type'] == AppConstant::FLAT_VALUE) {
            $coupon['coupon_discount'] = $coupon['coupon_discount_value'];
        } elseif ($coupon['coupon_discount_type'] == AppConstant::PERCENTAGE) {
            $coupon['coupon_discount'] = $amount * $coupon['coupon_discount_value'] / 100;
            /* Maximum discount */
            if ($coupon['coupon_discount'] > $coupon['coupon_max_discount']) {
                $coupon['coupon_discount'] = $coupon['coupon_max_discount'];
            }
        }
        $coupon['coupon_discount'] = min($coupon['coupon_discount'], $amount);
        $coupon['coupon_discount'] = round(abs($coupon['coupon_discount']), 2);
        return $coupon;
    }

    /**
     * Increase used count
     * 
     * @param int $count
     * @return bool
     */
    public function increaseUsedCount(int $count = 1): bool
    {
        $query = "UPDATE " . static::DB_TBL . " SET `coupon_used_uses` = `coupon_used_uses` + " .
                $count . " WHERE `coupon_id` = " . $this->getMainTableRecordId();
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Increase Lesson
     * 
     * @param int $teacherId
     * @param int $lessons
     * @return bool
     */
    public function decreaseUsedCount(int $couhisId): bool
    {
        $query = "UPDATE " . static::DB_TBL . " SET `coupon_used_uses` = `coupon_used_uses` - 1 " .
                " WHERE `coupon_used_uses` > 0 and `coupon_id` = " . $this->getMainTableRecordId();
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        $query = "UPDATE " . static::DB_TBL_HISTORY . " SET couhis_released = '" . date('Y-m-d H:i:s') . "' WHERE couhis_id   = " . $couhisId;
        if (!FatApp::getDb()->query($query)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Per User Uses
     * 
     * @param int $couponId
     * @param int $userId
     * @return int
     */
    private function getPerUserUses(int $couponId, int $userId): int
    {
        $srch = new SearchBase(static::DB_TBL_HISTORY, 'couhis');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = couhis.couhis_order_id', 'orders');
        $srch->addDirectCondition('couhis.couhis_released IS NULL');
        $srch->addCondition('couhis.couhis_coupon_id', '=', $couponId);
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->addDirectCondition('((orders.order_payment_status = ' . Order::ISPAID . ') OR (orders.order_payment_status = ' .
                Order::UNPAID . ' AND orders.order_status = ' . Order::STATUS_INPROCESS . '))');
        $srch->addFld('COUNT(*) AS uses');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet())['uses'];
    }

    /**
     * Get History Search Object
     * 
     * @return SearchBase
     */
    public static function getHistorySearchObject(): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL_HISTORY, 'couhis');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = couhis.couhis_order_id', 'orders');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'users.user_id = orders.order_user_id', 'users');
        return $srch;
    }

}
