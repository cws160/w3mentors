<?php

/**
 * Payment Controller Handles all type of payment throughout the system
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class PaymentController extends MyAppController
{

    private $order;
    private $pmethod;

    /**
     * Initialize Payment
     * 
     * @param string $action
     */
    public function __construct(string $action)
    {
        $this->order = [];
        $this->pmethod = [];
        MyUtility::setUserType(User::LEARNER);
        parent::__construct($action);
        $this->set('exculdeMainHeaderDiv', false);
    }

    /**
     * Render Payment Form or redirect to payment gateway website
     * 
     * @param int $orderId
     */
    public function charge($orderId)
    {
        $orderId = FatUtility::int($orderId);
        $orderObj = new Order($orderId, $this->siteUserId);
        $order = $orderObj->getOrderToPay();
        if (empty($order)) {
            Message::addErrorMessage($orderObj->getError());
            FatUtility::exitWithErrorCode(404);
        }
        if (Order::ISPAID == $order['order_payment_status']) {
            Message::addMessage(Label::getLabel('LBL_ORDER_HAS_BEEN_ALREADY_PAID'));
            FatApp::redirectUser(MyUtility::makeUrl('Payment', 'success', [$orderId]));
        }
        $pay = new $order['pmethod_code']($order);
        if (!$data = $pay->getChargeData()) {
            Message::addErrorMessage($pay->getError());
            FatApp::redirectUser(MyUtility::makeUrl('Payment', 'failed', [$orderId]));
        }
        $this->sets($data);
        $view = FatUtility::camel2dashed($order['pmethod_code']) . '.php';
        $this->_template->render(true, false, 'payment/' . $view);
    }

    /**
     * Callback receive all type on payment gateway callbacks
     * 
     * @param int $orderId
     */
    public function callback(int $orderId)
    {
        $orderObj = new Order($orderId);
        if (!$order = $orderObj->getOrderToPay()) {
            FatUtility::exitWithErrorCode(404);
        }
        Payment::logResponse($orderId);
        $this->siteUser = User::getDetail($order['order_user_id']);
        $this->siteLangId = FatUtility::int($order['user_lang_id']);
        $this->siteCurrId = FatUtility::int($order['user_currency_id']);
        $this->siteCurrency = Currency::getData($this->siteCurrId, $this->siteLangId);
        if (empty($this->siteCurrency)) {
            $this->siteCurrId = FatUtility::int(FatApp::getConfig('CONF_SITE_CURRENCY'));
            $this->siteCurrency = Currency::getData($this->siteCurrId, $this->siteLangId);
            User::setCurrency($order['order_user_id'],  $this->siteCurrId);
        }
        MyUtility::setSiteCurrency($this->siteCurrency, true);
        $pay = new $order['pmethod_code']($order);
        $requestData = $_REQUEST;
        if ($order['pmethod_code'] == MpesaPay::KEY) {
            $jsonData = file_get_contents('php://input');
            $requestData = json_decode($jsonData, true);
        }
        $res = $pay->callbackHandler($requestData);
        if ($res['status'] === AppConstant::NO) {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonError($res);
            } else {
                Message::addErrorMessage($res['msg']);
                FatApp::redirectUser($res['url']);
            }
        } else {
            if (FatUtility::isAjaxCall()) {
                FatUtility::dieJsonSuccess($res);
            } else {
                Message::addMessage($res['msg']);
                FatApp::redirectUser($res['url']);
            }
        }
    }

    /**
     * Return receive all type return requests from|on payment gateways
     * 
     * @param int $orderId
     */
    public function return(int $orderId)
    {
        $orderObj = new Order($orderId);
        if (!$order = $orderObj->getOrderToPay()) {
            FatUtility::exitWithErrorCode(404);
        }
        Payment::logResponse($orderId);
        $this->siteUser = User::getDetail($order['order_user_id']);
        $this->siteLangId = FatUtility::int($order['user_lang_id']);
        $this->siteCurrId = FatUtility::int($order['user_currency_id']);
        $this->siteCurrency = Currency::getData($this->siteCurrId, $this->siteLangId);
        if (empty($this->siteCurrency)) {
            $this->siteCurrId = FatUtility::int(FatApp::getConfig('CONF_SITE_CURRENCY'));
            $this->siteCurrency = Currency::getData($this->siteCurrId, $this->siteLangId);
            User::setCurrency($order['order_user_id'],  $this->siteCurrId);
        }
        MyUtility::setSiteCurrency($this->siteCurrency, true);
        $pay = new $order['pmethod_code']($order);
        $res = $pay->returnHandler($_REQUEST);
        if ($res['status'] === AppConstant::NO) {
            if (FatUtility::isAjaxCall()) {
                MyUtility::dieJsonError($res);
            } else {
                Message::addErrorMessage($res['msg']);
                FatApp::redirectUser($res['url']);
            }
        } else {
            if (FatUtility::isAjaxCall()) {
                MyUtility::dieJsonSuccess($res);
            } else {
                Message::addMessage($res['msg']);
                FatApp::redirectUser($res['url']);
            }
        }
    }

    /**
     * Render cancelled order page
     * 
     * @param int $orderId
     */
    public function cancel($orderId)
    {
        $order = Order::getAttributesById($orderId);
        if (empty($order)) {
            FatUtility::exitWithErrorCode(404);
        }
        $message = Label::getLabel('MSG_LEARNER_CANCEL_ORDER_{contacturl}');
        $this->set('textMessage', str_replace('{contacturl}', '<a href="' . MyUtility::makeUrl('Contact') . '" class="link">' . Label::getLabel('LBL_CLICK_HERE') . '</a>', $message));
        $this->set('order', $order);
        $this->_template->render(true, false);
    }

    /**
     * Render failed Payment order page
     * 
     * @param int $orderId
     */
    public function failed($orderId)
    {
        $orderObj = new Order($orderId);
        $order = $orderObj->getOrderToPay();
        if (empty($order)) {
            FatUtility::exitWithErrorCode(404);
        }
        $this->set('order', $order);
        $pay = new $order['pmethod_code']($order);
        $this->set('data', $pay->getFailedData());
        $this->_template->render(true, false);
    }

    /**
     * Render success Payment order page
     * 
     * @param int $orderId
     */
    public function success($orderId)
    {
        $orderObj = new Order($orderId);
        $order = $orderObj->getOrderToPay();
        if (FatUtility::int($order['order_related_order_id']) > 0) {
            $orderId = FatUtility::int($order['order_related_order_id']);
            $orderObj = new Order($orderId);
            $order = $orderObj->getOrderToPay();
        }
        if (empty($order)) {
            FatUtility::exitWithErrorCode(404);
        }
        if (!empty($order['pmethod_code'])) {
            $pay = new $order['pmethod_code']($order);
            $this->set('data', $pay->getSuccessData());
        }
        $this->set('order', $order);
        if (!API_CALL && $this->siteUserId > 0 && in_array(
            $order['order_type'],
            [Order::TYPE_LESSON, Order::TYPE_SUBSCR]
        )) {
            $this->set('lessons', $this->getOrderLessons($orderId));
            $this->_template->addJs('js/moment.min.js');
            $this->_template->addJs('js/fateventcalendar.js');
        }
        $this->_template->render(true, false);
    }

    /**
     * Render process Payment order page
     * 
     * @param int $orderId
     */
    public function process($orderId)
    {
        $orderObj = new Order($orderId);
        if (!$order = $orderObj->getOrderToPay()) {
            FatUtility::exitWithErrorCode(404);
        }
        $pay = new $order['pmethod_code']($order);
        $this->set('data', $pay->getProcessData());
        $this->set('order', $order);
        $this->_template->render(true, false);
    }

    /**
     * Get Ordered Lesson
     * 
     * @param int $orderId
     * @return type
     */
    private function getOrderLessons(int $orderId)
    {
        $srch = new SearchBase(Lesson::DB_TBL, 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->addMultipleFields(['ordles_id', 'ordles_status', 'ordles_lesson_starttime', 'ordles_lesson_endtime']);
        $srch->addCondition('orders.order_payment_status', '=', Order::ISPAID);
        $srch->addCondition('order_user_id', '=', $this->siteUserId);
        $srch->addCondition('orders.order_id', '=', $orderId);
        $srch->doNotCalculateRecords();
        $lessons = FatApp::getDb()->fetchAll($srch->getResultSet());
        foreach ($lessons as &$lesson) {
            $lesson['ordles_lesson_starttime'] = MyDate::convert($lesson['ordles_lesson_starttime']);
            $lesson['ordles_lesson_endtime'] = MyDate::convert($lesson['ordles_lesson_endtime']);
        }
        return $lessons;
    }
}
