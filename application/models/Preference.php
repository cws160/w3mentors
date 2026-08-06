<?php

/**
 * This class is used to handle Preferences
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Preference extends MyAppModel
{

    const DB_TBL = 'tbl_preferences';
    const DB_TBL_PREFIX = 'prefer_';
    const DB_TBL_LANG = 'tbl_preferences_lang';
    const DB_TBL_USER_PREF = 'tbl_user_preferences';
    /* Types */
    const TYPE_ACCENTS = 1;
    const TYPE_TEACHES_LEVEL = 2;
    const TYPE_LEARNER_AGES = 3;
    const TYPE_LESSONS = 4;
    const TYPE_TEST_PREPARATIONS = 6;

    /**
     * Initialize Preferences
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'prefer_id', $id);
    }

    /**
     * Get Preference Types
     * 
     * @return array
     */
    public static function getPreferenceTypeArr(): array
    {
        return [
            static::TYPE_ACCENTS => Label::getLabel('LBL_ACCENTS'),
            static::TYPE_TEACHES_LEVEL => Label::getLabel('LBL_TEACHES_LEVEL'),
            static::TYPE_LESSONS => Label::getLabel('LBL_LESSON_INCLUDES'),
            static::TYPE_TEST_PREPARATIONS => Label::getLabel('LBL_TEST_PREPARATIONS'),
            static::TYPE_LEARNER_AGES => Label::getLabel('LBL_LEARNER_AGES'),
        ];
    }

    /**
     * Delete Preference
     * 
     * @param int $preferId
     * @return bool
     */
    public function deletePreference(int $preferId): bool
    {
        $db = FatApp::getDb();
        if (!$db->deleteRecords(static::DB_TBL, ['smt' => 'prefer_id = ?', 'vals' => [$preferId]])) {
            $this->error = $db->getError();
            return false;
        }
        if (!$db->deleteRecords(static::DB_TBL_LANG, ['smt' => 'preferlang_prefer_id = ?', 'vals' => [$preferId]])) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Update Sort Order
     * 
     * @param array $order
     * @return bool
     */
    public function updateOrder(array $order): bool
    {
        if (empty($order)) {
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();
        foreach ($order as $i => $id) {
            if (FatUtility::int($id) < 1) {
                continue;
            }
            if (!$db->updateFromArray(static::DB_TBL, ['prefer_order' => $i], ['smt' => 'prefer_id = ?', 'vals' => [$id]])) {
                $db->rollbackTransaction();
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Get Preferences
     * 
     * @param int $langId
     * @return array
     */
    public static function getPreferencesArr(int $langId): array
    {
        $srch = new SearchBase(Preference::DB_TBL, 'p');
        $srch->joinTable(Preference::DB_TBL_LANG, 'LEFT OUTER JOIN', 'pl.preferlang_prefer_id = p.prefer_id AND pl.preferlang_lang_id = ' . $langId, 'pl');
        $srch->addMultipleFields(['prefer_id', 'IFNULL(prefer_title,prefer_identifier) as prefer_title', 'prefer_type']);
        $srch->addOrder('prefer_order', 'asc');
        $rs = $srch->getResultSet();
        $db = FatApp::getDb();
        $records = [];
        while ($row = $db->fetch($rs)) {
            $records[$row['prefer_type']][] = $row;
        }
        ksort($records);
        return $records;
    }

    /**
     * Get Options
     * 
     * @param int $langId
     * @return array
     */
    public static function getOptions(int $langId): array
    {
        $preferences = [
            static::TYPE_ACCENTS => [],
            static::TYPE_TEACHES_LEVEL => [],
            static::TYPE_LEARNER_AGES => [],
            static::TYPE_LESSONS => [],
            static::TYPE_TEST_PREPARATIONS => []
        ];
        $srch = new SearchBase(Preference::DB_TBL, 'prefer');
        $srch->joinTable(Preference::DB_TBL_LANG, 'LEFT JOIN', 'preferlang.preferlang_prefer_id '
                . '= prefer.prefer_id AND preferlang.preferlang_lang_id = ' . $langId, 'preferlang');
        $srch->addMultipleFields(['prefer.prefer_id', 'prefer.prefer_type',
            'IFNULL(preferlang.prefer_title, prefer.prefer_identifier) as prefer_title']);
        $srch->addOrder('prefer.prefer_order', 'asc');
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        $db = FatApp::getDb();
        while ($row = $db->fetch($resultSet)) {
            $preferences[$row['prefer_type']][$row['prefer_id']] = $row['prefer_title'];
        }
        return $preferences;
    }

    /**
     * Get User Preferences
     * 
     * @param int $userId
     * @param int $langId
     * @return array
     */
    public static function getUserPreferences(int $userId, int $langId = 0): array
    {
        $srch = new SearchBase(Preference::DB_TBL_USER_PREF, 'utp');
        $srch->joinTable(Preference::DB_TBL, 'INNER JOIN', 'uprefer_prefer_id=prefer_id', 'pref');
        $srch->addFld('pref.prefer_identifier as prefer_title');
        if ($langId > 0) {
            $srch->joinTable(Preference::DB_TBL_LANG, 'LEFT JOIN', 'uprefer_prefer_id = preferlang_prefer_id AND preferlang_lang_id = ' . $langId, 'prefLng');
            $srch->addFld('IFNULL(prefLng.prefer_title, pref.prefer_identifier) as prefer_title');
        }
        $srch->addMultiplefields(['uprefer_prefer_id', 'prefer_type']);
        $srch->addCondition('uprefer_user_id', '=', $userId);
        $srch->doNotCalculateRecords();
        $srch->addOrder('pref.prefer_order');
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

}
