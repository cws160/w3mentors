<?php

$price = $price - (($discount * $price) / 100);
$totalPrice = $totalPrice - (($discount * $totalPrice) / 100);
$data = [
    'msg' => Label::getLabel('API_PRICE_SLABS'),
    'price' => MyUtility::formatMoney($price, false),
    'price_display' => MyUtility::formatMoney($price),
    'total_price' => MyUtility::formatMoney($totalPrice, false),
    'total_price_display' => MyUtility::formatMoney($totalPrice),
    'subscription_text' => Label::getLabel('LBL_SUBSCRIPTION_HELP_TEXT'),
    'subscription_weeks' => str_replace('{weeks}', $subWeek, Label::getLabel('LBL_REPEAT_ON_EVERY_{weeks}_WEEKS')),
    'offline_sessions' => (int)User::offlineSessionsEnabled($teacher['user_id'])
];
$data['address'] = new stdClass();
if (!empty($address)) {
    $data['address'] = [
        'usradd_id' => $address['usradd_id'],
        'usradd_latitude' => $address['usradd_latitude'],
        'usradd_longitude' => $address['usradd_longitude'],
        'usradd_address' => UserAddresses::format($address),
    ];
}
MyUtility::dieJsonSuccess($data);
