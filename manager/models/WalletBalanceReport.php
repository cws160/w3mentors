<?php
/**
 * This class is used to handle Wallet Balance Reports
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class WalletBalanceReport extends FatModel
{
    /**
     * Initialize Wallet Balance Report
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
        $srch = new SearchBased(User::DB_TBL, 'user');
        $srch->joinTable(User::DB_TBL_SETTING, 'INNER JOIN', 'uset.user_id = user.user_id', 'uset');
        $srch->addDirectCondition('user.user_deleted IS NULL');
        $srch->addMultipleFields([
            'CONCAT(user.user_first_name, " ", user.user_last_name) as user_name',
            'uset.user_wallet_balance', ' user.user_is_teacher', 'user.user_is_affiliate',
            'uset.user_registered_as',
        ]);
        return $srch;
    }
}