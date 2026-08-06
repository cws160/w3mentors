<?php

/**
 * This class is a Base Model for all other models
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class MyAppModel extends FatModel
{

    protected $objMainTableRecord;
    protected $mainTableIdField;
    protected $mainTableRecordId;
    protected $mainTableName;
    protected $commonLangId;

    /**
     * Initialize MyApp Model
     *
     * @param string $tblName
     * @param string $keyFld
     * @param int $id
     */
    public function __construct(string $tblName, string $keyFld, int $id)
    {
        parent::__construct();
        $this->objMainTableRecord = new TableRecord($tblName);
        $this->mainTableIdField = $keyFld;
        $this->mainTableRecordId = FatUtility::convertToType($id, FatUtility::VAR_INT);
        $this->mainTableName = $tblName;
        $this->commonLangId = MyUtility::getSiteLangId();
    }

    public static function tblFld(string $key)
    {
        return static::DB_TBL_PREFIX . $key;
    }

    /**
     * Get All Names
     *
     * @param bool $assoc
     * @param int $recordId
     * @param int $activeFld
     * @return array
     */
    public static function getAllNames(bool $assoc = true, int $recordId = 0, int $activeFld = null)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addMultipleFields(array(static::tblFld('id'), static::tblFld('name')));
        $srch->addOrder(static::tblFld('name'));
        if ($activeFld != null) {
            $srch->addCondition($activeFld, '=', AppConstant::ACTIVE);
        }
        if ($recordId > 0) {
            $srch->addCondition(static::tblFld('id'), '=', FatUtility::int($recordId));
        }
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if ($assoc) {
            return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        } else {
            return FatApp::getDb()->fetchAll($srch->getResultSet(), static::tblFld('id'));
        }
    }

    /**
     * Update Lang Data
     *
     * @param int $langId
     * @param array $data
     * @return bool
     */
    public function updateLangData(int $langId, array $data): bool
    {
        if (!($this->mainTableRecordId > 0)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $record = new TableRecord(static::DB_TBL . '_lang');
        $record->assignValues($data);
        $prefix = substr(static::DB_TBL_PREFIX, 0, -1);
        $record->setFldValue($prefix . 'lang_' . static::DB_TBL_PREFIX . 'id', $this->mainTableRecordId);
        $record->setFldValue($prefix . 'lang_lang_id', FatUtility::int($langId));
        if (!$record->addNew([], $record->getFlds())) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Assign Values
     *
     * @param array $arr
     * @param bool $handleDates
     * @param string $mysqlDateFormat
     * @param string $mysqlDatetimeFormat
     * @param bool $executeMysqlFunctions
     */
    public function assignValues(array $arr, bool $handleDates = false, string $mysqlDateFormat = '', string $mysqlDatetimeFormat = '', bool $executeMysqlFunctions = false)
    {
        $this->objMainTableRecord->assignValues($arr, $handleDates, $mysqlDateFormat, $mysqlDatetimeFormat, $executeMysqlFunctions);
    }

    /**
     * Delete Record
     *
     * @param bool $deleteLangData
     * @return bool
     */
    public function deleteRecord(bool $deleteLangData = false): bool
    {
        $stmt = ['smt' => $this->mainTableIdField . ' = ?', 'vals' => [$this->mainTableRecordId]];
        if (!FatApp::getDb()->deleteRecords($this->mainTableName, $stmt)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        if ($deleteLangData == false) {
            return true;
        }
        $prefix = substr(static::DB_TBL_PREFIX, 0, -1);
        $stmt = ['smt' => $prefix . 'lang_' . static::DB_TBL_PREFIX . 'id' . ' = ?', 'vals' => [$this->mainTableRecordId]];
        if (!FatApp::getDb()->deleteRecords($this->mainTableName . '_lang', $stmt)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Load From Database
     *
     * @param bool $prepare_dates_for_display
     * @return type
     */
    public function loadFromDb(bool $prepare_dates_for_display = false)
    {
        $stmt = ['smt' => $this->mainTableIdField . " = ?", 'vals' => [$this->mainTableRecordId]];
        if (!$result = $this->objMainTableRecord->loadFromDb($stmt, $prepare_dates_for_display)) {
            $this->error = $this->objMainTableRecord->getError();
        }
        return $result;
    }

    /**
     * Get Attributes By Id
     *
     * @param int $recordId
     * @param string|array $attr
     * @return bool|array
     */
    public static function getAttributesById(int $recordId, $attr = null)
    {
        $db = FatApp::getDb();
        $srch = new SearchBase(static::DB_TBL);
        $srch->doNotCalculateRecords();
        $srch->setPagesize(1);
        $srch->addCondition(static::tblFld('id'), '=', $recordId);
        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $rs = $srch->getResultSet();
        $row = $db->fetch($rs);
        if (!is_array($row)) {
            return false;
        }
        if (is_string($attr)) {
            return $row[$attr];
        }
        return $row;
    }

    /**
     * Get Attributes By Lang Id
     *
     * @param int $langId
     * @param int $recordId
     * @param type $attr
     * @return bool|string|array
     */
    public static function getAttributesByLangId($langId, $recordId, $attr = null)
    {
        $recordId = FatUtility::convertToType($recordId, FatUtility::VAR_INT);
        $langId = FatUtility::convertToType($langId, FatUtility::VAR_INT);
        $db = FatApp::getDb();
        $srch = new SearchBase(static::DB_TBL . '_lang', 'ln');
        $srch->doNotCalculateRecords();
        $srch->setPagesize(1);
        $prefix = substr(static::DB_TBL_PREFIX, 0, -1);
        $srch->addCondition('ln.' . $prefix . 'lang_' . static::DB_TBL_PREFIX . 'id', '=', $recordId);
        $srch->addCondition('ln.' . $prefix . 'lang_lang_id', '=', FatUtility::int($langId));
        if (null != $attr) {
            if (is_array($attr)) {
                $srch->addMultipleFields($attr);
            } elseif (is_string($attr)) {
                $srch->addFld($attr);
            }
        }
        $row = $db->fetch($srch->getResultSet());
        if (!is_array($row)) {
            return false;
        }
        if (is_string($attr)) {
            return $row[$attr];
        }
        return $row;
    }

    /**
     * Get Fields
     *
     * @return type
     */
    public function getFlds()
    {
        return $this->objMainTableRecord->getFlds();
    }

    /**
     * Get Field Value
     *
     * @param type $key
     * @return type
     */
    public function getFldValue($key)
    {
        return $this->objMainTableRecord->getFldValue($key);
    }

    /**
     * Set Fields
     *
     * @param array $arr
     */
    public function setFlds(array $arr)
    {
        $this->objMainTableRecord->setFlds($arr);
    }

    /**
     * Set Field Value
     *
     * @param string $key
     * @param string $val
     * @param bool $execute_mysql_function
     */
    public function setFldValue(string $key, string $val, bool $execute_mysql_function = false)
    {
        $this->objMainTableRecord->setFldValue($key, $val, $execute_mysql_function);
    }

    /**
     * Save Record
     *
     * @return bool
     */
    public function save()
    {
        if (0 < $this->mainTableRecordId) {
            $result = $this->objMainTableRecord->update(['smt' => $this->mainTableIdField . ' = ?', 'vals' => [$this->mainTableRecordId]]);
        } else {
            $result = $this->objMainTableRecord->addNew();
            if ($result) {
                $this->mainTableRecordId = $this->objMainTableRecord->getId();
            }
        }
        if (!$result) {
            $this->error = $this->objMainTableRecord->getError();
        }
        return $result;
    }

    /**
     * Get Main Table Record Id
     *
     * @return int
     */
    public function getMainTableRecordId()
    {
        return FatUtility::int($this->mainTableRecordId);
    }

    /**
     * Change Status
     *
     * @param type $v
     * @return bool
     */
    public function changeStatus($v = 1): bool
    {
        if (!($this->mainTableRecordId > 0)) {
            $this->error = 'ERR_INVALID_REQUEST_ID';
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->updateFromArray(
            static::DB_TBL,
            [static::DB_TBL_PREFIX . 'active' => $v],
            ['smt' => static::DB_TBL_PREFIX . 'id = ?', 'vals' => [$this->mainTableRecordId]]
        )) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Update Order
     *
     * @param array $order
     * @return bool
     */
    public function updateOrder(array $order): bool
    {
        if (empty($order)) {
            return false;
        }
        foreach ($order as $i => $id) {
            if (FatUtility::int($id) < 1) {
                continue;
            }
            if (!FatApp::getDb()->updateFromArray(
                static::DB_TBL,
                [static::DB_TBL_PREFIX . 'order' => $i],
                ['smt' => static::DB_TBL_PREFIX . 'id = ?', 'vals' => [$id]]
            )) {
                $this->error = FatApp::getDb()->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Add New Record
     *
     * @param array $insert_options
     * @param array $flds_update_on_duplicate
     * @return bool
     */
    public function addNew(array $insert_options = [], array $flds_update_on_duplicate = []): bool
    {
        if (!$this->objMainTableRecord->addNew($insert_options, $flds_update_on_duplicate)) {
            $this->error = $this->objMainTableRecord->getError();
            return false;
        }
        $this->mainTableRecordId = $this->objMainTableRecord->getId();
        return true;
    }

    public function uniqueIdentifierCheck($identifier)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('mysql_func_LOWER(' . static::DB_TBL_PREFIX . 'identifier)', '=', strtolower(trim($identifier)), 'AND', true);
        $srch->addCondition(static::DB_TBL_PREFIX . 'id', '!=', $this->getMainTableRecordId());
        $srch->doNotCalculateRecords();
        if (FatApp::getDb()->fetch($srch->getResultSet())) {
            return false;
        }
        return true;
    }
}
