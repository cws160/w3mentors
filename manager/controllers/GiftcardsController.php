<?php

/**
 * GiftCard is used for GiftCard Orders handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class GiftcardsController extends AdminBaseController
{

    /**
     * Initialize GiftCard
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewGiftcardOrders();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $frmSearch = $this->getSearchForm();
        $frmSearch->fill(FatApp::getPostedData() + FatApp::getQueryStringData());
        $this->set('frmSearch', $frmSearch);
        $this->_template->render();
    }

    /**
     * Search & List GiftCard Orders
     */
    public function search()
    {
        $langId = $this->siteLangId;
        $adminId = $this->siteAdminId;
        $posts = FatApp::getPostedData();
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new GiftcardSearch($langId, $adminId, User::SUPPORT);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('order_id', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $orders = $srch->fetchAndFormat();
        $this->set('post', $post);
        $this->set('orders', $orders);
        $this->set('recordCount', $srch->recordCount());
        $this->set('canEdit', $this->objPrivilege->canEditGiftcardOrders(true));
        $this->_template->render(false, false);
    }

    /**
     * View GiftCard Order
     */
    public function view()
    {
        $ordgiftId = FatApp::getPostedData('ordgiftId');
        if (empty($ordgiftId)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $langId = $this->siteLangId;
        $adminId = $this->siteAdminId;
        $srch = new GiftcardSearch($langId, $adminId, User::SUPPORT);
        $srch->addCondition('ordgift.ordgift_id', '=', $ordgiftId);
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
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_Keyword'), 'keyword', '', ['id' => 'keyword', 'autocomplete' => 'off']);
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'giftcard_status', Giftcard::getStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_Payment_Status'), 'order_payment_status', Order::getPaymentArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'date_from', '', ['readonly' => 'true']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'date_to', '', ['readonly' => 'true']);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'order_id');
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_clear', Label::getLabel('LBL_Clear')));
        return $frm;
    }

}
