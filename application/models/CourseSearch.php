<?php

/**
 * This class is used to handle Courses search
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseSearch extends W3mentorsSearch
{

    /**
     * Initialize Course Search
     * 
     * @param int $langId
     * @param int $userId
     * @param int $userType
     */
    public function __construct(int $langId, int $userId, int $userType)
    {
        $this->table = Course::DB_TBL;
        $this->alias = 'course';

        parent::__construct($langId, $userId, $userType);
        $this->joinTable(Course::DB_TBL_LANG, 'LEFT JOIN', 'crsdetail.course_id = course.course_id', 'crsdetail');
        $this->joinTable(User::DB_TBL, 'INNER JOIN', 'course.course_user_id = teacher.user_id', 'teacher');
        $this->joinTable(Category::DB_TBL, 'LEFT JOIN', 'cate.cate_id = course.course_cate_id', 'cate');
        $this->joinTable(CourseLanguage::DB_TBL, 'LEFT JOIN', 'clang.clang_id = course.course_clang_id', 'clang');
    }

    /**
     * Add Search Listing Fields
     *
     * @return void
     */
    public function addSearchListingFields(): void
    {
        $fields = static::getListingFields();
        foreach ($fields as $field => $alias) {
            $this->addFld($field . ' AS ' . $alias);
        }
    }

    /**
     * Get Detail Fields
     * 
     * @return array
     */
    public static function getDetailFields(): array
    {
        $fields = [
            'testat.testat_ratings' => 'testat_ratings',
            'testat.testat_reviewes' => 'testat_reviewes',
            'testat.testat_students' => 'testat_students',
            'testat.testat_courses' => 'testat_courses',
            'IFNULL(userlang.user_biography, "")' => 'user_biography',
        ];
        return array_merge($fields, static::getListingFields());
    }

    /**
     * Get Listing FFields
     *
     * @return array
     */
    public static function getListingFields(): array
    {
        return [
            'course.course_id' => 'course_id',
            'course.course_user_id' => 'course_teacher_id',
            'course.course_slug' => 'course_slug',
            'course.course_status' => 'course_status',
            'course.course_active' => 'course_active',
            'course.course_cate_id' => 'course_cate_id',
            'course.course_subcate_id' => 'course_subcate_id',
            'course.course_clang_id' => 'course_clang_id',
            'course.course_price' => 'course_price',
            'course.course_type' => 'course_type',
            'course.course_duration' => 'course_duration',
            'course.course_created' => 'course_created',
            'course.course_sections' => 'course_sections',
            'course.course_lectures' => 'course_lectures',
            'course.course_reviews' => 'course_reviews',
            'course.course_students' => 'course_students',
            'course.course_ratings' => 'course_ratings',
            'course.course_certificate' => 'course_certificate',
            'course.course_certificate_type' => 'course_certificate_type',
            'course.course_quilin_id' => 'course_quilin_id',
            'course.course_level' => 'course_level',
            'course.course_currency_id' => 'course_currency_id',
            'course.course_preview_video' => 'course_preview_video',
            'crsdetail.course_title' => 'course_title',
            'crsdetail.course_subtitle' => 'course_subtitle',
            'IFNULL(crsdetail.course_srchtags, "")' => 'course_srchtags',
            'crsdetail.course_details' => 'course_details',
            'crsdetail.course_welcome' => 'course_welcome',
            'crsdetail.course_congrats' => 'course_congrats',
            'teacher.user_id' => 'teacher_id',
            'teacher.user_first_name' => 'teacher_first_name',
            'teacher.user_last_name' => 'teacher_last_name',
            'teacher.user_username' => 'teacher_username',
            'clang.clang_identifier' => 'clang_identifier',
            'course.course_updated' => 'course_updated'
        ];
    }

    /**
     * Apply Search Conditions
     *
     * @param array $post
     * @return void
     */
    public function applySearchConditions(array $post): void
    {
        if (!empty($post['keyword'])) {
            $keyword = trim($post['keyword']);
            $cnd = $this->addCondition('crsdetail.course_title', 'LIKE', '%' . $keyword . '%');
            $cnd->attachCondition('teacher.user_first_name', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('teacher.user_last_name', 'LIKE', '%' . $keyword . '%', 'OR');
            $cnd->attachCondition('mysql_func_CONCAT(teacher.user_first_name, " ", teacher.user_last_name)', 'LIKE', '%' . $keyword . '%', 'OR', true);
        }
        if (!empty($post['course_status'])) {
            $this->addCondition('course.course_status', '=', $post['course_status']);
        }
        if (!empty($post['course_addedon_from'])) {
            $start = $post['course_addedon_from'] . ' 00:00:00';
            $this->addCondition('course.course_created', '>=', MyDate::formatToSystemTimezone($start));
        }
        if (!empty($post['course_addedon_till'])) {
            $end = $post['course_addedon_till'] . ' 23:59:59';
            $this->addCondition('course.course_created', '<=', MyDate::formatToSystemTimezone($end));
        }
        /* for frontend filters */
        if (!empty($post['course_cate_id'])) {
            if (is_array($post['course_cate_id'])) {
                $cnd = $this->addCondition('course.course_cate_id', 'IN', $post['course_cate_id']);
                $cnd->attachCondition('course.course_subcate_id', 'IN', $post['course_cate_id'], 'OR');
            } else {
                $cnd = $this->addCondition('course.course_cate_id', '=', $post['course_cate_id']);
                $cnd->attachCondition('course.course_subcate_id', '=', $post['course_cate_id'], 'OR');
            }
        }
        /* for dashboard filters [ */
        if (isset($post['course_cateid']) && !empty($post['course_cateid'])) {
            $this->addCondition('course.course_cate_id', '=', $post['course_cateid']);
        }
        if (isset($post['course_subcateid']) && !empty($post['course_subcateid'])) {
            $this->addCondition('course.course_subcate_id', '=', $post['course_subcateid']);
        }
        /* ] */
        if (isset($post['course_id']) && !empty($post['course_id'])) {
            $this->addCondition('course.course_id', '=', $post['course_id']);
        }
        if (isset($post['course_level']) && count($post['course_level']) > 0) {
            $this->addCondition('course.course_level', 'IN', $post['course_level']);
        }
        if (isset($post['course_clang_id']) && !empty($post['course_clang_id'])) {
            if (is_array($post['course_clang_id']) && count($post['course_clang_id']) > 0) {
                $this->addCondition('course.course_clang_id', 'IN', $post['course_clang_id']);
            } elseif (!is_array($post['course_clang_id']) && $post['course_clang_id'] > 0) {
                $this->addCondition('course.course_clang_id', '=', $post['course_clang_id']);
            }
        } elseif (!empty($post['course_clang'])) {
            $this->joinTable(CourseLanguage::DB_TBL_LANG, 'LEFT JOIN', 'clanglang.clanglang_clang_id = '
                . ' course.course_clang_id AND clanglang.clanglang_lang_id = ' . $this->langId, 'clanglang');
            $this->addCondition('clanglang.clang_name', 'LIKE', '%' . trim($post['course_clang']) . '%');
        }
        $pricesql = [];

        $customPriceSql = [];
        if (isset($post['price_from']) && $post['price_from'] !== '') {
            $priceFrom = FatUtility::float($post['price_from']);
            $customPriceSql[] = 'ROUND(course.course_price, 2) >= ' . (MyUtility::convertToSystemCurrency($priceFrom));
        }
        if (isset($post['price_till']) && $post['price_till'] !== '') {
            $priceTill = FatUtility::float($post['price_till']);
            $customPriceSql[] = 'ROUND(course.course_price, 2) <= ' . (MyUtility::convertToSystemCurrency($priceTill));
        }
        if (count($customPriceSql) > 0) {
            array_push($pricesql, ' ( ' . implode(' AND ', $customPriceSql) . ' ) ');
        }
        if (count($pricesql) > 0) {
            $this->addDirectCondition(' ( ' . implode(' OR ', $pricesql) . ' ) ');
        }
        if (isset($post['user_id']) && $post['user_id'] > 0) {
            $this->addCondition('course.course_user_id', '=', $post['user_id']);
        }
        if (isset($post['type']) && $post['type'] > 0 && !empty($post['record_id'])) {
            if ($post['type'] == Course::FILTER_COURSE) {
                $this->addCondition('course.course_id', '=', $post['record_id']);
            }
            if ($post['type'] == Course::FILTER_TEACHER) {
                $this->addCondition('course.course_user_id', '=', $post['record_id']);
            }
            if ($post['type'] == Course::FILTER_TAGS) {
                $this->addDirectCondition('JSON_CONTAINS(course_srchtags, ' . '\'"' . $post['record_id'] . '"\')');
            }
        }
        if (isset($post['course_ratings']) && $post['course_ratings'] > 0) {
            $this->addCondition('course.course_ratings', '>=', $post['course_ratings']);
        }
        if (isset($post['course_type']) && $post['course_type'] > 0) {
            $this->addCondition('course.course_type', '=', $post['course_type']);
        }
    }

    /**
     * Apply Order By
     * 
     * @param int $sorting
     * @return void
     */
    public function applyOrderBy(int $sorting): void
    {
        switch ($sorting) {
            case AppConstant::SORT_PRICE_ASC:
                $this->addOrder('course.course_price', 'ASC');
                $this->addOrder('course.course_reviews', 'DESC');
                $this->addOrder('course.course_ratings', 'DESC');
                break;
            case AppConstant::SORT_PRICE_DESC:
                $this->addOrder('course.course_price', 'DESC');
                $this->addOrder('course.course_reviews', 'DESC');
                $this->addOrder('course.course_ratings', 'DESC');
                break;
            case AppConstant::SORT_POPULARITY:
                $this->addOrder('course.course_students', 'DESC');
                $this->addOrder('course.course_reviews', 'DESC');
                $this->addOrder('course.course_ratings', 'DESC');
                break;
            default:
                $this->addOrder('course.course_students', 'DESC');
                $this->addOrder('course.course_reviews', 'DESC');
                $this->addOrder('course.course_ratings', 'DESC');
                $this->addOrder('course.course_price', 'ASC');
                break;
        }
    }

    /**
     * Apply Primary Conditions
     *
     * @return void
     */
    public function applyPrimaryConditions(): void
    {
        $this->addCondition('course.course_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $this->addDirectCondition('teacher.user_deleted IS NULL');
        $this->addDirectCondition('teacher.user_verified IS NOT NULL');
        $this->addCondition('teacher.user_active', '=', AppConstant::ACTIVE);
        $this->addCondition('teacher.user_is_teacher', '=', AppConstant::YES);
        if ($this->userType == User::TEACHER) {
            $this->addCondition('course.course_user_id', '=', $this->userId);
        }
    }

    /**
     * Fetch And Format
     *
     * @param bool $single
     * @return array
     */
    public function fetchAndFormat(bool $single = false): array
    {
        $rows = FatApp::getDb()->fetchAll($this->getResultSet(), 'course_id');
        if (count($rows) == 0) {
            return [];
        }
        $courseIds = array_keys($rows);
        $teachLangIds = array_column($rows, 'course_clang_id');
        $teacherIds = array_unique(array_column($rows, 'course_teacher_id'));
        $catIds = array_column($rows, 'course_cate_id');
        $subcatIds = array_column($rows, 'course_subcate_id');
        $categoryIds = array_unique(array_merge($catIds, $subcatIds));
        $favorites = $this->getFavoriteCourses($this->userId, $courseIds);
        $purchasedCourses = $this->getPurchasedCourses($this->userId, $courseIds);
        $teachLangs = $this->getTeachLangs($this->langId, $teachLangIds);
        $categories = $this->getCategoryNames($this->langId, $categoryIds);
        $editRequests = $this->getEditRequests($courseIds);
        $profileStatus = User::isTeacherProfileComplete($teacherIds);
        foreach ($rows as $key => $row) {
            $row['can_send_edit_request'] = $this->canSendEditRequest($row) && !isset($editRequests[$row['course_id']]);
            $row['can_edit_course'] = $this->canEdit($row, $editRequests) || isset($editRequests[$row['course_id']]);
            $row['can_delete_course'] = $this->canDelete($row);
            $row['can_cancel_course'] = false;
            $row['can_rate_course'] = false;
            $row['can_retake_course'] = false;
            $row['can_download_certificate'] = false;
            $row['is_favorite'] = isset($favorites[$key]) ? AppConstant::YES : AppConstant::NO;
            $row['is_purchased'] = isset($purchasedCourses[$key]);
            $row['ordcrs_id'] = isset($purchasedCourses[$key]) ? $purchasedCourses[$key]['ordcrs_id'] : 0;
            $row['course_clang_name'] = $teachLangs[$row['course_clang_id']] ?? $row['clang_identifier'];
            $row['cate_name'] = $categories[$row['course_cate_id']] ?? '';
            $row['subcate_name'] = $categories[$row['course_subcate_id']] ?? '';
            $row['is_profile_complete'] = $profileStatus[$row['course_teacher_id']] ?? '';
            $row['course_tags'] = json_decode($row['course_srchtags']);
            $rows[$key] = $row;
        }
        return $rows;
    }

    /**
     * Get Search Form
     * 
     * @param int $langId
     * @return Form
     */
    public static function getSearchForm($langId)
    {
        $frm = new Form('frmSearch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addSelectBox(Label::getLabel('LBL_SEARCH_KEYWORD'), 'keyword', [], '', [], '');
        $frm->addHiddenField('', 'record_id', '');
        $frm->addHiddenField('', 'type', '');
        $frm->addHiddenField('', 'search_keyword', '');
        $frm->addRadioButtons(Label::getLabel('LBL_SORT_BY'), 'price_sorting', AppConstant::getSortbyArr(), AppConstant::SORT_POPULARITY);
        $categories = Category::getAll(Category::TYPE_COURSE, $langId);
        $frm->addCheckBoxes(Label::getLabel('LBL_CATEGORIES'), 'course_cate_id', $categories);
        $frm->addCheckBoxes(Label::getLabel('LBL_DIFFICULTY'), 'course_level', Course::getCourseLevels());
        $frm->addRadioButtons(Label::getLabel('LBL_RATING'), 'course_ratings', Course::getRatingFilters());
        $frm->addCheckBoxes(Label::getLabel('LBL_COURSE_LANGUAGE'), 'course_clang_id', (new CourseLanguage())->getAllLangs($langId, true));
        $frm->addTextBox(Label::getLabel('LBL_PRICE_FROM'), 'price_from', '')->requirements()->setFloatPositive();
        $frm->addTextBox(Label::getLabel('LBL_PRICE_TILL'), 'price_till', '')->requirements()->setFloatPositive();
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        return $frm;
    }

    /**
     * Get More Courses from Teacher
     * 
     * @param int $teacherId
     * @param int $courseId
     * @return array
     */
    public function getMoreCourses(int $teacherId, int $courseId = 0): array
    {
        $this->addSearchListingFields();
        $this->applyPrimaryConditions();
        $this->addCondition('course.course_id', '!=', $courseId);
        $this->addCondition('user_id', '=', $teacherId);
        $this->addCondition('course.course_status', '=', Course::PUBLISHED);
        $this->addCondition('course.course_active', '=', AppConstant::ACTIVE);
        $this->applyOrderBy(0);
        $this->setPageSize(AppConstant::PAGESIZE);
        return $this->fetchAndFormat();
    }

    /**
     * Get Teachers Teaching Lang
     * 
     * @param int $langId
     * @param array $teachLangIds
     * @return array
     */
    public static function getTeachLangs(int $langId, array $teachLangIds): array
    {
        if ($langId == 0 || count($teachLangIds) == 0) {
            return [];
        }
        $srch = new SearchBase(CourseLanguage::DB_TBL);
        $srch->joinTable(
            CourseLanguage::DB_TBL_LANG,
            'LEFT JOIN',
            'clang_id = clanglang.clanglang_clang_id AND clanglang.clanglang_lang_id = ' . $langId,
            'clanglang'
        );
        $srch->addMultipleFields(['clang_id', 'IFNULL(clang_name, clang_identifier) AS clang_name']);
        $srch->addDirectCondition('clang_id IN (' . implode(',', $teachLangIds) . ')');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }


    /**
     * Get Recent Edit Request
     * @param array $courseIds
     * @return array
     */
    public static function getEditRequests(array $courseIds): array
    {
        $srch = new SearchBase(Course::DB_TBL_EDIT_REQUEST, 'edit_req');
        $srch->addMultipleFields(['coedre_course_id', 'coedre_status', 'coedre_updated', 'coedre_id', ' coedre_updates_done', 'coedre_duration']);
        $srch->addDirectCondition('edit_req.coedre_course_id IN (' . implode(',', $courseIds) . ')');
        $srch->addCondition('coedre_recent', '=', AppConstant::YES);
        $srch->doNotCalculateRecords();
        $records = FatApp::getDb()->fetchAll($srch->getResultSet());
        $editRequests = [];
        foreach ($records as $record) {
            $allowEditDate = date('Y-m-d H:i:s', strtotime($record['coedre_updated'] . ' + ' . $record['coedre_duration'] . ' days'));
            if ($record['coedre_status'] == Course::EDIT_REQUEST_APPROVED && $allowEditDate > date('Y-m-d H:i:s') && $record['coedre_updates_done'] == Course::EDIT_UPDATES_INPROGRESS) {
                $editRequests[$record['coedre_course_id']] = true;
            }
        }
        return $editRequests;
    }

    /**
     * Get Categories Name
     * 
     * @param int $langId
     * @param array $categoryIds
     * @return array
     */
    public static function getCategoryNames(int $langId, array $categoryIds): array
    {
        if ($langId == 0 || count($categoryIds) == 0) {
            return [];
        }
        $srch = new SearchBase(Category::DB_TBL);
        $srch->joinTable(Category::DB_LANG_TBL, 'LEFT JOIN', 'cate_id = catelang_cate_id AND catelang_lang_id = ' . $langId);
        $srch->addMultipleFields(['cate_id', 'IFNULL(cate_name, cate_identifier) AS cate_name']);
        $srch->addCondition('cate_id', 'IN', $categoryIds);
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('cate_status', '=', AppConstant::ACTIVE);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get favorite courses
     *
     * @param int   $userId
     * @param array $courseIds
     * @return boolean
     */
    private function getFavoriteCourses(int $userId, array $courseIds)
    {
        $srch = new SearchBase(User::DB_TBL_COURSE_FAVORITE, 'ufc');
        $srch->addDirectCondition('ufc_course_id IN (' . implode(',', $courseIds) . ')');
        $srch->addCondition('ufc_user_id', '=', $userId);
        $srch->addFld('ufc_course_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'ufc_course_id');
    }

    /**
     * Check courses purchased
     *
     * @param int   $userId
     * @param array $courseIds
     * @return boolean
     */
    public static function getPurchasedCourses(int $userId, array $courseIds)
    {
        $srch = new SearchBase(OrderCourse::DB_TBL, 'ordcrs');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordcrs.ordcrs_order_id', 'orders');
        $srch->addMultipleFields(['ordcrs_course_id', 'ordcrs_id']);
        $srch->addDirectCondition('ordcrs_course_id IN (' . implode(',', $courseIds) . ')');
        $srch->addCondition('orders.order_user_id', '=', $userId);
        $srch->addCondition('order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('ordcrs_status', '!=', OrderCourse::CANCELLED);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'ordcrs_course_id');
    }

    /**
     * Get More Courses from Teacher
     * 
     * @return array
     */
    public function getPopularCourses(): array
    {
        /* get featured categories */
        $srch = new SearchBase(Category::DB_TBL);
        $srch->joinTable(Category::DB_LANG_TBL, 'LEFT JOIN', 'cate_id = catelang_cate_id AND catelang_lang_id = ' . $this->langId);
        $srch->addMultipleFields(['cate_id', 'IFNULL(cate_name, cate_identifier) AS cate_name']);
        $srch->addCondition('cate_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addCondition('cate_status', '=', AppConstant::ACTIVE);
        $srch->addCondition('cate_featured', '=', AppConstant::YES);
        $srch->addOrder('cate_order', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(50);
        $categories = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        if (empty($categories)) {
            return [];
        }
        $data = ['categories' => $categories, 'courses' => []];
        foreach ($categories as $id => $name) {
            $crsSrch = clone $this;
            $crsSrch->addSearchListingFields();
            $crsSrch->applyPrimaryConditions();
            $crsSrch->applyOrderBy(AppConstant::SORT_POPULARITY);
            $crsSrch->addCondition('course.course_status', '=', Course::PUBLISHED);
            $crsSrch->addCondition('course.course_active', '=', AppConstant::ACTIVE);
            $cnd = $crsSrch->addCondition('course.course_cate_id', '=', $id);
            $cnd->attachCondition('course.course_subcate_id', '=', $id, 'OR');
            $crsSrch->setPageSize(AppConstant::PAGESIZE);
            if ($courses = $crsSrch->fetchAndFormat()) {
                $data['courses'][$id] = $courses;
            }
            if (count($data['courses']) == 6) {
                break;
            }
        }
        return $data;
    }

    /**
     * Get Courses as per Sold Count for Home Page
     * 
     * @return array 
     */
    public function getCoursesHome(): array
    {
        $crsSrch = clone $this;
        $crsSrch->addSearchListingFields();
        $crsSrch->applyOrderBy(AppConstant::SORT_POPULARITY);
        $crsSrch->addCondition('course.course_status', '=', Course::PUBLISHED);
        $crsSrch->addCondition('course.course_active', '=', AppConstant::ACTIVE);
        $crsSrch->addCondition('teacher.user_username', '!=', "");
        $crsSrch->setPageSize(AppConstant::PAGESIZE);
        return $crsSrch->fetchAndFormat();
    }

    /**
     * Can Edit Course
     * 
     * @param array $course
     * @return bool
     */
    private function canEdit(array $course, array $editRequests = [])
    {
        if ($this->userType == User::TEACHER && ($course['course_status'] == Course::DRAFTED)) {
            return true;
        }
        /* if (!empty($editRequests) && isset($editRequests[$course['course_id']])) {
            return true;
        } */
        return false;
    }

    /**
     * Can Delete Course
     * 
     * @param array $course
     * @return bool
     */
    private function canDelete(array $course)
    {
        if ($this->userType == User::TEACHER && $course['course_status'] == Course::DRAFTED) {
            return true;
        }
        return false;
    }

    /**
     * Can Edit Course
     * 
     * @param array $course
     * @return bool
     */
    private function canSendEditRequest(array $course)
    {
        if ($this->userType == User::TEACHER && $course['course_status'] == Course::PUBLISHED) {
            return true;
        }
        return false;
    }
}
