<?php

/**
 * This class is used to define Forum Report Issue Reasons
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumReportIssueReason extends MyAppModel
{
    public const DB_TBL = 'tbl_forum_report_issue_reasons';
    public const DB_TBL_PREFIX = 'frireason_';
    public const DB_TBL_LANG = 'tbl_forum_report_issue_reasons_lang';
    public const DB_TBL_LANG_PREFIX = 'frireasonlang_';

    /**
     * Initialize ForumTag Class
     *
     * @param int $reasonId
     */
    public function __construct(int $reasonId = 0)
    {
        parent::__construct(static::DB_TBL, 'frireason_id', $reasonId);
    }

    /**
     * Get All Names
     *
     * @param bool $assoc
     * @param int $recordId
     * @param int $active
     * @return array
     */
    public static function getAllReasons(bool $assoc = true, int $recordId = 0, bool $active = true, int $langId = 0): array
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'frireason_id = frireasonlang_frireason_id AND frireasonlang_lang_id = ' . $langId);
        $srch->addOrder('frireason_order');
        $srch->addOrder('frireason_id');

        if (true == $active) {
            $srch->addCondition('frireason_active', '=', AppConstant::ACTIVE);
        }

        if ($recordId > 0) {
            $srch->addCondition('frireason_id', '=', FatUtility::int($recordId));
        }

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        if ($assoc) {
            $srch->addMultipleFields(array('frireason_id', 'IFNULL(frireason_name, frireason_identifier) as frireason_name'));
            return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        } else {
            return FatApp::getDb()->fetchAll($srch->getResultSet(), 'frireason_id');
        }
    }
}
