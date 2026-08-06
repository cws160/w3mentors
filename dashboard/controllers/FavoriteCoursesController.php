<?php

/**
 * This Controller is used for handling favorite courses
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class FavoriteCoursesController extends DashboardController
{

    /**
     * Initialize
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
    }

    /**
     * Render Listing Page
     */
    public function index()
    {
        $this->set('frm', $this->getSearchform());
        $this->_template->render();
    }

    /**
     * Render list of favorite courses
     */
    public function search()
    {
        $frm = $this->getSearchform();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        /* get courses list */
        $srch = new CourseSearch($this->siteLangId, $this->siteUserId, $this->siteUserType);
        $srch->joinTable(
            User::DB_TBL_COURSE_FAVORITE,
            'INNER JOIN',
            'crsfav.ufc_course_id = course.course_id',
            'crsfav'
        );
        $srch->applyPrimaryConditions();
        $srch->addCondition('course.course_active', '=', AppConstant::ACTIVE);
        $srch->addCondition('crsfav.ufc_user_id', '=', $this->siteUserId);
        $srch->addSearchListingFields([
            'course.course_id', 'course_type', 
            'course_title', 'course_subtitle', 'course_price', 'course_slug',
            'course_lectures', 'course_students', 'course_ratings', 'course_reviews',
            'course_cate_id', 'course_subcate_id'
        ]);
        $srch->addOrder('ufc_id', 'DESC');
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        $courses = $srch->fetchAndFormat();
        /* checkout form */
        $cart = new Cart($this->siteUserId, $this->siteLangId);
        $checkoutForm = $cart->getCheckoutForm([0 => Label::getLabel('LBL_NA')]);
        $checkoutForm->fill(['order_type' => Order::TYPE_COURSE]);
        $this->sets([
            'courses' => $courses,
            'post' => $post,
            'checkoutForm' => $checkoutForm,
            'courseTypes' => Course::getTypes(),
            'recordCount' => $srch->recordCount()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Search form
     */
    private function getSearchform()
    {
        $frm = new Form('frmSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE);
        $frm->addHiddenField('', 'pageno', 1);
        return $frm;
    }
}
