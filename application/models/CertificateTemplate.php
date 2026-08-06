<?php

/**
 * This class is used to handle certificates
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CertificateTemplate extends MyAppModel
{
    const DB_TBL = 'tbl_certificate_templates';
    const DB_TBL_PREFIX = 'certpl_';
    
    /**
     * Initialize certificate
     *
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'certpl_id', $id);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @return SearchBase
     */
    public static function getSearchObject(int $langId): SearchBase
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addMultipleFields(
        [
            'certpl_type',
            'certpl_code',
            'certpl_lang_id',
            'certpl_name',
            'certpl_body',
            'certpl_vars',
            'certpl_status',
            'certpl_id',
            'certpl_updated'
        ]
        );
        $srch->addCondition('certpl_lang_id', '=', $langId);
        $srch->addOrder('certpl_status', 'DESC');
        $srch->addOrder('certpl_name', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return $srch;
    }

    /**
     * Setup template
     *
     * @param array $data
     * @return bool
     */
    public function setup(array $data): bool
    {
        $db = FatApp::getDb();
        $db->startTransaction();

        $this->assignValues($data);
        $this->setFldValue('certpl_updated', date('Y-m-d H:i:s'));
        if (!$this->save()) {
            $this->error = $this->getError();
            return false;
        }

        /* update status */
        if (!$this->updateStatus($data['certpl_code'], $data['certpl_status'])) {
            $db->rollbackTransaction();
            return false;
        }
        
        $db->commitTransaction();
        return true;
    }
    
    /**
     * function to update template status
     *
     * @param string  $certTplCode
     * @param int     $status
     * @return bool
     */
    public function updateStatus(string $certTplCode, int $status)
    {
        $whr = ['smt' => 'certpl_code = ?', 'vals' => [$certTplCode]];
        if (!FatApp::getDb()->updateFromArray(static::DB_TBL, ['certpl_status' => $status], $whr)) {
            $this->error = $this->getError();
            return false;
        }

        return true;
    }
    
    /**
     * Check course certificates available or not
     *
     * @return bool
     */
    public static function checkCourseCertificatesAvailable()
    {
        $templates = Certificate::getTypes();
        unset($templates[Certificate::TYPE_QUIZ_EVALUATION]);
        return (count($templates) > 0);
    }
}
