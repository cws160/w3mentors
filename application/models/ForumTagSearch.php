<?php

/**
 * This class is used for Forum Tags Search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumTagSearch extends SearchBased
{

    /**
     * Initialize ForumTagSearch Class
     *
     * @param int $langId
     */
    public function __construct(int $langId = 0, bool $active = true, bool $includeDeleted = true)
    {
        parent::__construct(ForumTag::DB_TBL, 'ftag');

        $this->joinTable(Language::DB_TBL, 'LEFT JOIN', 'ftag.ftag_language_id = language_id');

        if (0 < $langId) {
            $this->addCondition('ftag.ftag_language_id', '=', $langId);
        }

        if ($active == true) {
            $this->addCondition('ftag.ftag_active', '=', AppConstant::ACTIVE);
        }

        if ($includeDeleted == false) {
            $this->addCondition('ftag.ftag_deleted', '=', AppConstant::NO);
        }

        $this->addOrder('ftag.ftag_active', 'DESC');
        $this->addOrder('ftag.ftag_deleted', 'ASC');
        $this->addOrder('ftag.ftag_id', 'DESC');
    }

    public static function getPopularTags(int $langId): array
    {
        $srch = new SearchBase(ForumTag::DB_TBL, 'ftag');
        $srch->joinTable(ForumTag::DB_TBL_TAGS_TO_QUESTION, 'INNER JOIN', 'ftag_id = ftagque_ftag_id');
        $srch->joinTable(ForumQuestion::DB_TBL, 'INNER JOIN', 'fque_id = ftagque_fque_id AND (fque_status = ' . ForumQuestion::FORUM_QUE_PUBLISHED . ' OR fque_status = ' . ForumQuestion::FORUM_QUE_RESOLVED . ') AND fque_deleted = ' . AppConstant::NO . ' AND fque_lang_id = ' . $langId);
        $srch->joinTable(ForumStat::DB_TBL, 'INNER JOIN', 'fque_id = fstat_record_id AND fstat_record_type = ' . ForumReaction::REACT_TYPE_QUESTION);
        $srch->addMultipleFields(['ftag_id', 'ftag_name', 'SUM(fstat_comments) as final_comments', 'fque_id',]);
        $srch->addCondition('ftag.ftag_active', '=', 'mysql_func_' . AppConstant::YES, 'AND', true);
        $srch->addCondition('ftag.ftag_deleted', '=', 'mysql_func_' . AppConstant::NO, 'AND', true);
        $srch->addCondition('ftag.ftag_language_id', '=', 'mysql_func_' . $langId, 'AND', true);
        $srch->addOrder('final_comments', 'DESC');
        $srch->addOrder('ftag_id', 'ASC');
        $srch->addGroupBy('ftag_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(10);
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

}
