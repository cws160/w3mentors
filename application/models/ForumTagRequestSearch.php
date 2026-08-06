<?php

/**
 * This class is used for Forum Tags Requests Search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumTagRequestSearch extends SearchBased
{
    /**
     * Initialize ForumTagRequestSearch Class
     *
     * @param int $langId
     */
    public function __construct(int $langId = 0)
    {
        parent::__construct(ForumTagRequest::DB_TBL, 'ftagreq');

        if (0 < $langId) {
            $this->addCondition('ftagreq.ftagreq_language_id', '=', $langId);
        }
    }

    public function joinWithUserTable(int $userId = 0)
    {
        $this->joinTable(User::DB_TBL, 'LEFT JOIN', 'ftagreq.ftagreq_user_id = user_id');

        if (0 < $userId) {
            $this->addCondition('ftagreq.ftagreq_user_id', '=', $userId);
        }
    }
}
