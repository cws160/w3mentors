<?php

/**
 * Packages Controller is used for Packages order handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class PackagesController extends AdminBaseController
{

    /**
     * Initialize Classes
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        if (!GroupClass::isEnabled()) {
            FatUtility::exitWithErrorCode(404);
        }
        parent::__construct($action);
        $this->objPrivilege->canViewPackagesOrders();
    }

    /**
     * Render Order Class Search Form
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $frm->fill(FatApp::getPostedData() + FatApp::getQueryStringData());
        $this->set('srchFrm', $frm);
        $this->_template->render();
    }

    /**
     * Search & List Order Class
     */
    public function search()
    {
        $langId = $this->siteLangId;
        $adminId = $this->siteAdminId;
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new PackageSearch($langId, $adminId, User::SUPPORT);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->addMultipleFields([
            'learner.user_first_name as learner_first_name', 'learner.user_last_name as learner_last_name',
            'order_id', 'order_net_amount', 'ordpkg_id', 'ordpkg_amount', 'ordpkg_discount',
            'ordpkg_reward_discount', 'order_payment_status', 'ordpkg_offline', 'order_addedon'
        ]);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('ordpkg_id', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $orders = $srch->fetchAndFormat();
        $this->set('post', $post);
        $this->set('orders', $orders);
        $this->set('recordCount', $srch->recordCount());
        $this->set('canEdit', $this->objPrivilege->canEditClassesOrders(true));
        $this->_template->render(false, false);
    }

    /**
     * Render Order Class View
     */
    public function view()
    {
        $ordpkgId = FatApp::getPostedData('ordpkgId');
        if (empty($ordpkgId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langId = $this->siteLangId;
        $adminId = $this->siteAdminId;
        $srch = new PackageSearch($langId, $adminId, User::SUPPORT);
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'learner.user_id = orders.order_user_id', 'learner');
        $srch->addMultipleFields([
            'learner.user_first_name as learner_first_name', 'learner.user_last_name as learner_last_name',
            'order_id', 'order_net_amount', 'ordpkg_package_id', 'ordpkg_amount', 'ordpkg_discount',
            'ordpkg_reward_discount', 'order_payment_status', 'order_pmethod_id', 'order_addedon'
        ]);
        $srch->addCondition('ordpkg.ordpkg_id', '=', $ordpkgId);
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
     * @return \Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_SEARCH_BY_KEYWORD')]);
        $frm->addTextBox(Label::getLabel('LBL_LANGUAGE'), 'ordcls_tlang', '', ['id' => 'ordcls_tlang', 'autocomplete' => 'off']);
        $frm->addSelectBox(Label::getLabel('LBL_SERVICE_TYPE'), 'ordpkg_offline', AppConstant::getServiceType(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'ordcls_tlang_id', '', ['id' => 'ordcls_tlang_id', 'autocomplete' => 'off']);
        $frm->addSelectBox(Label::getLabel('LBL_PAYMENT'), 'order_payment_status', Order::getPaymentArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'ordpkg_status', OrderPackage::getStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'order_addedon_from', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'order_addedon_till', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'order_id');
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear')));
        return $frm;
    }

}
