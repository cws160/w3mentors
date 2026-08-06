<?php
/**
 * This class is used to handle Teacher Payout Stats
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class PayoutsReport extends FatModel
{
    /**
     * Initialize Payouts Report
     * 
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get Search Object
     * 
     * @param array $post
     * @return SearchBased
     */
    public static function getSearchObject(): SearchBased
    {
        $srch = new SearchBased(Transaction::DB_TBL, 'usrtxn');
        $srch->addCondition('usrtxn.usrtxn_type', '=', Transaction::TYPE_TEACHER_PAYMENT);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'usrtxn.usrtxn_user_id = user.user_id', 'user');
        $srch->addMultipleFields([
            'SUM(usrtxn.usrtxn_amount) as total_amount',
            'usrtxn.usrtxn_user_id as user_id',
            'usrtxn.usrtxn_datetime',
            'CONCAT(user.user_first_name, " ", user.user_last_name) as user_name',
        ]);
        return $srch;
    }
}