<?php

/**
 * This class is used to handle User Verification
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Verification extends FatModel
{

    const DB_TBL = 'tbl_user_verifications';
    const DB_TBL_PREFIX = 'usrver_';
    const TYPE_EMAIL_VERIFICATION = 1;
    const TYPE_EMAIL_CHANGE = 2;

    /**
     * Initialize Verification
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Add Verification Token
     * 
     * @param string $token
     * @param int $userId
     * @param string $email
     * @param int $type
     * @return bool
     */
    public function addToken(string $token, int $userId, string $email, int $type): bool
    {
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues([
            'usrver_user_id' => $userId,
            'usrver_email' => trim($email),
            'usrver_token' => $token,
            'usrver_type' => $type,
            'usrver_expire' => date('Y-m-d H:i:s', strtotime('+1 week')),
            'usrver_created' => date('Y-m-d H:i:s'),
        ]);
        if (!$record->addNew()) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Remove Token
     * 
     * @param int $userId
     * @return bool
     */
    public function removeToken(int $userId): bool
    {
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL,
                        ['smt' => 'usrver_user_id = ?', 'vals' => [$userId]])) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Remove Expired Token
     * 
     * @return bool
     */
    public function removeExpiredToken(): bool
    {
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL,
                        ['smt' => 'usrver_expire < ?', 'vals' => [date('Y-m-d H:i:s')]])) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Verify Token
     * 
     * @param string $token
     * @return bool
     */
    public function verify(string $token): bool
    {
        $token = $this->getToken($token);
        if (empty($token)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();

        switch ($token['usrver_type']) {
            case static::TYPE_EMAIL_VERIFICATION:
                if (!$this->verifyAccount($token)) {
                    $db->rollbackTransaction();
                    return false;
                }
                break;
            case static::TYPE_EMAIL_CHANGE:
                if (!$this->changeEmail($token)) {
                    $db->rollbackTransaction();
                    return false;
                }
                break;
            default:
                $this->error = Label::getLabel('LBL_INVALID_REQUEST');
                return false;
        }

        if (!$this->removeToken($token['usrver_user_id'])) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Verify Account
     * 
     * @param array $token
     * @return bool
     */
    private function verifyAccount(array $token): bool
    {
        if (!empty($token['user_verified'])) {
            $this->error = Label::getLabel('LBL_USER_ACCOUNT_ALREADY_VERIFIED');
            return false;
        }
        $userId = $token['usrver_user_id'];
        $user = new User($userId);
        if (!$user->verifyAccount()) {
            $this->error = $user->getError();
            return false;
        }
        $userDetail = UserSetting::getSettings($userId, ['user_referred_by']);
       
        if($userDetail['user_referred_by'] > 0){
           if(!User::isAffilate($userDetail['user_referred_by'])){
                $record = new RewardPoint($userId);
                if (!$record->registerRewards()) {
                    $this->error = Label::getLabel('MSG_REWARDS_COULD_NOT_BE_SET');
                    return false;
                }
            }else{
               $record = new User($userId);
                if (!$record->settleAffiliateSignupCommission()) {
                    $this->error = Label::getLabel('MSG_AFFILIATE_COMMISSION_COULD_NOT_BE_SET');
                    return false;
                }
            }  
         }
       
        return true;
    }

    /**
     * Change Email
     * 
     * @param array $token
     * @return bool
     */
    private function changeEmail(array $token): bool
    {
        $user = new User($token['usrver_user_id']);
        if (!$user->changeEmail($token['usrver_email'])) {
            $this->error = $user->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Token Detail
     * 
     * @param string $token
     * @return null|array
     */
    public function getToken(string $token)
    {
        $srch = new SearchBase(static::DB_TBL, 'usrver');
        $srch->addMultipleFields(['usrver_user_id', 'usrver_email', 'usrver_type', 'user_email', 'user_verified']);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = usrver.usrver_user_id', 'user');
        $srch->addDirectCondition('user_deleted IS NULL');
        $srch->addCondition('usrver_token', '=', $token);
        $srch->addCondition('usrver_expire', '>=', date('Y-m-d H:i:s'));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

}
