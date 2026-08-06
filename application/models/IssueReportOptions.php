<?php

/**
 * This class is used to handle Issue Report Options
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class IssueReportOptions extends MyAppModel
{

    const DB_TBL = 'tbl_issue_report_options';
    const DB_TBL_PREFIX = 'tissueopt_';
    const DB_TBL_LANG = 'tbl_issue_report_options_lang';
    const DB_TBL_LANG_PREFIX = 'tissueoptlang_';

    /**
     * Initialize Issue Report Options
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'tissueopt_id', $id);
    }

    /**
     * Get All Options
     * 
     * @param int $langId
     * @return SearchBase
     */
    public static function getSearchObj(int $langId): SearchBase
    {
        $srch = new SearchBase(IssueReportOptions::DB_TBL, 'iropt');
        $on = 'iroptLang.tissueoptlang_tissueopt_id = iropt.tissueopt_id AND iroptLang.tissueoptlang_lang_id = ' . $langId;
        $srch->joinTable(IssueReportOptions::DB_TBL_LANG, 'LEFT JOIN', $on, 'iroptLang');
        $srch->doNotCalculateRecords();
        return $srch;
    }

    /**
     * Get Options Array
     * 
     * @param int $langId
     * @return array
     */
    public static function getOptionsArray(int $langId): array
    {
        $srch = new SearchBase(IssueReportOptions::DB_TBL, 'iropt');
        $on = 'iroptLang.tissueoptlang_tissueopt_id = iropt.tissueopt_id AND iroptLang.tissueoptlang_lang_id = ' . $langId;
        $srch->joinTable(IssueReportOptions::DB_TBL_LANG, 'LEFT JOIN', $on, 'iroptLang');
        $srch->addCondition('tissueopt_active', '=', AppConstant::YES);
        $srch->addMultipleFields(['tissueopt_id', 'IFNULL(tissueoptlang_title, tissueopt_identifier) as tissueoptlang_title']);
        $srch->addOrder('tissueopt_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Delete Option
     * 
     * @param int $optId
     * @return bool
     */
    public function deleteOption(int $optId): bool
    {
        $db = FatApp::getDb();
        $langDelete = $db->deleteRecords(static::DB_TBL_LANG, ['smt' => 'tissueoptlang_tissueopt_id = ?', 'vals' => [$optId]]);
        if (!$db->deleteRecords(static::DB_TBL, ['smt' => 'tissueopt_id = ?', 'vals' => [$optId]]) && !$langDelete) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

}
