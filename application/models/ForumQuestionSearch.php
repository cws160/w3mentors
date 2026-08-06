<?php

/**
 * This class is used for Forum Tags Search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumQuestionSearch extends SearchBased
{

    private $searchByPopular = false;

    public const TYPE_ALL = 0; // Not Resolved yet
    public const TYPE_ACTIVE = 1; // Not Resolved yet
    public const TYPE_ANSWERED = 2; // Resolved
    public const TYPE_POPULAR = 3; // Popular

    /**
     * Initialize ForumQuestionSearch Class
     *
     * @param int $langId
     */
    public function __construct()
    {
        parent::__construct(ForumQuestion::DB_TBL, 'fque');
    }

    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addDirectCondition('fque.fque_deleted = 0');
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (1 > count($post)) {
            return;
        }
        if (!empty($post['lang_id'])) {
            $this->addCondition('fque.fque_lang_id', '=', $post['lang_id']);
        }
        if (!empty($post['id'])) {
            $this->addCondition('fque.fque_id', '=', $post['id']);
        }
        if (!empty($post['fque_slug'])) {
            $this->addCondition('fque.fque_slug', '=', $post['fque_slug']);
        }
        if (!empty($post['user_id'])) {
            $this->addCondition('fque.fque_user_id', '=', $post['user_id']);
        }
        if (!empty($post['date_from'])) {
            $this->addCondition('fque.fque_added_on', '>=', $post['date_from']);
        }
        if (!empty($post['date_till'])) {
            $this->addCondition('fque.fque_added_on', '<=', $post['date_till']);
        }
        $keyword = trim($post['keyword'] ?? '');
        if (!empty($keyword)) {
            $this->addCondition('fque.fque_title', 'LIKE', '%' . $keyword . '%')
                    ->attachCondition('fque.fque_description', 'LIKE', '%' . $keyword . '%');
        }
        if (!empty($post['tags'])) {
            $this->addCondition('ftagque_ftag_id', 'IN', $post['tags']);
        }
    }

    public function applySearchByType(int $type): void
    {
        $this->searchByPopular = false;
        if (static::TYPE_ACTIVE == $type) {
            $this->addStatusCondition([ForumQuestion::FORUM_QUE_PUBLISHED]);
        } elseif (static::TYPE_ANSWERED == $type) {
            $this->addStatusCondition([ForumQuestion::FORUM_QUE_RESOLVED]);
        } elseif (static::TYPE_POPULAR == $type) {
            $this->searchByPopular = true;
            $this->addStatusCondition([ForumQuestion::FORUM_QUE_PUBLISHED, ForumQuestion::FORUM_QUE_RESOLVED]);
            $this->addHaving('pop_count', '>', 'mysql_func_' . 0, 'AND', true);
        } else {
            $this->addStatusCondition([ForumQuestion::FORUM_QUE_PUBLISHED, ForumQuestion::FORUM_QUE_RESOLVED]);
        }
    }

    public function addOrderBy(array $order)
    {
        if (!empty($order)) {
            foreach ($order as $key => $val) {
                $this->addOrder($key, $val);
            }
        }
        $this->addOrder('fque_id', 'DESC');
    }

    public function joinWithStats()
    {
        $this->joinTable(ForumStat::DB_TBL, 'LEFT JOIN', 'fque_id = fstat_record_id AND fstat_record_type = ' . ForumReaction::REACT_TYPE_QUESTION);
        if (true == $this->searchByPopular) {
            $this->addFld('(COALESCE(fstat_comments, 0) + COALESCE(fstat_likes, 0)) as pop_count');
        }
        $this->addMultipleFields(static::getStatsFields());
    }

    public function joinWithTags()
    {
        $this->joinTable(ForumTag::DB_TBL_TAGS_TO_QUESTION, 'LEFT JOIN', 'fque_id = ftagque_fque_id');
        $this->joinTable(ForumTag::DB_TBL, 'INNER JOIN', 'ftagque_ftag_id = ftag_id');
        $this->addCondition('ftag_active', '=', AppConstant::YES);
        $this->addCondition('ftag_deleted', '=', AppConstant::NO);
    }

    public function joinWithUsers()
    {
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = fque_user_id', 'user');
        $this->addDirectCondition('user.user_deleted IS NULL');
        $this->addDirectCondition('user.user_verified IS NOT NULL');
        $this->addCondition('user.user_active', '=', AppConstant::YES);
    }

    public function addStatusCondition(array $status)
    {
        if (1 > count($status)) {
            $status = [FORUM_QUE_PUBLISHED];
        }
        $this->addCondition('fque.fque_status', 'IN', FatUtility::int($status));
    }

    public function addKeywordSearchCondition($keyword = '')
    {
        $this->addCondition('fque.fque_title', 'LIKE', '%' . $keyword . '%');
    }

    public function addLanguageCondition(int $langId)
    {
        $this->addCondition('fque.fque_lang_id', '=', $langId);
    }

    public function addUserIdCondition($userId)
    {
        $this->addCondition('fque.fque_user_id', '=', 'mysql_func_' . $userId, 'AND', true);
    }

    public function getRecommendedPosts(int $langId, array $queIds = [], array $tagIds = [], int $loggedUserId = 0): array
    {
        if (1 > count($tagIds)) {
            return [];
        }
        $this->applyPrimaryConditions();
        $this->joinWithStats();
        $this->joinWithTags();
        if (0 < count($tagIds)) {
            $this->addCondition('ftag_id', 'IN', $tagIds);
        }
        if (0 < count($queIds)) {
            $this->addCondition('fque_id', 'NOT IN', $queIds);
        }
        $flds = ['fque_title', 'fque_slug', 'fque_id', '(fstat_comments + fstat_likes) as stats_count'];
        $this->addMultipleFields($flds);
        if (0 < $langId) {
            $this->applySearchConditions(['lang_id' => $langId]);
        }
        if (0 < $loggedUserId) {
            $this->addCondition('fque_user_id', '!=', $loggedUserId);
        }
        $this->applySearchByType(static::TYPE_ALL);
        $this->addGroupBy('fque_id');
        $this->doNotCalculateRecords();
        $this->setPageSize(5);
        $this->setPageNumber(1);
        $this->addOrderBy(['stats_count' => 'DESC']);
        return FatApp::getDb()->fetchAll($this->getResultSet());
    }

    /**
     * Get Record Count
     *
     * @return int
     */
    public function getRecordCount(): int
    {
        $db = FatApp::getDb();
        $order = $this->order;
        $page = $this->page;
        $pageSize = $this->pageSize;
        $this->limitRecords = false;
        $this->order = [];
        $qry = $this->getQuery() . ' LIMIT ' . SEARCH_MAX_COUNT . ', 1';
        if ($db->totalRecords($db->query($qry)) > 0) {
            $recordCount = SEARCH_MAX_COUNT;
        } else {
            if (empty($this->groupby) && empty($this->havings)) {
                $this->addFld('COUNT(*) AS total');
                $rs = $db->query($this->getQuery());
            } else {
                $rs = $db->query('SELECT COUNT(*) AS total FROM (' . $this->getQuery() . ') t');
            }
            $recordCount = FatUtility::int($db->fetch($rs)['total'] ?? 0);
        }
        $this->order = $order;
        $this->page = $page;
        $this->pageSize = $pageSize;
        $this->limitRecords = true;
        return $recordCount;
    }

    public function fetchAndFormat()
    {
        $records = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($records) == 0) {
            return [];
        }

        foreach ($records as $key => $record) {
            $record['fque_added_on'] = MyDate::formatDate($record['fque_added_on']);
            $records[$key] = $record;
        }
        return $records;
    }

    public static function getListingFields()
    {
        return [
            'fque_id', 'fque_title', 'fque_slug', 'fque_status', 'fque_description',
            'fque_added_on', 'fque_comments_allowed', 'fque_updated_on', 'fque_deleted',
            'user_id', "CONCAT_WS(' ', user_first_name, user_last_name) as user_name",
            'user_first_name', 'user_last_name', 'fque_lang_id'
        ];
    }

    public static function getStatsFields()
    {
        return [
            'COALESCE(fstat_comments, 0) as fstat_comments',
            'COALESCE(fstat_likes, 0) as fstat_likes',
            'COALESCE(fstat_dislikes, 0) as fstat_dislikes',
            'COALESCE(fstat_views, 0) as fstat_views'
        ];
    }

    public static function getSearchTypeArr()
    {
        return [
            static::TYPE_ALL => Label::getLabel("LBL_All_Questions"),
            static::TYPE_ACTIVE => Label::getLabel("LBL_Active_Questions"),
            static::TYPE_ANSWERED => Label::getLabel("LBL_Answered_Questions"),
            static::TYPE_POPULAR => Label::getLabel("LBL_Popular_Questions"),
        ];
    }

}
