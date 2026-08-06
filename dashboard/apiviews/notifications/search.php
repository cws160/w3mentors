<?php
foreach ($notifications as &$notification) {
    $notification['notifi_added'] = MyDate::showDate($notification['notifi_added'], true);
    $notification['notifi_read'] = (!is_null($notification['notifi_read']));
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_NOTIFICATION_LISTING'),
    'notifications' => $notifications,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
