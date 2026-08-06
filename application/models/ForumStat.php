<?php

/**
 * This class is used to handle Forum Stats
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumStat extends FatModel
{
    const DB_TBL = 'tbl_forum_stats';
    const DB_TBL_PREFIX = 'fstat_';

    private $recordId;
    private $recordType;

    /**
     * Initialize ForumStat
     *
     * @param int $recordId
     * @param int $recordType
     */
    public function __construct(int $recordId, int $recordType)
    {
        parent::__construct();
        $this->recordId = $recordId;
        $this->recordType = $recordType;
    }

    /**
     * Udpate Reactions Count
     *
     * @return bool
     */
    public function updateReactionCount(int $upVote, int $downVote)
    {
        $smt = 'INSERT  INTO `tbl_forum_stats` SET `fstat_record_id` = ?, `fstat_record_type` = ?, `fstat_likes` = ' . (1 == $upVote ? 1 : 0) . ', `fstat_dislikes` = ' . (1 == $downVote ? 1 : 0) . ' ON DUPLICATE KEY UPDATE `fstat_likes` = fstat_likes + (?), `fstat_dislikes` = fstat_dislikes + (?)';
        
        $db = FatApp::GetDb();

        $smt = $db->prepareStatement($smt);
        $smt->bindParameters('iiii', $this->recordId, $this->recordType, $upVote, $downVote);

        if (!$smt->execute()) {
            $this->error = $record->getError();
            return false;
        }

        return true;
    }
    /**
     * Udpate Reactions Count
     *
     * @return bool
     */
    public function updateQuestionViewCount()
    {
        $smt = 'INSERT  INTO `tbl_forum_stats` SET `fstat_record_id` = ?, `fstat_record_type` = ?, fstat_views = 1 ON DUPLICATE KEY UPDATE `fstat_views` = fstat_views + 1';
        
        $db = FatApp::GetDb();

        $smt = $db->prepareStatement($smt);
        $smt->bindParameters('ii', $this->recordId, $this->recordType);

        if (!$smt->execute()) {
            $this->error = $record->getError();
            return false;
        }

        return true;
    }

    /**
     * Set Reactions Count
     *
     * @return bool
     */
    public function setReactionCount()
    {
        // TODO need to check, this function will be not in use and need to delete
        $srch = new SearchBase(ForumReaction::DB_TBL, 'freact');
        $srch->addMultipleFields([
            'COALESCE(SUM(freact_reaction = 1), 0) AS like_count',
            'COALESCE(SUM(freact_reaction = -1), 0) AS dislike_count',
        ]);
        $srch->addCondition('freact.freact_type', '=', $this->recordType);
        $srch->addCondition('freact.freact_record_id', '=', $this->recordId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());

        $data = ['fstat_likes' => 0, 'fstat_dislikes' => 0 ];
        if (!empty($row)) {
            $data = ['fstat_likes' => $row['like_count'], 'fstat_dislikes' => $row['dislike_count'] ];
        }

        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('fstat_record_id', $this->recordId);
        $record->setFldValue('fstat_record_type', $this->recordType);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Set Comment Count
     *
     * @return bool
     */
    public function setCommentCount()
    {
        $srch = new SearchBase(ForumQuestionComment::DB_TBL, 'fcommnt');
        $srch->addMultipleFields(['COUNT(*) as total_comments']);
        $srch->addCondition('fcommnt.fquecom_fque_id', '=', $this->recordId);
        $srch->addCondition('fcommnt.fquecom_deleted', '!=', AppConstant::YES);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
 
        $data = ['fstat_comments' => 0];
        if (!empty($row)) {
            $data = ['fstat_comments' => $row['total_comments']];
        }
        $record = new TableRecord(static::DB_TBL);
        $record->setFldValue('fstat_record_id', $this->recordId);
        $record->setFldValue('fstat_record_type', $this->recordType);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    public static function getStats($recordId, $recordType)
    {
        $srch = new SearchBase(static::DB_TBL);

        if (is_array($recordId)) {
            $srch->addCondition('fstat_record_id', 'IN', $recordId);
        } else {
            $srch->addCondition('fstat_record_id', '=', $recordId);
        }
        $srch->addCondition('fstat_record_type', '=', $recordType);

        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $data = FatApp::getDb()->fetchAll($srch->getResultSet(), 'fstat_record_id');
        
        return $data;
    }
}
