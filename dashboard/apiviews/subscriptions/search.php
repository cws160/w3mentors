<?php

$titleLabel = Label::getLabel('LBL_{lang-name}_-_{lesson-count}_LESSONS');
$naLabel = Label::getLabel('LBL_NA');
$statuses = Subscription::getStatuses();
foreach ($subscriptions as &$subscription) {
    $status = $statuses[$subscription['ordsub_status']];
    if ($subscription['ordsub_status'] == Subscription::ACTIVE && strtotime($subscription['ordsub_enddate']) < $subscription['ordsub_currenttime_unix']) {
        $status = Label::getLabel('LBL_EXPIRED');
    }
    $subscription = [
        'status_text' => $status,
        'ordsub_id' => $subscription['ordsub_id'],
        'user_name' => implode(' ', [$subscription['first_name'], $subscription['last_name']]),
        'title' => str_replace(['{lang-name}', '{lesson-count}'], [$subscription['langName'], $subscription['lessonCount']], $titleLabel),
        'start_date' => MyDate::showDate($subscription['ordsub_startdate']),
        'end_date' => MyDate::showDate($subscription['ordsub_enddate']),
        'order_id' => $subscription['order_id'],
        'canCancel' => $subscription['canCancel']
    ];
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_MY_SUBSCRIPTION'),
    'subscriptions' => array_values($subscriptions),
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
