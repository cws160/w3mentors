<?php

/**
 * This class is used for Forum Tags Search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumReportIssueReasonSearch extends SearchBase
{
    /**
     * Initialize ForumReportIssueReasonSearch Class
     *
     * @param int $langId
     */
    public function __construct(int $langId = 0, bool $active = true)
    {
        parent::__construct(ForumReportIssueReason::DB_TBL, 'frireason');

        if ($active == true) {
            $this->addCondition('frireason.frireason_active', '=', AppConstant::ACTIVE);
        }

        if ($langId > 0) {
            $this->joinTable(ForumReportIssueReason::DB_TBL_LANG, 'LEFT JOIN', 'frireason_l.frireasonlang_frireason_id = frireason.frireason_id and frireason_l.frireasonlang_lang_id = ' . $langId, 'frireason_l');
        }
        
        $this->addOrder('frireason.frireason_active', 'DESC');
        $this->addOrder('frireason.frireason_order', 'ASC');
        $this->addOrder('frireason.frireason_id', 'DESC');
    }
}
