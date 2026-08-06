<?php

/**
 * This class is used to handle EmailTemplates
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class EmailTemplates extends FatModel
{

    const DB_TBL = 'tbl_email_templates';
    const DB_TBL_PREFIX = 'etpl_';

    static private $_instance;

    /**
     * Initialize EmailTemplates
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get Instance
     * 
     * @return type
     */
    public static function getInstance()
    {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Get Email Template
     * 
     * @param string $tplCode
     * @param int $langId
     * @return array
     */
    public function getEtpl(string $tplCode = '', int $langId = 0)
    {
        if (empty($tplCode)) {
            return [];
        }
        $db = FatApp::getDb();
        $srch = static::getSearchObject($langId);
        $srch->addCondition('etpl_code', 'LIKE', $tplCode);
        if ($langId > 0) {
            $srch->addCondition('etpl_lang_id', '=', $langId);
        }
        $srch->addOrder('etpl_lang_id', 'ASC');
        $srch->addGroupby('etpl_code');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $data = $db->fetch($srch->getResultSet());
        if (!empty($data)) {
            return $data;
        }
        return [];
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @return SearchBase
     */
    public static function getSearchObject(int $langId): SearchBase
    {
        if ($langId < 1) {
            $langId = MyUtility::getSiteLangId();
        }
        $srch = new SearchBased(static::DB_TBL);
        $srch->addMultipleFields(['etpl_code', 'etpl_lang_id', 'etpl_name', 'etpl_subject', 'etpl_body', 'etpl_vars', 'etpl_status']);
        $srch->addCondition('etpl_lang_id', '=', $langId);
        $srch->addOrder('etpl_status', 'DESC');
        $srch->addOrder('etpl_name', 'ASC');
        return $srch;
    }

    /**
     * Add Update Data
     * 
     * @param array $data
     * @return bool
     */
    public function addUpdateData(array $data = []): bool
    {
        if (!FatApp::getDb()->insertFromArray(static::DB_TBL, $data, false, [], $data)) {
            $this->error = FatApp::getDb()->getError();
            return false;
        }
        return true;
    }

    /**
     * Activate Email Template
     * 
     * @param int $status
     * @param string $etplCode
     * @return bool
     */
    public function activateEmailTemplate(int $status = 1, string $etplCode = ''): bool
    {
        if (!$etplCode) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST_USER_NOT_INITIALIZED');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, ['etpl_status' => $status],
                        ['smt' => 'etpl_code = ?', 'vals' => [$etplCode]])) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

}
