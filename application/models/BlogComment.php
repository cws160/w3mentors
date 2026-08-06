<?php

/**
 * This class is used to handle Blog Comments
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class BlogComment extends MyAppModel
{

    const DB_TBL = 'tbl_blog_post_comments';
    const DB_TBL_PREFIX = 'bpcomment_';
    const STATUS_APPROVED = 1;
    const STATUS_PENDING = 0;

    /**
     * Initialize Blog Comment
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'bpcomment_id', $id);
        $this->db = FatApp::getDb();
    }

    /**
     * Get Search Object
     * 
     * @param bool $joinBlogPost
     * @param int $langId
     * @return SearchBase
     */
    public static function getSearchObject(int $langId): SearchBase
    {
        $srch = new SearchBased(static::DB_TBL);
        $srch->joinTable(BlogPost::DB_TBL, 'left outer join', 'bpcomment_post_id = post_id');
        $srch->joinTable(BlogPost::DB_LANG_TBL, 'left outer join', 'post_id = postlang_post_id and postlang_lang_id = ' . $langId);
        $srch->addCondition('bpcomment_deleted', '=', AppConstant::NO);
        return $srch;
    }

    /**
     * Can Mark Record Deleted
     * 
     * @param int $bpcId
     * @return bool
     */
    public function canMarkRecordDelete(int $bpcId): bool
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('bpcomment_deleted', '=', AppConstant::NO);
        $srch->addCondition('bpcomment_id', '=', $bpcId);
        $srch->addFld('bpcomment_id');
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return empty($row) ? false : true;
    }

}
