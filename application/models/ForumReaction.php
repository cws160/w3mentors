<?php

/**
 * This class is used for Forum Reactions
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumReaction extends MyAppModel
{

    private $userId;

    const DB_TBL = 'tbl_forum_reactions';
    const DB_TBL_PREFIX = 'freact_';
    const REACT_TYPE_QUESTION = 1;
    const REACT_TYPE_COMMENT = 2;
    const REACTION_LIKE = 1;
    const REACTION_DISLIKE = -1;

    /**
     * Initialize ForumReaction Class
     *
     * @param int $forumQueCommId
     */
    public function __construct(int $userId, int $recordId, int $reactType, int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'freact_id', $id);
        $this->userId = $userId;
        $this->recordId = $recordId;
        $this->reactType = $reactType;
    }

    /**
     * Get Reaction Type
     *
     * return type array
     */
    public static function getReactionTypeArray(): array
    {
        return [
            static::REACT_TYPE_QUESTION => Label::getLabel('LBL_Question'),
            static::REACT_TYPE_COMMENT => Label::getLabel('LBL_Comment'),
        ];
    }

    public function getRecord(): array
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('freact_record_id', '=', $this->recordId);
        $srch->addCondition('freact_type', '=', $this->reactType);
        $srch->addCondition('freact_user_id', '=', $this->userId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet()) ?? [];
    }

    public function getLoggedUserReactions(array $recordIds)
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('freact_record_id', 'IN', $recordIds);
        $srch->addCondition('freact_type', '=', $this->reactType);
        $srch->addCondition('freact_user_id', '=', $this->userId);
        $srch->addMultipleFields(['freact_record_id', 'freact_reaction']);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(count($recordIds));
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'freact_record_id');
    }

}
