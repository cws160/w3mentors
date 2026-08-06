<?php

/**
 * This class is used to handle App Labels
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class AppLabel extends MyAppModel
{

    const DB_TBL = 'tbl_app_labels';
    const DB_TBL_PREFIX = 'applbl_';

    /**
     * Initialize Label
     * 
     * @param int $labelId
     */
    public function __construct(int $labelId)
    {
        parent::__construct(static::DB_TBL, 'applbl_id', $labelId);
    }

    public static function getSearchObject(int $langId = 0): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'lbl');
        $srch->addMultipleFields(['applbl_id', 'applbl_lang_id', 'applbl_key', 'applbl_value',]);
        if ($langId > 0) {
            $srch->addCondition('lbl.applbl_lang_id', '=', $langId);
        }
        $srch->addOrder('lbl.applbl_id', 'DESC');
        return $srch;
    }

    public static function getLabel(string $lblKey, int $langId = 0)
    {
        if (preg_match('/\s/', $lblKey)) {
            return $lblKey;
        }
        $lblKey = strtoupper($lblKey);
        $langId = (0 >= $langId) ? MyUtility::getSiteLangId() : FatUtility::int($langId);
        global $lang_array;
        if (isset($lang_array[$lblKey][$langId])) {
            if ($lang_array[$lblKey][$langId] != '') {
                return $lang_array[$lblKey][$langId];
            } else {
                $arr = explode(' ', ucwords(str_replace('_', ' ', strtolower($lblKey))));
                array_shift($arr);
                return $str = implode(' ', $arr);
            }
        }
        $db = FatApp::getDb();
        $srch = static::getSearchObject($langId);
        $srch->addCondition('applbl_key', '=', $lblKey);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $lbl = $db->fetch($srch->getResultSet());
        if (!empty($lbl['applbl_value'] ?? '')) {
            $str = $lbl['applbl_value'];
        } else {
            $arr = explode(' ', ucwords(str_replace('_', ' ', strtolower($lblKey))));
            array_shift($arr);
            $str = implode(' ', $arr);
            $str = str_replace('"', "'", $str);
            $assignValues = [
                'applbl_key' => $lblKey,
                'applbl_value' => $str,
                'applbl_lang_id' => $langId
            ];
            $db->insertFromArray(static::DB_TBL, $assignValues, false, [], $assignValues);
        }
        return $lang_array[$lblKey][$langId] = $str;
    }

    /**
     * Add Update Data
     * 
     * @param array $data
     * @return bool
     */
    public function addUpdateData(array $data = []): bool
    {
        $data['applbl_value'] = str_replace('"', "'", $data['applbl_value']);
        $assignValues = [
            'applbl_key' => $data['applbl_key'],
            'applbl_value' => $data['applbl_value'],
            'applbl_lang_id' => $data['applbl_lang_id']
        ];
        $db = FatApp::getDB();
        if (!$db->insertFromArray(static::DB_TBL, $assignValues, false, [], $assignValues)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }
}
