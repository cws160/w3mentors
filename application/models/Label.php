<?php

/**
 * This class is used to handle Labels
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class Label extends MyAppModel
{

    const DB_TBL = 'tbl_language_labels';
    const DB_TBL_PREFIX = 'label_';

    /**
     * Initialize Label
     * 
     * @param int $labelId
     */
    public function __construct(int $labelId = 0)
    {
        parent::__construct(static::DB_TBL, 'label_id', $labelId);
    }

    /**
     * Get Search ObjectF
     * 
     * @param int $langId
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL, 'lbl');
        $srch->addMultipleFields(['label_id', 'label_lang_id', 'label_key', 'label_caption',]);
        if ($langId > 0) {
            $srch->addCondition('lbl.label_lang_id', '=', $langId);
        }
        return $srch;
    }

    /**
     * Get Label
     * 
     * @global array $lang_array
     * @global array $lang_array
     * @param string $lblKey
     * @param int $langId
     * @return string
     */
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
        $srch->addCondition('label_key', '=', $lblKey);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $lbl = $db->fetch($srch->getResultSet());
        if (!empty($lbl['label_caption'] ?? '')) {
            $str = $lbl['label_caption'];
        } else {
            $arr = explode(' ', ucwords(str_replace('_', ' ', strtolower($lblKey))));
            array_shift($arr);
            $str = implode(' ', $arr);
            $str = str_replace('"', "'", $str);
            $assignValues = [
                'label_key' => $lblKey,
                'label_caption' => $str,
                'label_lang_id' => $langId
            ];
            $db->insertFromArray(static::DB_TBL, $assignValues, false, [], $assignValues);
        }
        return $lang_array[$lblKey][$langId] = commonHelper::renderHtml($str, false);
    }

    /**
     * Add Update Data
     * 
     * @param array $data
     * @return bool
     */
    public function addUpdateData(array $data = []): bool
    {
        $data['label_caption'] = str_replace('"', "'", $data['label_caption']);
        $assignValues = [
            'label_key' => strtoupper($data['label_key']),
            'label_caption' => $data['label_caption'],
            'label_lang_id' => $data['label_lang_id']
        ];
        $db = FatApp::getDB();
        if (!$db->insertFromArray(static::DB_TBL, $assignValues, false, [], $assignValues)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }
}
