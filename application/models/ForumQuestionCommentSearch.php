<?php

/**
 * This class is used for Forum Question Comments Search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumQuestionCommentSearch extends SearchBase
{
    private $queId;
    private $userId;

    /**
     * Initialize ForumQuestionComment Class
     *
     * @param int $forumQueCommId
     */
    public function __construct(int $queId, int $userId = 0)
    {
        parent::__construct(ForumQuestionComment::DB_TBL, 'fqc');

        $this->queId = $queId;
        $this->userId = $userId;

        $this->joinTable(ForumQuestion::DB_TBL, 'INNER JOIN', 'fque_id = fquecom_fque_id');

        $this->joinTable(ForumStat::DB_TBL, 'LEFT JOIN', 'fquecom_id = fstat_record_id AND fstat_record_type = ' . ForumReaction::REACT_TYPE_COMMENT);
        
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'user_id = fquecom_user_id');
        $this->addCondition('fquecom_fque_id', '=', 'mysql_func_' . $this->queId, 'AND', true);
    }

    public function applyPrimaryConditions()
    {
        if (0 < $this->userId) {
            $this->addCondition('fque_user_id', '=', 'mysql_func_' . $this->userId, 'AND', true);
        }
        $this->addCondition('fquecom_deleted', '=', 'mysql_func_' . AppConstant::NO, 'AND', true);
        $this->addCondition('fque_deleted', '=', 'mysql_func_' . AppConstant::NO, 'AND', true);
    }

    public static function getListingFields()
    {
        return [
            'fquecom_id',
            'fquecom_comment',
            'fquecom_accepted',
            'fquecom_added_on',
            'fquecom_user_id',
            'fquecom_status',
            'fquecom_deleted',
            'fque_id',
            'fque_user_id',
            'fque_title',
            'fque_status',
            'fque_deleted',
            'fque_added_on',
            'COALESCE(fstat_likes, 0) as fstat_likes',
            'COALESCE(fstat_views, 0) as fstat_views',
            'COALESCE(fstat_dislikes, 0) as fstat_dislikes',
            'user_id',
            'user_first_name',
            'user_last_name'
        ];
    }

    public static function getTotalComments()
    {
        // all comments including deleted comments. No Matters Question is deleted, spammed etc.
        $srch = new SearchBase(ForumQuestionComment::DB_TBL);
        $srch->joinTable(ForumQuestion::DB_TBL, 'INNER JOIN', 'fque_id = fquecom_fque_id');

        $srch->doNotCalculateRecords();
        $srch->setPageNumber(1);
        $srch->addFld('count(fquecom_id) as total_comments');
        $result = FatApp::getDb()->fetch($srch->getResultSet());
        return $result['total_comments'];
    }

    public function fetchAndFormat()
    {
        $records = FatApp::getDb()->fetchAll($this->getResultSet());
        if (count($records) == 0) {
            return [];
        }

        foreach ($records as $key => $record) {
            $record['fquecom_added_on'] = MyDate::formatDate($record['fquecom_added_on']);
            $records[$key] = $record;
        }
        return $records;
    }

}
