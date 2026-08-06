<?php

$chats = [];
foreach ($threads as $sn => $row) {
    if ($row['thread_type'] == Thread::GROUP) {
        $image = MyUtility::makeFullUrl('Images', 'group.png', [], CONF_WEBROOT_FRONT_URL);
    } else {
        $image = User::getPhoto($row['thread_record_id']);
    }
    array_push($chats, [
        'thread_title' => $row['thread_title'],
        'thread_id' => $row['thread_id'],
        'thread_type' => $row['thread_type'],
        'message_text' => $row['msg_text'] ?? '',
        'thusr_color' => $row['thusr_color'],
        'thread_read' => $row['thread_read'],
        'unread_count' => $row['thread_unread'],
        'message_date' => MyDate::showDate($row['thread_updated'], true),
        'thread_image' => $image
    ]);
}
$chats['msg'] = Label::getLabel('API_MESSAGE_LISTING');
MyUtility::dieJsonSuccess($chats);
