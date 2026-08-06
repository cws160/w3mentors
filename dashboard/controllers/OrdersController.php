<?php

/**
 * Orders Controller is used for handling Orders
 *  
 * @package W3Mentors
 * @author Fatbit Team
 */
class OrdersController extends DashboardController
{

    /**
     * Initialize Orders
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        parent::__construct($action);
    }

    /**
     * Render Order Search Form
     */
    public function index()
    {
        $this->set('frm', OrderSearch::getSearchForm());
        $this->_template->render();
    }

    /**
     * Search & List Orders
     */
    public function search()
    {
        $posts = FatApp::getPostedData();
        $posts['pageno'] = $posts['pageno'] ?? 1;
        $posts['pagesize'] = AppConstant::PAGESIZE;
        $frm = OrderSearch::getSearchForm();
        if (!$post = $frm->getFormDataFromArray($posts)) {
            FatUtility::dieJsonError(current($frm->getValidationErrors()));
        }
        $srch = new OrderSearch($this->siteLangId, $this->siteUserId, User::LEARNER);
        $srch->applySearchConditions($post);
        $srch->applyPrimaryConditions();
        if (!Course::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_COURSE);
        }
        if (!GroupClass::isEnabled()) {
            $attachCond = $srch->addCondition('orders.order_type', '!=', Order::TYPE_GCLASS);
            $attachCond->attachCondition('orders.order_type', '!=', Order::TYPE_PACKGE, 'AND');
        }
        $srch->addSearchListingFields();
        $srch->addOrder('order_id', 'DESC');
        $srch->setPageSize($post['pagesize']);
        $srch->setPageNumber($post['pageno']);
        $orders = $srch->fetchAndFormat();
        $this->sets([
            'post' => $post,
            'orders' => $orders,
            'recordCount' => $srch->recordCount(),
            'pmethods' => PaymentMethod::getAll()
        ]);
        $this->_template->render(false, false);
    }

    /**
     * View Order Detail
     */
    public function view()
    {
        $orderId = FatApp::getPostedData('orderId', FatUtility::VAR_INT, 0);
        $srch = new OrderSearch($this->siteLangId, $this->siteUserId, User::LEARNER);
        $srch->addCondition('orders.order_id', '=', FatUtility::int($orderId));
        if (!Course::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_COURSE);
        }
        $srch->doNotCalculateRecords();
        $srch->applyPrimaryConditions();
        $srch->addSearchDetailFields();
        $srch->setPageNumber(1);
        $srch->setPageSize(1);
        $orders = $srch->fetchAndFormat();

        if (empty($orders)) {
            MyUtility::dieJsonError(Label::getLabel('LBL_INVALID_REQUEST'));
        }
        $order = current($orders);
        $order['orderPayments'] = OrderPayment::getPaymentsByOrderId($orderId);
        $pendingAmount = 0;
        $totalPaidAmount = array_sum(array_column($order['orderPayments'], 'ordpay_amount'));
        if ($totalPaidAmount < $order["order_net_amount"]) {
            $pendingAmount = $order["order_net_amount"] - $totalPaidAmount;
        }
        $orderObj = new Order($order["order_id"]);
        $subOrders = $orderObj->getSubOrders($order["order_type"], $this->siteLangId);
        $this->sets([
            'order' => $order,
            'subOrders' => $subOrders,
            'pendingAmount' => $pendingAmount,
            'totalPaidAmount' => $totalPaidAmount,
            'pmethods' => PaymentMethod::getPayins(true, false),
            'countries' => Country::getNames($this->siteLangId),
        ]);
        $this->_template->render(false, false);
    }

    public function viewInvoice(int $orderId)
    {
        $srch = new OrderSearch($this->siteLangId, $this->siteUserId, User::LEARNER);
        $srch->addCondition('orders.order_id', '=', FatUtility::int($orderId));
        if (!Course::isEnabled()) {
            $srch->addCondition('orders.order_type', '!=', Order::TYPE_COURSE);
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

        $orderObj = new Order($order["order_id"]);
        $subOrders = $orderObj->getSubOrders($order["order_type"], $this->siteLangId);

        $this->sets([
            'order' => $order,
            'subOrders' => $subOrders,
            'pmethods' => PaymentMethod::getPayins(true, false),
            'countries' => Country::getNames($this->siteLangId),
        ]);
        $this->_template->render(false, false, 'orders/view-invoice.php');
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
