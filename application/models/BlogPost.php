<?php

/**
 * This class is used to handle Blog Posts
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class BlogPost extends MyAppModel
{

    const DB_TBL = 'tbl_blog_post';
    const DB_TBL_PREFIX = 'post_';
    const DB_LANG_TBL = 'tbl_blog_post_lang';
    const DB_LANG_TBL_PREFIX = 'postlang_';
    const DB_POST_TO_CAT_TBL = 'tbl_blog_post_to_category';
    const DB_POST_TO_CAT_TBL_PREFIX = 'ptc_';
    /* Post Status */
    const IN_DRAFT = 0;
    const PUBLISHED = 1;
    /* Comment Status */
    const ACTIVE = 1;
    const INACTIVE = 0;
    /* Contribution Status */
    const CONTRI_PENDING = 0;
    const CONTRI_APPROVED = 1;
    const CONTRI_POSTED = 2;
    const CONTRI_REJECTED = 3;

    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'post_id', $id);
    }

    public static function getStatuses(int $key = null)
    {
        $arr = [
            static::IN_DRAFT => Label::getLabel('LBL_IN_DRAFT'),
            static::PUBLISHED => Label::getLabel('LBL_PUBLISHED'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public static function getCommentStatuses(int $key = null)
    {
        $arr = [
            static::ACTIVE => Label::getLabel('LBL_Approved'),
            static::INACTIVE => Label::getLabel('LBL_Pending'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    public static function getContriStatuses(int $key = null, int $langId = 0)
    {
        $arr = [
            static::CONTRI_PENDING => Label::getLabel('LBL_PENDING', $langId),
            static::CONTRI_APPROVED => Label::getLabel('LBL_APPROVED', $langId),
            static::CONTRI_POSTED => Label::getLabel('LBL_POSTED', $langId),
            static::CONTRI_REJECTED => Label::getLabel('LBL_REJECTED', $langId),
        ];
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $joinCategory
     * @param bool $post_published
     * @param bool $categoryActive
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0, bool $joinCategory = true, bool $post_published = false, bool $categoryActive = false): SearchBase
    {
        $srch = new SearchBased(static::DB_TBL, 'bp');
        if ($joinCategory) {
            $srch->joinTable(static::DB_POST_TO_CAT_TBL, 'LEFT OUTER JOIN', 'bptc.ptc_post_id = bp.post_id', 'bptc');
            $srch->joinTable(BlogPostCategory::DB_TBL, 'LEFT OUTER JOIN', 'bptc.ptc_bpcategory_id = bpc.bpcategory_id and bpc.bpcategory_deleted = 0', 'bpc');
            if ($langId > 0) {
                $srch->joinTable(BlogPostCategory::DB_TBL_LANG, 'LEFT OUTER JOIN', 'bpc_l.bpcategorylang_bpcategory_id = bpc.bpcategory_id and bpc_l.bpcategorylang_lang_id = ' . $langId, 'bpc_l');
            }
        }
        if ($categoryActive) {
            $srch->addCondition('bpc.bpcategory_active', '=', AppConstant::ACTIVE);
        }
        if ($post_published) {
            $srch->addCondition('bp.post_published', '=', AppConstant::ACTIVE);
        }
        $srch->addCondition('bp.post_deleted', '=', AppConstant::NO);
        return $srch;
    }

    /**
     * Get Blog Posts Under Category
     * 
     * @param int $langId
     * @param int $bpcategory_id
     * @return array
     */
    public static function getPostsCount(int $langId, int $bpcategory_id, $blogChild = []): int
    {
        $srch = BlogPost::getSearchObject($langId, true, true, true);
        $srch->addCondition('ptc_bpcategory_id', '=', $bpcategory_id);
        $srch->joinTable(BlogPost::DB_LANG_TBL, 'INNER JOIN', 'bp_l.postlang_post_id = bp.post_id and bp_l.postlang_lang_id = ' . $langId, 'bp_l');
        if(count($blogChild)>0) {
            $id = implode(',', array_column($blogChild, 'bpcategory_id'));
            $srchPost = new SearchBase(BlogPost::DB_POST_TO_CAT_TBL);
            $srchPost->addDirectCondition('ptc_bpcategory_id  IN ('.$id.')');
            $postData = FatApp::getDb()->fetchAll($srchPost->getResultSet());
            $postIds = implode(',', array_column($postData, 'ptc_post_id'));
            ($postIds) ? $srch->addDirectCondition('ptc_post_id  NOT IN (' . $postIds . ')'): '';
        }
        $srch->addGroupby('ptc_bpcategory_id');
        $srch->addFld('COUNT(*) as posts');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        return FatUtility::int($row['posts'] ?? 0);
    }

    public function updateImagesOrder(int $postId, array $order)
    {
        if (is_array($order) && sizeof($order) > 0) {
            foreach ($order as $i => $id) {
                if (FatUtility::int($id) < 1) {
                    continue;
                }
                FatApp::getDb()->updateFromArray('tbl_attached_files', ['afile_order' => $i], [
                    'smt' => 'afile_type = ? AND afile_record_id = ? AND afile_id = ?',
                    'vals' => [Afile::TYPE_BLOG_POST_IMAGE, $postId, $id]
                ]);
            }
            return true;
        }
        return false;
    }

    /**
     * Get Post Categories
     * 
     * @param int $postId
     * @return array
     */
    public function getPostCategories(int $postId): array
    {
        $srch = new SearchBase(static::DB_POST_TO_CAT_TBL, 'ptc');
        $srch->addCondition('ptc_post_id', '=', $postId);
        $srch->joinTable(BlogPostCategory::DB_TBL, 'INNER JOIN', 'bpcategory_id = ptc.ptc_bpcategory_id', 'cat');
        $srch->addMultipleFields(['bpcategory_id']);
        $rs = $srch->getResultSet();
        return FatApp::getDb()->fetchAll($rs);
    }

    /**
     * Add Update Categories
     * 
     * @param int $postId
     * @param array $categories
     * @return bool
     */
    public function addUpdateCategories(int $postId, array $categories = []): bool
    {
        if (!$postId) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        FatApp::getDb()->deleteRecords(static::DB_POST_TO_CAT_TBL,
                ['smt' => 'ptc_post_id = ?', 'vals' => [$postId]]);
        if (empty($categories)) {
            return true;
        }
        $record = new TableRecord(static::DB_POST_TO_CAT_TBL);
        foreach ($categories as $category_id) {
            $to_save_arr = [];
            $to_save_arr['ptc_post_id'] = $postId;
            $to_save_arr['ptc_bpcategory_id'] = $category_id;
            $record->assignValues($to_save_arr);
            if (!$record->addNew([], $to_save_arr)) {
                $this->error = $record->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Delete Blog Post Image
     * 
     * @param int $postId
     * @param int $imageId
     * @return boolean
     */
    public function deleteBlogPostImage(int $postId, int $imageId)
    {
        if ($postId < 1 || $imageId < 1) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $file = new Afile(Afile::TYPE_BLOG_POST_IMAGE);
        if (!$file->removeById($postId, $imageId)) {
            $this->error = $file->getError();
            return false;
        }
        return true;
    }

    /**
     * Get Blogs For Grids
     * 
     * @return array
     */
    public static function getBlogsForGrids(int $langId): array
    {
        $srch = BlogPost::getSearchObject($langId, true, true);
        $srch->joinTable(BlogPost::DB_LANG_TBL, 'INNER JOIN', 'bp_l.postlang_post_id = bp.post_id and bp_l.postlang_lang_id = ' . $langId, 'bp_l');
        $srch->joinTable(Afile::DB_TBL, 'INNER  JOIN', 'file.file_record_id = bp.post_id and file_type =' . Afile::TYPE_BLOG_POST_IMAGE, 'file');
        $srch->addCondition('bpc.bpcategory_active', '=', self::ACTIVE);
        $srch->doNotCalculateRecords();
        $srch->addGroupBy('bp.post_id');
        $srch->addOrder('bp.post_published', 'DESC');
        $srch->addOrder('bp.post_id', 'DESC');
        $srch->setPageSize(4);
        $posts = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($posts as $key => $post) {
            $posts[$key]['post_published_on'] = MyDate::convert($post['post_published_on']);
        }
        return $posts;
    }

}
