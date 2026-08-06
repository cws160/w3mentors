<?php

/**
 * This class is used to handle User Teach Language
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UserTeachLanguage extends MyAppModel
{

    const DB_TBL = 'tbl_user_teach_languages';
    const DB_TBL_PREFIX = 'utlang_';

    protected $userId;
    protected $slot;

    /**
     * Initialize User Teach Language
     * 
     * @param int $userId
     * @param int $id
     */
    public function __construct(int $userId = 0, int $id = 0)
    {
        $this->userId = $userId;
        parent::__construct(static::DB_TBL, 'utlang_id', $id);
    }

    /**
     * Save Teach Lang
     * 
     * @param int $teachLangId
     * @return bool
     */
    public function saveTeachLang(int $teachLangId): bool
    {
        if (empty($this->userId)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $data = [
            'utlang_tlang_id' => $teachLangId,
            'utlang_user_id' => $this->userId
        ];
        $this->assignValues($data);
        if (!$this->addNew([], $data)) {
            return false;
        }
        if(!$this->updateUsrRecordCount()){
            return false;
        }
        return true;
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @return SearchBase
     */
    public static function getSearchObject(int $langId): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'utlang');
        $srch->joinTable(TeachLanguage::DB_TBL, 'INNER JOIN', 'tlang.tlang_id = utlang.utlang_tlang_id', 'tlang');
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id =' . $langId, 'tlanglang');
        return $srch;
    }

    /**
     * Get Teach Languages
     * 
     * @param int $langId
     * @return bool|array
     */
    public function getTeachLangs(int $langId)
    {
        $srch = static::getSearchObject($langId);
        $srch->addMultipleFields(['IFNULL(tlang_name, tlang_identifier) as tlang_name', 'tlang_id']);
        $srch->addCondition('utlang.utlang_user_id', '=', $this->userId);
        $srch->addCondition('utlang.utlang_price', '>', 0);
        $srch->addGroupBy('tlang.tlang_id');
        $srch->doNotCalculateRecords();
        $langs = FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
        if (empty($langs)) {
            $this->error = Label::getLabel('LBL_TEACHER_DOES_NOT_HAVE_LANGUAGE');
            return false;
        }
        return $langs;
    }

    /**
     * Get Language Slots

     * @param int $langId
     * @return array
     */
    public function getLangSlots(int $langId)
    {
        $srch = new SearchBase(TeachLanguage::DB_TBL, 'tlang');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'utlang.utlang_tlang_id = tlang.tlang_id', 'utlang');
        $on = 'tlang.tlang_id = tlanglang.tlanglang_tlang_id AND tlanglang.tlanglang_lang_id = ' . $langId;
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', $on, 'tlanglang');
        $srch->joinTable(
                UserSetting::DB_TBL,
                'INNER JOIN',
                'user.user_id = utlang.utlang_user_id AND user.user_id = ' . $this->userId,
                'user'
        );
        $srch->addMultipleFields([
            'tlang.tlang_id', 'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name', 
            'IFNULL(user.user_slots, "") as user_slots'
        ]);
        $srch->addOrder('tlang_name', 'ASC');
        $records = FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
        $langslots = [];
        foreach ($records as $data) {
            $langslots[$data['tlang_id']] = [
                'name' => $data['tlang_name'] ?? '', 
                'id' => $data['tlang_id'], 
                'slots' => FatUtility::int(json_decode($data['user_slots'], true))
            ];
        }
        return $langslots;
    }

    /**
     * Get Price Slabs
     * 
     * @param int $langId
     * @param int $tlangId
     * @return bool|array
     */
    public function getById(int $tlangId, int $langId = 0)
    {
        $srch = static::getSearchObject($langId);
        $srch->addCondition('tlang.tlang_id', '=', $tlangId);
        $srch->addCondition('utlang_user_id', '=', $this->userId);
        $adminManagePrice = FatApp::getConfig('CONF_MANAGE_PRICES', FatUtility::VAR_INT, 0);
        $srch->addMultipleFields(['tlang.tlang_id', 'IF(' . $adminManagePrice . ', tlang.tlang_hourly_price, utlang_price) as utlang_price', 'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name']);
        $srch->doNotCalculateRecords();
        $userLangData = FatApp::getDb()->fetch($srch->getResultSet(), 'tlang_id');
        if (empty($userLangData)) {
            return [];
        }
        return $userLangData;
    }

    /**
     * Get Lesson Types
     * 
     * @param int $langId
     * @return array
     */
    public function getLessonTypes()
    {
        $types = [];
        $settings = UserSetting::getSettings($this->userId, ['user_trial_enabled']);
        if ($settings['user_trial_enabled'] == AppConstant::YES) {
            $types[Lesson::TYPE_FTRAIL] = Label::getLabel('TYPE_FREE_TRAIL');
        }
        $types[Lesson::TYPE_REGULAR] = Label::getLabel('TYPE_REGULAR_LESSON');
        $types[Lesson::TYPE_SUBCRIP] = Label::getLabel('TYPE_SUBSCRIPTION');
        return $types;
    }

    public function getSrchObject(int $langId = 0)
    {
        $searchBase = new SearchBase(static::DB_TBL, 'utlang');
        $searchBase->addCondition('utlang.utlang_user_id', '=', $this->userId);
        $searchBase->joinTable(TeachLanguage::DB_TBL, 'INNER JOIN', 'tlang.tlang_id = utlang.utlang_tlang_id', 'tlang');
        if ($langId > 0) {
            $searchBase->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id =' . $langId, 'tlanglang');
        }
        return $searchBase;
    }

    /**
     * Remove Teach Languages
     * 
     * @param array $langIds
     * @return bool
     */
    public function removeTeachLang(array $langIds = []): bool
    {
        $query = 'DELETE  FROM ' . UserTeachLanguage::DB_TBL . ' Where 1 = 1';
        if (!empty($this->userId)) {
            $query .= ' and utlang_user_id = ' . $this->userId;
        }
        if (!empty($langIds)) {
            $langIds = implode(",", $langIds);
            $query .= ' and utlang_tlang_id IN (' . $langIds . ')';
        }
        $db = FatApp::getDb();
        $db->query($query);
        if ($db->getError()) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    public function getUserTechLandIds(): array
    {
        $srch = new SearchBase(UserTeachLanguage::DB_TBL, 'utlang');
        $srch->joinTable(TeachLanguage::DB_TBL, 'INNER JOIN', 'tlang.tlang_id = utlang.utlang_tlang_id', 'tlang');
        $srch->addMultipleFields(['utlang_id', 'utlang_tlang_id']);
        $srch->addCondition('utlang.utlang_user_id', '=', $this->userId);
        $srch->addGroupBy('utlang_tlang_id');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * User Teach Languages
     * 
     * @param int $langId
     * @param int $userId
     * @return array
     */
    public static function getUserTeachLangs(int $langId, int $userId): array
    {
        $srch = new SearchBase(TeachLanguage::DB_TBL, 'tlang');
        $srch->joinTable(UserTeachLanguage::DB_TBL, 'INNER JOIN', 'utlang.utlang_tlang_id = tlang.tlang_id', 'utlang');
        $on = 'tlang.tlang_id = tlanglang.tlanglang_tlang_id AND tlanglang.tlanglang_lang_id = ' . $langId;
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', $on, 'tlanglang');
        $srch->addCondition('utlang_user_id', '=', $userId);
        $srch->addOrder('tlang_name', 'ASC');
        $adminManagePrice = FatApp::getConfig('CONF_MANAGE_PRICES', FatUtility::VAR_INT, 0);
        $srch->addMultipleFields([
            'utlang_id', 'tlang.tlang_id', 'tlang.tlang_min_price',
            'tlang.tlang_max_price', 'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name',
            'IF(' . $adminManagePrice . ', tlang.tlang_hourly_price, utlang_price) as utlang_price'
        ]);
        $row = FatApp::getDb()->fetchAll($srch->getResultSet(), 'utlang_id');
        return $row;
    }

    /**
     * Update teach language price
     * @param int $langId
     * @param float $price
     * @return boolean
     */
    public function updateLangPrice(int $langId, float $price): bool
    {
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('utlang_price', $price);
        if (!$record->update(['smt' => 'utlang_tlang_id = ?', 'vals' => [$langId]])) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }


    private function updateUsrRecordCount(){
        $db = FatApp::getDb();
        if(!$db->query(
            "UPDATE ".TeachLanguage::DB_TBL." tlang 
            LEFT JOIN (SELECT COUNT(*) as ttl, utlang_tlang_id FROM ".static::DB_TBL." GROUP BY `utlang_tlang_id`) utlang
            ON utlang.utlang_tlang_id = tlang.tlang_id
            SET tlang.tlang_userrecords = utlang.ttl"
        )){
            $this->error = $db->getError();
            return false;
        }
        return true;
    }
}
