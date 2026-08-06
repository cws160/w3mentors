<?php

/**
 * This class is used to handle Cookies
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class CookieConsent extends MyAppModel
{

    const DB_TBL = 'tbl_user_cookie_consent';
    const DB_TBL_PREFIX = 'usercc_';
    const COOKIE_NAME = 'CONF_USER_CONSENTS';
    const NECESSARY = 'necessary';
    const PREFERENCES = 'preferences';
    const STATISTICS = 'statistics';

    private $userId;

    /**
     * Initialize Cookies Consent
     * @param int $userId
     */
    public function __construct(int $userId = 0)
    {
        $this->userId = $userId;
    }

    /**
     * Get Search Object
     * 
     * @param bool $joinUser
     * @return SearchBase
     */
    public static function getSearchObject(bool $joinUser = true): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'usercc');
        if ($joinUser) {
            $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'usercc_user_id = user_id', 'user');
        }
        return $srch;
    }

    /**
     * Get Default Consent
     * 
     * @param string $key
     * @return string|array
     */
    public static function getDefaultConsent(string $key = null)
    {
        $arr = [
            self::NECESSARY => AppConstant::YES,
            self::PREFERENCES => AppConstant::YES,
            self::STATISTICS => AppConstant::YES,
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Update Setting
     * 
     * @param array $settings
     * @param type $setCookie
     * @return boolean
     */
    public function updateSetting(array $settings = [], $setCookie = true)
    {
        $settings = array_merge(self::getDefaultConsent(), $settings);
        $tableRecord = new TableRecord(self::DB_TBL);
        $fields = [
            'usercc_user_id' => $this->userId,
            'usercc_settings' => json_encode($settings),
            'usercc_added_on' => date('Y-m-d H:i:s'),
        ];
        $tableRecord->setFlds($fields);
        if ($tableRecord->addNew([], $fields) === false) {
            $this->error = $tableRecord->getError();
            return false;
        }
        if ($setCookie) {
            MyUtility::setCookieConsents($settings, true);
        }
        return true;
    }

    /**
     * Get Cookie Settings
     * 
     * @return string
     */
    public static function getSettings(int $userId): string
    {
        $srch = self::getSearchObject(true);
        $srch->addMultipleFields(['usercc_settings']);
        $srch->addCondition('usercc_user_id', '=', $userId);
        $sttings = FatApp::getDb()->fetch($srch->getResultSet());
        return $sttings['usercc_settings'] ?? '';
    }

}
