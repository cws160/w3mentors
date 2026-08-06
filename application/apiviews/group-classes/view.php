<?php

$seatsLabel = Label::getLabel('LBL_{n}_SEATS');
$durationLabel = Label::getLabel('LBL_({n}_MINS)');
$bookingGap = FatApp::getConfig('CONF_CLASS_BOOKING_GAP');
$offerPriceLabel = Label::getLabel('LBL_{percentages}%_OFF_ON_{duration}_MINUTES_SESSION');
$class = [
    'grpcls_id' => $class['grpcls_id'],
    'grpcls_type' => $class['grpcls_type'],
    'grpcls_slug' => $class['grpcls_slug'],
    'user_full_name' => $class['user_full_name'],
    'grpcls_tlang_name' => implode(' / ', array_column($class['grpcls_tlang_name'], 'name')),
    'testat_ratings' => $class['testat_ratings'],
    'testat_reviewes' => $class['testat_reviewes'],
    'testat_students' => str_replace(['{n}'], [$class['testat_students']], Label::getLabel('LBL_{n}_STUDENTS')),
    'testat_sessions' => str_replace(['{n}'], [$class['testat_classes'] + $class['testat_lessons']], Label::getLabel('LBL_{n}_SESSIONS')),
    'user_username' => $class['user_username'],
    'user_country_name' => $class['user_country_name'],
    'user_country_photo' => MyUtility::makeFullUrl() . 'flags/' . strtolower($class['user_country_code']) . '.png',
    'grpcls_title' => $class['grpcls_title'],
    'grpcls_entry_fee' => MyUtility::formatMoney($class['grpcls_entry_fee']),
    'grpcls_description' => $class['grpcls_description'],
    'grpcls_sub_classes' => str_replace(['{n}'], [$class['grpcls_sub_classes']], Label::getLabel('LBL_{n}_CLASSES')),
    'grpcls_duration' => str_replace(['{n}'], [$class['grpcls_duration']], $durationLabel),
    'start_date' => MyDate::showDate($class['grpcls_start_datetime']),
    'start_time' => MyDate::showTime($class['grpcls_start_datetime']),
    'grpcls_start_datetime' => $class['grpcls_start_datetime'],
    'grpcls_banner' => MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_GROUP_CLASS_BANNER, $class['grpcls_id'], Afile::SIZE_LARGE]) . '?t=' . time(),
    'user_photo' => User::getPhoto($class['grpcls_teacher_id']),
    'grpcls_total_seats_text' => str_replace(['{n}'], [$class['grpcls_total_seats']], $seatsLabel),
    'grpcls_left_seats' => $class['grpcls_total_seats'] - $class['grpcls_booked_seats'],
    'grpcls_booked_seats' => $class['grpcls_booked_seats'],
    'grpcls_total_seats' => $class['grpcls_total_seats'],
    'grpcls_already_booked' => $class['grpcls_already_booked'],
    'bookBeforeTime' => intval($bookingGap),
    'canBookTill' => date('Y-m-d H:i:s', strtotime($class['grpcls_start_datetime'] . ' - ' . $bookingGap . ' minute')),
    'offers' => ($class['class_offer'] > 0) ? str_replace(['{duration}', '{percentages}'], [$class['grpcls_duration'], $class['class_offer']], $offerPriceLabel) : '',
    'userProfileLink' => MyUtility::makeFullUrl('Teachers', 'view', [$class['user_username']]),
    'groupClassLink' => MyUtility::makeFullUrl('GroupClasses', 'view', [$class['grpcls_slug']]),
    'canBookClass' => $class['canBookClass'],
    'offline_session' => $class['grpcls_offline'],
    'grpcls_address' => $class['grpcls_address']??new stdClass(),
    'grpcls_formatted_address' => isset($class['grpcls_address']) ? UserAddresses::format($class['grpcls_address']): '',
    'package_classes' => []
];
foreach ($pkgclses as $pkgcls) {
    $class['package_classes'][] = [
        'grpcls_title' => $pkgcls['grpcls_title'],
        'grpcls_duration' => str_replace(['{n}'], [$pkgcls['grpcls_duration']], $durationLabel),
        'start_date' => MyDate::showDate($pkgcls['grpcls_start_datetime']),
        'start_time' => MyDate::showTime($pkgcls['grpcls_start_datetime']),
    ];
}
$seatleft = $class['grpcls_total_seats'] - $class['grpcls_booked_seats'];
if ($seatleft < 10 && $seatleft > 0) {
    $class['grpcls_left_seats_text'] = str_replace('{seats}', $seatleft, Label::getLabel('LBL_HURRY_ONLY_{seats}_SEATS_LEFT'));
}
$class['msg'] = Label::getLabel('API_CLASS_DETAILS');
MyUtility::dieJsonSuccess($class);
