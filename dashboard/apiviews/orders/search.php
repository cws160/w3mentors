<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($orders as &$order) {
    $order['payment_link'] = '';
    $pmethod = PaymentMethod::getByCode(BankTransferPay::KEY);
    if (
            $order['order_payment_status'] == Order::UNPAID &&
            $order['order_pmethod_id'] == ($pmethod['pmethod_id'] ?? 0)
    ) {
        $rootUrl = API_CALL ? CONF_WEBROOT_FRONTEND . 'api/' : CONF_WEBROOT_FRONTEND;
        $order['payment_link'] = MyUtility::makeFullUrl('Payment', 'charge', [$order['order_id']], $rootUrl);
    }
    $order['view_order_id'] = $order['order_id'];
    if (!empty($order['order_related_order_id'])) {
        $order['view_order_id'] = $order['order_related_order_id'];
    }
    $order['order_format_id'] = Order::formatOrderId($order['order_id']);
    $order['order_type_text'] = Order::getTypeArr($order['order_type']);
    $order['order_addedon'] = MyDate::showDate($order['order_addedon']);
    $order['order_status_text'] = Order::getStatusArr($order['order_status']);
    $order['order_payment_status'] = Order::getPaymentArr($order['order_payment_status']);
    $order['order_total_amount'] = MyUtility::formatMoney($order['order_total_amount']);
    $order['order_discount_value'] = MyUtility::formatMoney($order['order_discount_value']);
    $order['order_net_amount'] = MyUtility::formatMoney($order['order_net_amount']);
    unset($order['learner_email']);
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_ORDER_LISTING'),
    'orders' => $orders,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
