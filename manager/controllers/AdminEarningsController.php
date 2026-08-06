<?php

/**
 * Admin Earnings Controller
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class AdminEarningsController extends AdminBaseController
{

    /**
     * Initialize 
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewAdminEarningsReport();
    }

    /**
     * Render Refer And Earn Section
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $this->set('frm', $frm);
        $this->_template->render();
    }

    /**
     * Render Referral earned list
     */
    public function search()
    {
        $post = FatApp::getPostedData();
        $post['pageno'] = $post['pageno'] ?? 1;
        $post['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($post)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new AdminEarningSearch();
        $srch->applySearchConditions($post);
        $srch->addSearchListingFields();
        $srch->addOrder('admtxn_datetime', 'DESC');
        $srch->setPageNumber($post['pageno']);
        $srch->setPageSize($post['pagesize']);
        if (FatApp::getPostedData('export')) {
            return ['post' => FatApp::getPostedData(), 'srch' => $srch];
        }
        $this->sets([
            'frm' => $frm, 'post' => $post,
            'records' => $srch->fetchAndFormat(),
            'recordCount' => $srch->recordCount(),
            'canViewClasses' => $this->objPrivilege->canViewClassesOrders(true),
            'canViewLessons' => $this->objPrivilege->canViewLessonsOrders(true),
            'canViewCourses' => $this->objPrivilege->canViewCoursesOrders(true),
            'canViewOrders' => $this->objPrivilege->canViewOrders(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm()
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        
        $types = AdminTransaction::getTypes();
        unset($types[AppConstant::SUBPLAN]);

        $frm->addSelectBox(Label::getLabel('LBL_TYPE'), 'admtxn_record_type', $types, '', [], Label::getLabel('LBL_SELECT_TYPE'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'admtxn_date_from', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'admtxn_date_to', '', ['readonly' => 'readonly', 'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        return $frm;
    }

}
