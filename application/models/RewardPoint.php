<?php

/**
 * This class is used to handle Reward Point
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class RewardPoint extends MyAppModel
{

    const DB_TBL = 'tbl_reward_points';
    const DB_TBL_PREFIX = 'repnt_';
    /* Types */
    const TYPE_REGISTER = 1;
    const TYPE_PURCHASE = 2;
    const TYPE_POINTS_USED = 3;
    const TYPE_POINTS_REFUNDED = 4;
    const TYPE_POINTS_REDEEMED = 5;

    private $userId;

    /**
     * Initialize Transaction
     * 
     * @param int $userId
     */
    public function __construct(int $userId)
    {
        $this->userId = $userId;
        parent::__construct(static::DB_TBL, 'repnt_id', 0);
    }

    /**
     * Register reward
     * 
     * @return bool
     */
    public function registerRewards(): bool
    {

        if (empty(FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))) {
            return true;
        }
        if ($this->validateUser($this->userId)) {
            return true;
        }
        $user = User::getDetail($this->userId); 
        if(User::isAffilate($user['user_referred_by'])){
            return true;
        }
        if ($this->alreadyAvailed(static::TYPE_REGISTER, $user['user_referred_by'])) {
            return true;
        }
        
        $refById = FatUtility::int($user['user_referred_by']);
        if($user['user_is_affiliate'] == AppConstant::NO){

            $rewards = FatApp::getConfig('CONF_REFERENT_REGISTER_REWARDS');
            if ($rewards > 0) {
                $comment = Label::getLabel('LBL_REGISTER_REWARD_POINTS');
                if (!$this->credit($this->userId, $refById, static::TYPE_REGISTER, $rewards, $comment)) {
                    return false;
                }
                $notifi = new Notification($this->userId, Notification::TYPE_REWARD_POINT_CREDIT);
                $comment = Label::getLabel('LBL_REGISTRATION', $user['user_lang_id']);
                $vars = ['{rewards}' => $rewards, '{message}' => $comment];
                if (!$notifi->sendNotification($vars)) {
                    return false;
                }
            }
        }       
        
        $rewards = FatApp::getConfig('CONF_REFERRER_REGISTER_REWARDS');
        if ($rewards > 0) {
            
            $username = $user['user_first_name'] . ' ' . $user['user_last_name'];
            $comment = str_replace('{user}', $username, Label::getLabel('LBL_{user}_REGISTERED'));
            if (!$this->credit($refById, $this->userId, static::TYPE_REGISTER, $rewards, $comment)) {
                return false;
            }
            $notifi = new Notification($refById, Notification::TYPE_REWARD_POINT_CREDIT);
            $comment = str_replace('{user}', $username, Label::getLabel('LBL_REGISTRATION_BY_{user}', $user['user_lang_id']));
            $vars = ['{rewards}' => $rewards, '{message}' => $comment];
            if (!$notifi->sendNotification($vars)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Purchase Rewards
     * 
     * @return bool
     */
    public function purchaseRewards(): bool
    {
        if (empty(FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))) {
            return true;
        }
        if ($this->validateUser($this->userId)) {
            return true;
        }
        
        $user = User::getDetail($this->userId);

        if(User::isAffilate($user['user_referred_by'])){
            return true;
        }
        $refById = FatUtility::int($user['user_referred_by']);
        
        $rewards = FatApp::getConfig('CONF_REFERENT_PURCHASE_REWARDS');
        if ($rewards > 0) {
            if ($this->alreadyAvailed(static::TYPE_PURCHASE, $refById, User::LEARNER)) {
                return true;
            }
            $comment = Label::getLabel('LBL_FIRST_PURCHASE');
            if (!$this->credit($this->userId, $refById, static::TYPE_PURCHASE, $rewards, $comment)) {
                return false;
            }
            $notifi = new Notification($this->userId, Notification::TYPE_REWARD_POINT_CREDIT);
            $comment = Label::getLabel('LBL_FIRST_PURCHASE', $user['user_lang_id']);
            $vars = ['{rewards}' => $rewards, '{message}' => $comment];
            $notifi->sendNotification($vars);
        }
       
        $rewards = FatApp::getConfig('CONF_REFERRER_PURCHASE_REWARDS');
        if ($rewards > 0) {
            if ($this->alreadyAvailed(static::TYPE_PURCHASE, $refById, User::TEACHER)) {
                return true;
            }
            $username = $user['user_first_name'] . ' ' . $user['user_last_name'];
            $comment = str_replace('{user}', $username, Label::getLabel('LBL_PURCHASE_BY_{user}'));
            if (!$this->credit($refById, $this->userId, static::TYPE_PURCHASE, $rewards, $comment)) {
                return false;
            }
            $notifi = new Notification($refById, Notification::TYPE_REWARD_POINT_CREDIT);
            $comment = str_replace('{user}', $username, Label::getLabel('LBL_PURCHASE_BY_{user}', $user['user_lang_id']));
            $vars = ['{rewards}' => $rewards, '{message}' => $comment];
            $notifi->sendNotification($vars);
        }
        return true;
    }

    /**
     * Used Rewards
     * 
     * @param int $points
     * @return bool
     */
    public function usedRewards(int $orderId, int $points): bool
    {
        $comment = Label::getLabel('LBL_USED_FOR_ORDER_ID:_{orderid}');
        $comment = str_replace('{orderid}', Order::formatOrderId($orderId), $comment);
        return $this->debit($this->userId, 0, static::TYPE_POINTS_USED, $points, $comment);
    }

    /**
     * Refund Rewards
     * 
     * @param int $points
     * @return bool
     */
    public function refundRewards(int $orderId, int $points): bool
    {
        $comment = Label::getLabel('LBL_REFUND_FOR_ORDER_ID:_{orderid}');
        $comment = str_replace('{orderid}', Order::formatOrderId($orderId), $comment);
        return $this->credit($this->userId, 0, static::TYPE_POINTS_REFUNDED, $points, $comment);
    }

    /**
     * Validate User
     * 
     * @param int $userId
     * @return bool
     */
    private function validateUser(int $userId): bool
    {
        $srch = new SearchBase(User::DB_TBL, 'user');
        $srch->joinTable(UserSetting::DB_TBL, 'INNER JOIN', 'uset.user_id=user.user_id', 'uset');
        $srch->addCondition('user.user_id', '=', $userId);
        $srch->addCondition('user_active', '=', AppConstant::YES);
        $srch->addDirectCondition("((user_referred_by IS NOT NULL) AND (user_referred_by != '". AppConstant::NO. "'))");
        $srch->addDirectCondition('user_verified IS NOT NULL');
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->doNotCalculateRecords();
        $srch->addFld('COUNT(*) as total');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return (FatUtility::int($row['total']) == 0);
    }

    /**
     * Already Availed
     * @param int $type
     * @param int $userId
     * @return bool
     */
    private function alreadyAvailed(int $type, int $fromUserId, int $userType = User::LEARNER): bool
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('repnt_type', '=', $type);

        if ($type == static::TYPE_REGISTER) {
            $srch->addCondition('repnt_user_id', '=', $this->userId);
        } else {
            if ($userType == User::LEARNER) {
                $srch->addCondition('repnt_user_id', '=', $this->userId);
                $srch->addCondition('repnt_referrer_id', '=', $fromUserId);
            } else {
                $srch->addCondition('repnt_user_id', '=', $fromUserId);
                $srch->addCondition('repnt_referrer_id', '=', $this->userId);
            }
        }
        $srch->addFld('COUNT(*) as total');
        $srch->doNotCalculateRecords();
        $order = FatApp::getDb()->fetch($srch->getResultSet());
        return (FatUtility::int($order['total']) > 0);
    }

    /**
     * Credit Reward Points
     * @param int $userId
     * @param int $type
     * @param int $rewards
     * @param string $comment
     * @return bool
     */
    private function credit(int $userId, int $fromUserId, int $type, int $rewards, string $comment): bool
    {
        return $this->store($userId, $fromUserId, $type, abs($rewards), $comment);
    }

    /**
     * Debit Reward Points
     * @param int $userId
     * @param int $type
     * @param int $rewards
     * @param string $comment
     * @return bool
     */
    private function debit(int $userId, int $fromUserId, int $type, int $rewards, string $comment): bool
    {
        return $this->store($userId, $fromUserId, $type, -1 * abs($rewards), $comment);
    }

    /**
     * Save Reward Points
     * @param int $userId
     * @param int $type
     * @param int $rewards
     * @param string $comment
     * @return bool
     */
    private function store(int $userId, int $fromUserId, int $type, int $rewards, string $comment): bool
    {
        $comment = $this->formatComments($comment);
        $this->assignValues([
            'repnt_type' => $type,
            'repnt_points' => $rewards,
            'repnt_user_id' => $userId,
            'repnt_referrer_id' => $fromUserId,
            'repnt_comment' => $comment,
            'repnt_datetime' => date('Y-m-d H:i:s')
        ]);
        if (!$this->addNew()) {
            return false;
        }
        $sql = 'UPDATE ' . UserSetting::DB_TBL . ' SET user_reward_points = '
            . ' user_reward_points + ' . $rewards . ' WHERE user_id = ' . $userId;
        if (!FatApp::getDb()->query($sql)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Format Comments
     * 
     * @param string $comments
     * @return string
     */
    private function formatComments(string $comments): string
    {
        $comments = preg_replace('/<\/?a[^>]*>/', '', $comments);
        return CommonHelper::htmlEntitiesDecode($comments);
    }

    /**
     * Get Referrer Id
     * 
     * @param string $code
     * @return int $userId
     */
    public static function getReferrerId(string $code): int
    {
        $srch = new SearchBase(UserSetting::DB_TBL);
        $srch->addCondition('user_referral_code', '=', $code);
        $srch->doNotCalculateRecords();
        $srch->addFld('user_id');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return FatUtility::int($row['user_id'] ?? 0);
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
            static::TYPE_REGISTER => Label::getLabel('REPNT_REGISTER'),
            static::TYPE_PURCHASE => Label::getLabel('REPNT_PURCHASE'),
            static::TYPE_POINTS_USED => Label::getLabel('REPNT_POINTS_USED'),
            static::TYPE_POINTS_REFUNDED => Label::getLabel('REPNT_POINTS_REFUNDED'),
            static::TYPE_POINTS_REDEEMED => Label::getLabel('REPNT_POINTS_REDEEMED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Convert To Value
     * 
     * @param int $points
     * @return float $amount
     */
    public static function convertToValue(int $points): float
    {
        return round($points / FatApp::getConfig('CONF_REWARD_POINT_MULTIPLIER'), 8);
    }

    /**
     * Convert To Points
     * 
     * @param float $amount
     * @return int $points
     */
    public static function convertToPoints(float $amount): int
    {
        return floor($amount * FatApp::getConfig('CONF_REWARD_POINT_MULTIPLIER'));
    }

    /**
     * Get Refer Code
     * 
     * @param int $userId
     * @return string $code
     */
    public static function getReferCode(int $userId): string
    {
        $setting = UserSetting::getSettings($userId, ['user_referral_code']);
        if (!empty($setting['user_referral_code'])) {
            return $setting['user_referral_code'];
        }
        $code = uniqid();
        $record = new TableRecord(User::DB_TBL_SETTING);
        $record->setFldValue('user_referral_code', $code);
        $record->update(['smt' => 'user_id = ?', 'vals' => [$userId]]);
        return $code;
    }

    /**
     * Redeem Point To Wallet
     * 
     */
    public function redeemPointsToWallet()
    {
        $langId = User::getAttributesById($this->userId, 'user_lang_id');
        $points = User::getRewardBalance($this->userId);
        if ($points <= 0) {
            $this->error = Label::getLabel('LBL_YOU_HAVE_ALREADY_REDEEMED_POINTS');
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        $amount =  self::convertToValue($points);
        $comment = Label::getLabel('LBL_REDEEMED_TO_WALLET');
        if (!$this->debit($this->userId, 0, static::TYPE_POINTS_REDEEMED, $points, $comment)) {
            $this->error = $this->getError();
            $db->rollbackTransaction();
            return false;
        }
        $txn = new Transaction($this->userId, Transaction::TYPE_REWARD_POINTS_REDEEMED);
        
        $comment = Label::getLabel('LBL_{rewardpoints}_REWARD_POINTS_REDEEMED_TO_WALLET');
        $comment = str_replace('{rewardpoints}', $points, $comment);
        
        if (!$txn->credit($amount, $comment)) {
            $db->rollbackTransaction();
            $this->error = $txn->getError();
            return false;
        }
        $notifi = new Notification($this->userId, Notification::TYPE_WALLET_CREDIT);
        $notifiVar = [
            '{amount}' => MyUtility::formatMoney($amount),
            '{reason}' => strip_tags(Label::getLabel('LBL_REWARD_POINTS_REDEEMED_TO_WALLET', $langId))
        ];
        if (!$notifi->sendNotification($notifiVar)) {
            $db->rollbackTransaction();
            $this->error = $notifi->getError();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Validate Reward Points
     *
     * @param integer $points
     * @return bool
     */
    
    public function validate(int $points = 0): bool
    {
        if (empty(FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))) {
            $this->error = Label::getLabel('LBL_REWARDS_NOT_AVAILABLE');
            return false;
        }
        $minPoints = FatApp::getConfig('CONF_REWARD_POINT_MINIMUM_USE');
        if (User::getRewardBalance($this->userId) < $minPoints || $points < $minPoints) {
            $msg = Label::getLabel('LBL_MINIMUM_{rewards}_REWARDS_CAN_BE_APPLIED');
            $this->error = str_replace('{rewards}', $minPoints, $msg);
            return false;
        }
        return true;
    }
}
