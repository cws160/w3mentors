<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($orders as $key => $order) {
    $order['formattedOrderId'] = Order::formatOrderId($order['order_id']);
    $order['order_date'] = MyDate::showDate($order['order_addedon'], true);
    $order['order_addedon'] = MyDate::showDate($order['order_addedon'], true);
    $order['ordgift_expiry'] = MyDate::showDate($order['ordgift_expiry'], true);
    $order['order_total_amount'] = MyUtility::formatMoney($order['order_total_amount']);
    $order['ordgift_status_value'] = FatUtility::int($order['ordgift_status']);
    $order['ordgift_status'] = Giftcard::getStatuses($order['ordgift_status']);
    $orders[$key] = $order;
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_GIFT_CARDS_LISTING'),
    'giftcards' => $orders,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
