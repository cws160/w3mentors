<?php

/**
 * Courses Controller is used for course handling
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class CoursesController extends AdminBaseController
{

    /**
     * Initialize Courses
     *
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        if (!Course::isEnabled()) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError(Label::getLabel('LBL_COURSE_MODULE_NOT_AVAILABLE'));
            }
            FatUtility::exitWithErrorCode(404);
        }
        $this->objPrivilege->canViewCourses();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $cateId = FatApp::getQueryStringData('course_cateid') ?? 0;
        $frm = $this->getSearchForm($cateId);
        $frm->fill(FatApp::getQueryStringData());
        $this->set('srchFrm', $frm);
        $this->set('params', FatApp::getQueryStringData());
        $this->_template->render();
    }

    /**
     * Search & List
     */
    public function search()
    {
        $frm = $this->getSearchForm();
        $post = $frm->getFormDataFromArray(FatApp::getPostedData(), ['course_subcateid']);
        
        $srch = new CourseSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->joinTable(Course::DB_TBL_APPROVAL_REQUEST, 'INNER JOIN', 'course.course_id = coapre.coapre_course_id', 'coapre');
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addFld('coapre_updated');
        $srch->addCondition('coapre.coapre_status', '=', Course::REQUEST_APPROVED);
        $srch->addCondition('course.course_status', '=', Course::PUBLISHED);
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['page']);
        $srch->addOrder('course.course_active', 'DESC');
        $srch->addOrder('course.course_id', 'DESC');
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $orders = $srch->fetchAndFormat();
        $this->sets([
            'arrListing' => $orders,
            'page' => $post['page'],
            'post' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'canEdit' => $this->objPrivilege->canEditCourses(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Render Course View
     *
     * @param int $courseId
     * return html
     */
    public function view(int $courseId)
    {
        $srch = new CourseSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->addCondition('course.course_id', '=', $courseId);
        $srch->applyPrimaryConditions();
        $srch->joinTable(Category::DB_TBL, 'LEFT JOIN', 'subcate.cate_id = course.course_subcate_id', 'subcate');
        $srch->joinTable(Course::DB_TBL_APPROVAL_REQUEST, 'LEFT JOIN', 'coapre.coapre_course_id = course.course_id', 'coapre');
        $srch->joinTable(Category::DB_LANG_TBL, 'LEFT JOIN', 'subcate.cate_id = subcatelang.catelang_cate_id AND subcatelang.catelang_lang_id = ' . $this->siteLangId, 'subcatelang');
        $srch->addSearchListingFields();
        $srch->addMultipleFields(['subcatelang.cate_name AS subcate_name', 'coapre_updated']);
        $courses = $srch->fetchAndFormat();
        if (empty($courses)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $course = current($courses);
        $course['course_quiz_title'] = '';
        if ($course['course_quilin_id'] > 0) {
            $course['course_quiz_title'] = QuizLinked::getAttributesById($course['course_quilin_id'], 'quilin_title');
        }
        $this->sets([
            'courseData' => $course,
            'canEdit' => $this->objPrivilege->canEditCourses(true),
        ]);
        $this->_template->render(false, false);
    }

    public function video(string $id)
    {
        $videoUrl = '';
        $video = new VideoStreamer();
        if(!$videoUrl = $video->getUrl($id)) {
            FatUtility::dieWithError($video->getError());
        }
        $this->set('url', $videoUrl);
        $this->_template->render(false, false);
    }

    /**
     * Fetch sub categories for selected category
     *
     * @param int $catgId
     * @param int $subCatgId
     * @return html
     */
    public function getSubcategories(int $catgId, int $subCatgId = 0)
    {
        $catgId = FatUtility::int($catgId);
        $subcategories = [];
        if ($catgId > 0) {
            $subcategories = Category::getCategoriesByParentId($this->siteLangId, $catgId);
        }
        $this->set('subCatgId', $subCatgId);
        $this->set('subcategories', $subcategories);
        $this->_template->render(false, false);
    }

    /**
     * Auto Complete JSON
     */
    public function autoCompleteJson()
    {
        $keyword = trim(FatApp::getPostedData('keyword', FatUtility::VAR_STRING, ''));
        if (empty($keyword)) {
            FatUtility::dieJsonSuccess(['data' => []]);
        }
        $srch = new SearchBase(CourseLanguage::DB_TBL, 'clang');
        $srch->joinTable(CourseLanguage::DB_TBL_LANG, 'LEFT JOIN', 'clanglang.clanglang_clang_id = clang.clang_id AND clanglang.clanglang_lang_id = ' . $this->siteLangId, 'clanglang');
        $srch->addMultiplefields(['clang_id', 'IFNULL(clanglang.clang_name, clang.clang_identifier) as clang_name']);
        if (!empty($keyword)) {
            $cond = $srch->addCondition('clanglang.clang_name', 'LIKE', '%' . $keyword . '%');
            $cond->attachCondition('clang.clang_identifier', 'LIKE', '%' . $keyword . '%', 'OR');
        }
        $srch->addCondition('clang.clang_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('clang.clang_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addOrder('clang_name', 'ASC');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(20);
        $data = FatApp::getDb()->fetchAll($srch->getResultSet(), 'clang_id');
        FatUtility::dieJsonSuccess(['data' => $data]);
    }

    /**
     * Update status
     *
     * @param int $courseId
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $courseId, int $status)
    {
        $this->objPrivilege->canEditCourses();
        $courseId = FatUtility::int($courseId);
        $status = FatUtility::int($status);
        $status = ($status == AppConstant::YES) ? AppConstant::NO : AppConstant::YES;
        $course = new Course($courseId);
        $course->setFldValue('course_active', $status);

        $db = FatApp::getDb();
        $db->startTransaction();
        if (!$course->save()) {
            FatUtility::dieJsonError($course->getError());
        }
        if (!$course->setStatsCount()) {
            $db->rollbackTransaction();
            FatUtility::dieJsonError($course->getError());
        }
        $db->commitTransaction();
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_STATUS_UPDATED_SUCCESSFULLY'));
    }

    public function frame(int $id)
    {
        $srch = new SearchBase(Course::DB_TBL_LANG);
        $srch->addCondition('course_id', '=', $id);
        $srch->addFld('course_details');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $data = FatApp::getDb()->fetch($srch->getResultSet());
        $this->set('data', $data['course_details']);
        $this->_template->render(false, false, '_partial/frame.php');
    }

    /**
     * Get Search Form
     *
     * @param int $cateId
     * @return \Form
     */
    private function getSearchForm(int $cateId = 0): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_SEARCH_BY_COURSE_TITLE_OR_TEACHER')]);
        $frm->addTextBox(Label::getLabel('LBL_COURSE_LANGUAGE'), 'course_clang', '', ['id' => 'course_clang_id', 'autocomplete' => 'off']);
        $categoryList = Category::getCategoriesByParentId($this->siteLangId, 0, Category::TYPE_COURSE, true);
        $frm->addSelectBox(Label::getLabel('LBL_CATEGORY'), 'course_cateid', $categoryList, '', [], Label::getLabel('LBL_SELECT'));
        $subcategories = [];
        if ($cateId > 0) {
            $subcategories = Category::getCategoriesByParentId($this->siteLangId, $cateId);
        }
        $frm->addSelectBox(Label::getLabel('LBL_SUBCATEGORY'), 'course_subcateid', $subcategories, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'course_addedon_from', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'course_addedon_till', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'course_clang_id', '', ['id' => 'course_clang_id', 'autocomplete' => 'off']);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'));
        $frm->addHiddenField('', 'page', 1);
        $frm->addHiddenField('', 'order_id');
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        return $frm;
    }

}
