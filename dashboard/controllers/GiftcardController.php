<?php

/**
 * GiftCard Controller is used for handling GiftCards
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class GiftcardController extends DashboardController
{

    /**
     * Initialize GiftCard
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render GiftCard Search Form
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $frm->fill(FatApp::getQueryStringData());
        $this->set('frm', $frm);
        $this->_template->render(true, true);
    }

    /**
     * Search & List GiftCards
     */
    public function search()
    {
        $langId = $this->siteLangId;
        $userId = $this->siteUserId;
        $userType = $this->siteUserType;
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new GiftcardSearch($langId, $userId, $userType);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        $srch->addSearchListingFields();
        $srch->addOrder('order_id', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $orders = $srch->fetchAndFormat();
        $this->sets([
            'post' => $post, 'orders' => $orders,
            'recordCount' => $srch->recordCount()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $frm = new Form('frmGiftcardSrch');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addTextBox(Label::getLabel('LBL_SEARCH_BY_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_SEARCH_BY_NAME_EMAIL_CODE')]);
        $frm->addSelectBox(Label::getLabel('LBL_Type'), 'giftcard_type', Giftcard::getTypes(), Giftcard::PURCHASED, [], '');
        $frm->addSelectBox(Label::getLabel('LBL_Status'), 'giftcard_status', Giftcard::getStatuses(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addHiddenField('', 'order_id')->requirements()->setInt();
        $frm->addHiddenField('', 'pagesize', AppConstant::PAGESIZE)->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_Search'));
        $frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_Clear'));
        return $frm;
    }

    /**
     * Render GiftCard Form
     */
    public function form()
    {
        $this->set('form', $this->getForm());
        $this->set('currency', MyUtility::getSystemCurrency());
        $this->set('minAmount', FatApp::getConfig('MINIMUM_GIFT_CARD_AMOUNT'));
        $this->set('walletBalance', User::getWalletBalance($this->siteUserId));
        $this->set('walletPayId', PaymentMethod::getByCode(WalletPay::KEY)['pmethod_id']);
        $this->_template->render(false, false);
    }

    /**
     * Setup GiftCard
     */
    public function setup()
    {
        $frm = $this->getForm();
        if (!$post = $frm->getFormDataFromArray(FatApp::getPostedData())) {
            MyUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        if ($post['ordgift_receiver_email'] == $this->siteUser['user_email']) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if (FatUtility::int($post['order_total_amount']) <= 0 || empty($post['order_pmethod_id'])) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $pmethodId = FatApp::getPostedData('order_pmethod_id', FatUtility::VAR_INT, 0);
        $pmethod = PaymentMethod::getAttributesById($pmethodId);
        if (FatUtility::int($post['order_total_amount']) > 0 && (empty($pmethod) || empty($pmethod['pmethod_active']))) {
            MyUtility::dieJsonError(Label::getLabel('LBL_PAYMENT_METHOD_NOT_AVAILABLE'));
        }
        $order = new Order(0, $this->siteUserId);
        if (!$order->placeGiftcardOrder($post)) {
            MyUtility::dieJsonError($order->getError());
        }
        $orderId = $order->getMainTableRecordId();
        $viewOrderId = $orderId;
        $relatedOrderId = Order::getAttributesById($orderId, 'order_related_order_id');
        if (!empty($relatedOrderId)) {
            $viewOrderId = FatUtility::int($relatedOrderId);
        }
        $rootUrl = (API_CALL) ? CONF_WEBROOT_FRONTEND . 'api/' : CONF_WEBROOT_FRONTEND;
        $redirectUrl = MyUtility::makeFullUrl('Payment', 'charge', [$orderId], $rootUrl);
        MyUtility::dieJsonSuccess([
            'order_id' => $orderId,
            'redirectUrl' => $redirectUrl,
            'view_order_id' => $viewOrderId,
            'msg' => Label::getLabel('MSG_REDIRECTING_PLEASE_WAIT'),
        ]);
    }

    /**
     * Get Form
     * 
     * @return Form
     */
    private function getForm(): Form
    {
        $currency = MyUtility::getSystemCurrency();
        $placeHolder = Currency::format($currency['currency_positive_format'], 0, $currency['currency_symbol']);
        $balance = User::getWalletBalance($this->siteUserId);
        $payins = PaymentMethod::getPayins($balance > 0);
        $frm = new Form('frmAddMoney');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addCheckBox(Label::getLabel('LBL_ADD_AND_PAY'), 'add_and_pay', 1);
        $frm->addRadioButtons(Label::getLabel('LBL_PAYMENT_METHOD'), 'order_pmethod_id', $payins, array_key_first($payins))->requirements()->setRequired();
        $str = str_replace("{currency-code}", $currency['currency_code'], Label::getLabel('LBL_ENTER_AMOUNT_({currency-code})'));
        $amount = $frm->addFloatField($str, 'order_total_amount', '', ['placeholder' => $placeHolder]);
        $amount->requirements()->setRange(FatApp::getConfig('MINIMUM_GIFT_CARD_AMOUNT'), 9999999999);
        $amount->requirements()->setRequired();
        $frm->addRequiredField(Label::getLabel('LBL_RECEIVER_NAME'), 'ordgift_receiver_name', '', ['placeholder' => Label::getLabel('LBL_RECEIVER_NAME')]);
        $frm->addEmailField(Label::getLabel('LBL_RECEIVER_EMAIL'), 'ordgift_receiver_email', '', ['placeholder' => Label::getLabel('LBL_RECEIVER_EMAIL')]);
        $frm->addSubmitButton('', 'submit', Label::getLabel('LBL_SEND_GIFT_CARD'));
        return $frm;
    }

    /**
     * Payment Methods
     * Used for Mobile Apps
     */
    public function paymentMethods()
    {
        $amount = FatApp::getPostedData('amount', FatUtility::VAR_FLOAT, 0);
        $minAmount = max(FatApp::getConfig('MINIMUM_GIFT_CARD_AMOUNT'), $amount);
        $wallet = ($this->siteUser['user_wallet_balance'] >= $minAmount);
        $this->sets([
            'currency' => MyUtility::getSystemCurrency(),
            'methods' => PaymentMethod::getPayins($wallet),
            'balance' => $this->siteUser['user_wallet_balance'],
            'walletPayId' => PaymentMethod::getByCode(WalletPay::KEY)['pmethod_id'],
        ]);
        $this->_template->render(false, false);
    }

}
