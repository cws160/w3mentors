<?php

/**
 * This class is used to handle Meta Tags Search
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class MetaTagSearch extends SearchBased
{

    public function __construct(int $langId = 0, int $metaType = MetaTag::META_GROUP_DEFAULT)
    {
        parent::__construct(MetaTag::DB_TBL, 'mt');
        if ($langId > 0) {
            $this->joinTable(MetaTag::DB_LANG_TBL, 'LEFT OUTER JOIN', 'mt_l.metalang_meta_id = mt.meta_id AND mt_l.metalang_lang_id = ' . $langId, 'mt_l');
            $this->joinTable(Afile::DB_TBL, 'LEFT OUTER JOIN', 'ogimg.file_record_id = mt.meta_id AND ogimg.file_type = ' . Afile::TYPE_OPENGRAPH_IMAGE . ' AND ogimg.file_lang_id = ' . $langId, 'ogimg');
        }
    }

    /**
     * Join Teachers
     * 
     * @param int $metaType
     */
    public function joinTeachers(int $metaType = MetaTag::META_GROUP_DEFAULT)
    {
        $this->joinTable(User::DB_TBL, 'RIGHT OUTER JOIN', 'mt.meta_record_id = u.user_username AND u.user_is_teacher = 1 and u.user_deleted IS NULL and mt.meta_type=' . $metaType, 'u');
    }

    /**
     * Join Group Classes
     * 
     * @param int $metaType
     */
    public function joinGrpClasses(int $metaType = MetaTag::META_GROUP_DEFAULT)
    {
        $this->joinTable(GroupClass::DB_TBL, 'RIGHT JOIN', 'mt.meta_record_id = gcls.grpcls_slug AND mt.meta_type=' . $metaType, 'gcls');
        $this->joinTable(User::DB_TBL, 'LEFT JOIN', 'gcls.grpcls_teacher_id = u.user_id and u.user_deleted IS NULL', 'u');
        $this->addCondition('gcls.grpcls_start_datetime', '>', date('Y-m-d H:i:s'));
        $this->addCondition('gcls.grpcls_status', '=', GroupClass::SCHEDULED);
        $this->addCondition('gcls.grpcls_parent', '=', 0);
    }

    /**
     * Join CMS Page
     * 
     * @param int $langId
     * @param int $metaType
     */
    public function joinCmsPage(int $langId, int $metaType = MetaTag::META_GROUP_DEFAULT)
    {
        $this->joinTable(ContentPage::DB_TBL, 'RIGHT OUTER JOIN', 'mt.meta_record_id = cp.cpage_id AND mt.meta_type=' . $metaType, 'cp');
        $this->joinTable(ContentPage::DB_TBL_LANG, 'LEFT OUTER JOIN', 'cp_l.cpagelang_cpage_id = cp.cpage_id and cp_l.cpagelang_lang_id=' . $langId, 'cp_l');
    }

    /**
     * Join Blog Categories
     * 
     * @param int $langId
     * @param int $metaType
     */
    public function joinBlogCategories(int $langId, int $metaType = MetaTag::META_GROUP_DEFAULT)
    {
        $this->joinTable(BlogPostCategory::DB_TBL, 'RIGHT OUTER JOIN', 'mt.meta_record_id = bpc.bpcategory_id AND mt.meta_type=' . $metaType, 'bpc');
        $this->joinTable(BlogPostCategory::DB_TBL_LANG, 'LEFT OUTER JOIN', 'bpc.bpcategory_id = bpcl.bpcategorylang_bpcategory_id and bpcl.bpcategorylang_lang_id=' . $langId, 'bpcl');
    }

    /**
     * Join Blog Posts
     * 
     * @param int $langId
     * @param int $metaType
     */
    public function joinBlogPosts(int $langId, int $metaType = MetaTag::META_GROUP_DEFAULT)
    {
        $this->joinTable(BlogPost::DB_TBL, 'RIGHT OUTER JOIN', 'mt.meta_record_id = bp.post_id AND mt.meta_type=' . $metaType, 'bp');
        $this->joinTable(BlogPost::DB_LANG_TBL, 'LEFT OUTER JOIN', 'bpl.postlang_post_id = bp.post_id and bpl.postlang_lang_id=' . $langId, 'bpl');
    }

    /**
     * Join Courses
     * 
     * @param int $langId
     * @param int $metaType
     */
    public function joinCourses(int $langId, int $metaType = MetaTag::META_GROUP_DEFAULT)
    {
        $this->joinTable(Course::DB_TBL, 'RIGHT OUTER JOIN', 'mt.meta_record_id = crs.course_slug AND mt.meta_type=' . $metaType, 'crs');
        $this->joinTable(Course::DB_TBL_LANG, 'LEFT OUTER JOIN', 'crsdetail.course_id = crs.course_id', 'crsdetail');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'crs.course_user_id = teacher.user_id', 'teacher');
        $this->joinTable(Category::DB_TBL, 'INNER JOIN', 'cate.cate_id = crs.course_cate_id', 'cate');
        $this->joinTable(CourseLanguage::DB_TBL, 'INNER JOIN', 'clang.clang_id = crs.course_clang_id', 'clang');
    }

    /**
     * Join TeachLanguge
     * 
     * @param int $langId
     * @param int $metaType
     */
    public function joinTeachLanguge(int $langId, int $metaType = MetaTag::META_GROUP_TEACH_LANGUAGE)
    {
        $this->joinTable(TeachLanguage::DB_TBL, 'RIGHT OUTER JOIN', 'mt.meta_record_id = tlang.tlang_slug AND mt.meta_type=' . $metaType, 'tlang');
        $this->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT OUTER JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id AND tlanglang.tlanglang_lang_id = '.$langId, 'tlanglang');
    }

    /**
     * Search By Criteria
     * 
     * @param array $criteria
     * @param int $langId
     */
    public function searchByCriteria(array $criteria, int $langId)
    {
        $metaType = $criteria['metaType']['val'];
        if (isset($criteria['keyword']['val']) && $criteria['keyword']['val']) {
            $condition = $this->addCondition('mt.meta_identifier', 'like', '%' . $criteria['keyword']['val'] . '%');
            $condition->attachCondition('mt_l.meta_title', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
        }
        switch ($metaType) {
            case MetaTag::META_GROUP_CMS_PAGE:
                $this->joinCmsPage($langId, $criteria['metaType']['val']);
                $this->addCondition('cpage_deleted', '=', 0);
                if (isset($condition) && $condition) {
                    $condition->attachCondition('cp.cpage_identifier', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                }
                $this->addOrder('cp.cpage_id', 'DESC');
                break;
            case MetaTag::META_GROUP_TEACHER:
                $this->joinTeachers($metaType);
                $this->addCondition('u.user_is_teacher', '=', AppConstant::YES, 'AND');
                $this->addDirectCondition('u.user_deleted IS NULL');
                $this->addCondition('u.user_username', 'is not', 'mysql_func_null', 'and', true);
                if (isset($condition) && $condition) {
                    $condition->attachCondition('u.user_first_name', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                    $condition->attachCondition('u.user_last_name', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                    $condition->attachCondition('mysql_func_concat(u.user_first_name," ",u.user_last_name)', ' like', '%' . $criteria['keyword']['val'] . '%', 'OR', true);
                }
                $this->addOrder('u.user_id', 'DESC');
                break;
            case MetaTag::META_GROUP_GRP_CLASS:
                $this->joinGrpClasses($metaType);
                $this->addDirectCondition('u.user_deleted IS NULL');
                if (isset($condition) && $condition) {
                    $condition->attachCondition('gcls.grpcls_title', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                }
                $this->addOrder('gcls.grpcls_id', 'DESC');
                break;
            case MetaTag::META_GROUP_BLOG_POST:
                $this->joinBlogPosts($langId, $metaType);
                $this->addCondition('post_deleted', '=', 0);
                if (isset($condition) && $condition) {
                    $condition->attachCondition('bp.post_identifier', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                    $condition->attachCondition('bpl.post_title', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                }
                $this->addOrder('bp.post_id', 'DESC');
                break;
            case MetaTag::META_GROUP_BLOG_CATEGORY:
                $this->joinBlogCategories($langId, $metaType);
                $this->addCondition('bpcategory_deleted', '=', 0);
                if (isset($condition) && $condition) {
                    $condition->attachCondition('bpc.bpcategory_identifier', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                    $condition->attachCondition('bpcl.bpcategory_name', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                }
                $this->addOrder('bpc.bpcategory_id', 'DESC');
                break;
            default:
                if (!empty($post['keyword'])) {
                    $condition = $this->addCondition('mt.meta_identifier', 'like', '%' . $criteria['keyword']['val'] . '%');
                    $condition->attachCondition('mt_l.meta_title', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                }
                $this->addCondition('mt.meta_type', '=', $metaType);
                break;
            case MetaTag::META_GROUP_COURSE:
                $this->joinCourses($langId, $metaType);
                $this->addCondition('course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
                $this->addCondition('course_active', '=', AppConstant::ACTIVE);
                $this->addCondition('course_status', '=', Course::PUBLISHED);
                $this->addCondition('cate.cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
                $this->addCondition('cate.cate_status', '=', AppConstant::ACTIVE);
                $this->addCondition('teacher.user_username', '!=', "");
                $this->addDirectCondition('teacher.user_deleted IS NULL');
                $this->addDirectCondition('teacher.user_verified IS NOT NULL');
                $this->addCondition('teacher.user_active', '=', AppConstant::ACTIVE);
                $this->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
                if (isset($condition) && $condition) {
                    $condition->attachCondition('crsdetail.course_title', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                }
                $this->addOrder('crs.course_id', 'DESC');
                break;
            case MetaTag::META_GROUP_TEACH_LANGUAGE:
                $this->joinTeachLanguge($langId, $metaType);
                if (isset($condition) && $condition) {
                    $condition->attachCondition('tlang.tlang_identifier', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                    $condition->attachCondition('tlang.tlang_slug', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                    $condition->attachCondition('tlanglang.tlang_name', 'like', '%' . $criteria['keyword']['val'] . '%', 'OR');
                }
                $this->addOrder('tlang.tlang_slug');
                break;
        }
        if (isset($criteria['hasTagsAssociated'])) {
            if ($criteria['hasTagsAssociated']['val'] == AppConstant::YES) {
                $this->addCondition('mt.meta_id', 'is not', 'mysql_func_NULL', 'AND', true);
            } else {
                $this->addCondition('mt.meta_id', 'is', 'mysql_func_NULL', 'AND', true);
            }
        }
    }

}
