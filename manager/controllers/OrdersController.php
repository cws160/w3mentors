<?php

/**
 * Orders Controller is used for Orders handling
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrdersController extends AdminBaseController
{

    /**
     * Order Initialize
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
        $this->objPrivilege->canViewOrders();
    }

    /**
     * Render Search Form
     */
    public function index()
    {
        $frm = $this->getSearchForm();
        $frm->fill(FatApp::getPostedData());
        $this->set('srchFrm', $frm);
        $this->_template->render();
    }

    /**
     * Search & List Orders
     */
    public function search()
    {
        $posts = FatApp::getPostedData();
        $posts['pagesize'] = FatApp::getConfig('CONF_ADMIN_PAGESIZE');
        $frm = $this->getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new OrderSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        if (!Course::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_COURSE);
        }
        if (!GroupClass::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_GCLASS);
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_PACKGE);
        }
        $srch->addSearchListingFields();
        $srch->addOrder('order_id', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        if (FatApp::getPostedData('export')) {
            return ['post' => $post, 'srch' => $srch];
        }
        $this->sets([
            'orders' => $srch->fetchAndFormat(),
            'post' => $post,
            'recordCount' => $srch->recordCount(),
            'canEdit' => $this->objPrivilege->canEditOrders(true)
        ]);
        $this->_template->render(false, false);
    }

    /**
     * View Order Detail
     * 
     * @param int $orderId
     */
    public function view($orderId = 0)
    {
        $srch = new OrderSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->addCondition('orders.order_id', '=', FatUtility::int($orderId));
        $srch->applyPrimaryConditions();
        if (!Course::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_COURSE);
        }
        if (!GroupClass::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_GCLASS);
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_PACKGE);
        }
        $srch->doNotCalculateRecords();
        $srch->addSearchDetailFields();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        $orders = $srch->fetchAndFormat();
        if (empty($orders)) {
            FatUtility::exitWithErrorCode(404);
        }
        $order = current($orders);
        $order['orderPayments'] = OrderPayment::getPaymentsByOrderId($orderId);
        $pendingAmount = 0;
        $totalPaidAmount = array_sum(array_column($order['orderPayments'], 'ordpay_amount'));
        if ($totalPaidAmount < $order["order_net_amount"]) {
            $pendingAmount = $order["order_net_amount"] - $totalPaidAmount;
        }
        $orderObj = new Order($order["order_id"]);
        $childeOrders = current($orderObj->getSubOrders($order["order_type"], $this->siteLangId));
        $form = $this->getPaymentForm($orderId, $pendingAmount);
        $form->fill(['ordpay_order_id' => $orderId, 'ordpay_pmethod_id' => $order['order_pmethod_id']]);
        $this->sets([
            'form' => $form,
            'order' => $order,
            'pendingAmount' => $pendingAmount,
            'totalPaidAmount' => $totalPaidAmount,
            'childeOrderDetails' => $childeOrders,
            'canEdit' => $this->objPrivilege->canEditOrders(true),
            'payins' => array_column(PaymentMethod::getAll(), 'pmethod_code', 'pmethod_id'),
        ]);
        if (class_exists('BankTransferPay')) {
            $bankTransfers = BankTransferPay::getPayments($orderId);
            foreach ($bankTransfers as &$bankTransfer) {
                $bankTransfer['bnktras_datetime'] = MyDate::formatDate($bankTransfer['bnktras_datetime']);
            }
            $this->sets([
                'bankTransfers' => $bankTransfers,
                'bankTransferPay' => PaymentMethod::getByCode(BankTransferPay::KEY),
            ]);
        }
        $this->_template->render();
    }

    /**
     * Update Payment
     */
    public function updatePayment()
    {
        $this->objPrivilege->canEditOrders();
        $orderId = FatApp::getPostedData('ordpay_order_id', FatUtility::VAR_INT, 0);
        $srch = new OrderSearch($this->siteLangId, $this->siteAdminId, User::SUPPORT);
        $srch->addCondition('orders.order_id', '=', FatUtility::int($orderId));
        $srch->addCondition('order_payment_status', '=', Order::UNPAID);
        $srch->addCondition('order_status', '=', Order::STATUS_INPROCESS);
        $srch->addCondition('order_net_amount', '>', 0);
        $srch->applyPrimaryConditions();
        $srch->addSearchDetailFields();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        $orders = $srch->fetchAndFormat();
        if (count($orders) < 1) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $order = current($orders);
        $orderPayments = OrderPayment::getPaymentsByOrderId($orderId);
        $pendingAmount = 0;
        $totalPaidAmount = array_sum(array_column($orderPayments, 'ordpay_amount'));
        if ($totalPaidAmount < $order["order_net_amount"]) {
            $pendingAmount = $order["order_net_amount"] - $totalPaidAmount;
        }
        if (empty($pendingAmount)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $form = $this->getPaymentForm($orderId, $pendingAmount);
        if (!$post = $form->getFormDataFromArray(FatApp::getPostedData())) {
            FatUtility::dieJsonError(current($form->getValidationErrors()));
        }
        $post['ordpay_amount'] = FatUtility::float($post['ordpay_amount']);
        $orderPayment = new OrderPayment($orderId, $this->siteLangId);
        if (!$orderPayment->paymentSettlements($post['ordpay_txn_id'], $post['ordpay_amount'], $post)) {
            FatUtility::dieJsonError($orderPayment->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_PAYMENT_DETAILS_ADDED_SUCCESSFULLY'));
    }

    /**
     * Cancel Order
     */
    public function cancelOrder()
    {
        $this->objPrivilege->canEditOrders();
        $orderId = FatApp::getPostedData('orderId', FatUtility::VAR_INT, 0);
        $order = new Order($orderId);
        if (!$order->cancelOrder()) {
            FatUtility::dieJsonError($order->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_CANCELLED_SUCCESSFULLY'));
    }

    public function updateStatus()
    {
        $this->objPrivilege->canEditOrders();
        $payId = FatApp::getPostedData('payId', FatUtility::VAR_INT, 0);
        $status = FatApp::getPostedData('status', FatUtility::VAR_INT, 0);
        if (empty($payId) || empty($status)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $payment = BankTransferPay::getById($payId);
        if (empty($payment)) {
            FatUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        if ($status == BankTransferPay::APPROVED) {
            if ($payment['bnktras_status'] == BankTransferPay::DECLINED) {
                FatUtility::dieJsonError(Label::getLabel('LBL_ALREADY_DECLINED'));
            }
            $payment['bnktras_datetime'] = MyDate::showDate($payment['bnktras_datetime'], true) . ' UTC';
            $orderPayment = new OrderPayment($payment['bnktras_order_id'], $this->siteLangId);
            if (!$orderPayment->paymentSettlements($payment['bnktras_txn_id'], $payment['bnktras_amount'], $payment)) {
                FatUtility::dieJsonError($orderPayment->getError());
            }
        } elseif ($status == BankTransferPay::DECLINED) {
            if ($payment['bnktras_status'] == BankTransferPay::APPROVED) {
                FatUtility::dieJsonError(Label::getLabel('LBL_ALREADY_APPROVED'));
            }
            $orderObj = new Order($payment['bnktras_order_id']);
            $order = $orderObj->getOrderToPay();
            $mail = new FatMailer($order['user_lang_id'], 'bank_transfer_payment_declined');
            $mail->setVariables([
                '{user_name}' => $order['user_name'],
                '{order_id}' => Order::formatOrderId($order['order_id'])
            ]);
            $mail->sendMail([$order['user_email']]);
        }
        $record = new TableRecord(BankTransferPay::DB_TBL);
        $record->setFldValue('bnktras_status', $status);
        if (!$record->update(['smt' => 'bnktras_id = ?', 'vals' => [$payId]])) {
            FatUtility::dieJsonError($record->getError());
        }
        FatUtility::dieJsonSuccess(Label::getLabel('LBL_ORDER_PAYMENT_UPDATED_SUCCESSFULLY'));
    }

    /**
     * Get Search Form
     * 
     * @return Form
     */
    private function getSearchForm(): Form
    {
        $orderType = Order::getTypeArr();
        $frm = new Form('srchForm');
        $frm = CommonHelper::setFormProperties($frm);
        $frm->addHiddenField('', 'order_user_id', '', ['id' => 'order_user_id', 'autocomplete' => 'off']);
        $frm->addTextBox(Label::getLabel('LBL_KEYWORD'), 'keyword', '', ['placeholder' => Label::getLabel('LBL_Search_By_Keyword')]);
        $frm->addTextBox(Label::getLabel('LBL_USER'), 'order_user', '', ['id' => 'order_user', 'autocomplete' => 'off']);
        $frm->addSelectBox(Label::getLabel('LBL_ORDER_TYPE'), 'order_type', $orderType, '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_SERVICE_TYPE'), 'service_type', AppConstant::getServiceType(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_PAYMENT'), 'order_payment_status', Order::getPaymentArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addSelectBox(Label::getLabel('LBL_STATUS'), 'order_status', Order::getStatusArr(), '', [], Label::getLabel('LBL_SELECT'));
        $frm->addDateField(Label::getLabel('LBL_DATE_FROM'), 'date_from', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addDateField(Label::getLabel('LBL_DATE_TO'), 'date_to', '', ['readonly' => 'readonly',  'class' => 'small dateTimeFld field--calender']);
        $frm->addHiddenField('', 'pagesize', FatApp::getConfig('CONF_ADMIN_PAGESIZE'))->requirements()->setIntPositive();
        $frm->addHiddenField('', 'pageno', 1)->requirements()->setIntPositive();
        $btnSubmit = $frm->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SEARCH'));
        $btnSubmit->attachField($frm->addResetButton('', 'btn_reset', Label::getLabel('LBL_CLEAR')));
        return $frm;
    }

    /**
     * Get Payment Form
     * 
     * @param int $orderId
     * @param float $netAmount
     * @return Form
     */
    private function getPaymentForm(int $orderId, float $netAmount): Form
    {
        $form = new Form('frmPayment');
        $form = CommonHelper::setFormProperties($form);
        $form->addHiddenField('', 'ordpay_order_id', $orderId);
        $form->addSelectBox(Label::getLabel('LBL_PAYMENT_METHOD'), 'ordpay_pmethod_id', PaymentMethod::getPayins(), '', [], Label::getLabel('LBL_SELECT'))
            ->requirements()->setRequired(true);
        $form->addRequiredField(Label::getLabel('LBL_TXN_ID'), 'ordpay_txn_id');
        $amountFld = $form->addRequiredField(Label::getLabel('LBL_AMOUNT'), 'ordpay_amount');
        $amountFld->requirements()->setFloatPositive(true);
        $amountFld->requirements()->setRange(round($netAmount, 2), round($netAmount, 2));
        $form->addTextArea(Label::getLabel('LBL_COMMENTS'), 'ordpay_response', '')->requirements()->setRequired();
        $form->addSubmitButton('', 'btn_submit', Label::getLabel('LBL_SAVE_CHANGES'));
        return $form;
    }

    public function viewInvoice(int $orderId, int $subOrderId = null)
    {
        $srch = new OrderSearch($this->siteLangId, 0, 0);
        $srch->addCondition('orders.order_id', '=', FatUtility::int($orderId));
        if (!Course::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_COURSE);
        }
        if (!GroupClass::isEnabled()) {
            $srch->addCondition('orders.order_type', 'NOT IN', [Order::TYPE_GCLASS, Order::TYPE_PACKGE]);
        }
        if (!SubscriptionPlan::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=',  Order::TYPE_SUBPLAN);
        }
        $srch->doNotCalculateRecords();
        $srch->applyPrimaryConditions();
        $srch->addSearchDetailFields();
        $srch->setPageSize(1);

        $orders = $srch->fetchAndFormat();

        if (empty($orders)) {
            MyUtility::exitWithErrorCode(404);
        }

        $order = current($orders);
        $order['learner_email'] = $order['learner_email'] ?? Label::getLabel('LBL_NA');

        $orderObj = new Order($order["order_id"]);
        $subOrders = $orderObj->getSubOrders($order["order_type"], $this->siteLangId, $subOrderId);

        $this->sets([
            'order' => $order,
            'subOrders' => $subOrders,
            'pmethods' => PaymentMethod::getPayins(true, false),
            'countries' => Country::getNames($this->siteLangId),
            'subOrderId' => $subOrderId
        ]);

        $content = $this->_template->render(false, false, 'orders/view-invoice.php', true);
        $mpdf = new \Mpdf\Mpdf([
            'orientation' => 'P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
            'mirrorMargins' => 0,
            'autoLangToFont' => true,
            'autoScriptToLang' => true,
            'tempDir' => CONF_UPLOADS_PATH,
            'default_font' => 'DejaVu Sans'
        ]);
        $mpdf->SetDirectionality(Language::getAttributesById($this->siteLangId, 'language_direction'));
        $mpdf->WriteHTML($content);
        ob_end_clean();
        header('Cache-Control: no-cache, no-store, must-revalidate'); // HTTP 1.1
        header('Pragma: no-cache'); // HTTP 1.0
        header('Expires: 0');
        $mpdf->Output('order-receipt-' . Order::formatOrderId(FatUtility::int($orderId)) . '.pdf', \Mpdf\Output\Destination::INLINE);
        $mpdf->Reset();
        return true;
    }
}
