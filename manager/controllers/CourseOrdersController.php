<?php

/**
 * Course Orders Controller is used for Order Courses handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class CourseOrdersController extends AdminBaseController
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
            FatUtility::exitWithErrorCode(404);
        }
        $this->objPrivilege->canViewCoursesOrders();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        
        $frm = $this->getSearchForm();
        $frm->fill(FatApp::getPostedData() + FatApp::getQueryStringData());
        $this->set('srchFrm', $frm);
        $this->_template->render();
    }

    /**
     * Search & List
     */
    public function search()
    {
        $form = $this->getSearchForm();
      
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $srch = new OrderCourseSearch($this->siteLangId, 0, User::SUPPORT);
        $srch->addSearchListingFields();
        $srch->applySearchConditions($post);
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['page'] ?? 1);
        $srch->addOrder('ordcrs_id', 'DESC');
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $data = FatApp::getDb()->fetchAll($srch->getResultSet());
        $data = $this->fetchAndFormat($data);
        $this->sets([
            'arrListing' => $data,
            'page' => $post['page'],
            'post' => $post,
            'pageSize' => $post['pagesize'],
            'pageCount' => $srch->pages(),
            'recordCount' => $srch->recordCount(),
            'canEdit' => $this->objPrivilege->canEditCoursesOrders(true),
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     * 
     * @return \Form
     */
    private function getSearchForm(): Form
    {
        $orderStatuses = OrderCourse::getStatuses();
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_SEARCH_BY_COURSE_TITLE,_TEACHER,_LEARNER,_ORDER_ID'), 'title' => Label::getLabel('LBL_SEARCH_BY_COURSE_TITLE,_TEACHER,_LEARNER,_ORDER_ID')]);
        $frm->addSelectBox(Label::getLabel('LBL_PAYMENT'), 'order_payment_status', Order::getPaymentArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'ordcrs_status', $orderStatuses, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'order_addedon_from', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'order_addedon_till', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'page', 1)->requirements()->setIntPositive();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        return $frm;
    }
    private function fetchAndFormat($rows, $single = false)
    {
        if (empty($rows)) {
            return [];
        }
        foreach ($rows as $key => $row) {
            $row['order_created'] = MyDate::formatDate($row['order_created']);
            $rows[$key] = $row;
        }
        if ($single) {
            $rows = current($rows);
        }
        return $rows;
    }
}
