<?php

foreach ($records as $key => $record) {
    if (isset($record['file_id'])) {
        $record['downloadAttachment'] = MyUtility::makeUrl('Chats', 'downloadAttachment', [$record['msg_id'], $record['file_name']]);
        $fromMe = ($record['user_id'] == $siteUserId);
        $minutes = (time() - strtotime($record['msg_created_utc'])) / 60;
        $record['canDeleteAttachment'] = ($fromMe && $minutes <= $deleteDuration);
    }
    $record['user_photo'] = User::getPhoto($record['user_id']);
    $record['msg_created'] = MyDate::showDate($record['msg_created'], true);
    $records[$key] = $record;
}
$messages = [];
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('LBL_MESSAGE_LISTING'),
    'records' => array_values($records),
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / AppConstant::PAGESIZE)),
]);
$messages['msg'] = Label::getLabel('LBL_MESSAGE_LISTING');
MyUtility::dieJsonSuccess($messages);

