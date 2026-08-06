<?php

/**
 * Order Subscription Controller is used for Order Subscription handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrderSubscriptionPlansController extends AdminBaseController
{

    /**
     * Initialize Order Lesson
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        if (!SubscriptionPlan::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
        parent::__construct($action);
        $this->objPrivilege->canViewSubscriptionPlanOrders();
    }

    /**
     * Render Subscriptions Order Search Form
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $frm->fill(FatApp::getPostedData() + FatApp::getQueryStringData());
        $this->set('srchFrm', $frm);
        $this->_template->render();
    }

    /**
     * Search & List Subscription Plan Orders
     */
    public function search()
    {
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new OrderSubscriptionPlanSearch($this->siteLangId);
        $srch->applyPrimaryConditions();
        $srch->applySearchConditions($post);
        $srch->addSearchListingFields();
        $srch->addOrder('ordsplan_id', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $this->set('post', $post);
        $this->set('subscriptions', $srch->fetchAndFormat());
        $this->set('paymentMethod', OrderPayment::getMethods());
        $this->set('recordCount', $srch->recordCount());
        $this->set('canEdit', $this->objPrivilege->canEditSubscriptionPlanOrders(true));
        $this->_template->render(false, false);
    }

    /**
     * View Subscription Plan Order Detail
     */
    public function view()
    {
        $ordSplanId = FatApp::getPostedData('ordSplanId');
        if (empty($ordSplanId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $srch = new OrderSubscriptionPlanSearch($this->siteLangId);
        $srch->addCondition('ordsplan.ordsplan_id', '=', $ordSplanId);
        $srch->applyPrimaryConditions();
        $srch->addSearchDetailFields();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        $orders = $srch->fetchAndFormat();
        if (count($orders) < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $this->set('order', current($orders));
        $this->_template->render(false, false);
    }



    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $subPlanStatuses = OrderSubscriptionPlan::getStatuses();
        unset($subPlanStatuses[OrderSubscriptionPlan::EXPIRED]);
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_SEARCH_BY_KEYWORD')]);
        $frm->addSelectBox(Label::getLabel('LBL_PAYMENT'), 'order_payment_status', Order::getPaymentArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'ordsplan_status', $subPlanStatuses, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_START_DATE'), 'ordsplan_start_date', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_END_DATE'), 'ordsplan_end_date', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'order_id');
        $frm->addHiddenField('', 'ordsplan_id');
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        return $frm;
    }
}
