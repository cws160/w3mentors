<?php

/**
 * User Email Change Request
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UserEmailChangeRequest extends MyAppModel
{

    const DB_TBL = 'tbl_user_email_change_request';
    const DB_TBL_PREFIX = 'uecreq_';

    /**
     * Initialize Email Change
     * 
     * @param int $requestID
     */
    public function __construct(int $requestID = 0)
    {
        parent::__construct(static::DB_TBL, 'uecreq_id', $requestID);
    }

    /**
     * Delete Old Link for User
     * 
     * @param int $userId
     * @return bool
     */
    public function deleteOldLinkforUser(int $userId): bool
    {
        if (!FatApp::getDb()->deleteRecords(static::DB_TBL,
                        ['smt' => 'uecreq_user_id = ?', 'vals' => [$userId]])) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

}
