<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$durationLabel = Label::getLabel('LBL_({n}_MINS)');
$seatsLabel = Label::getLabel('LBL_{n}_SEATS');
$classesLabel = Label::getLabel('LBL_{n}_CLASSES');
$offerPriceLabel = Label::getLabel('LBL_{percentages}%_OFF_ON_{duration}_MINUTES_SESSION');
foreach ($classes as $key => $class) {
    $class = [
        'grpcls_id' => $class['grpcls_id'],
        'grpcls_type' => $class['grpcls_type'],
        'grpcls_slug' => $class['grpcls_slug'],
        'user_full_name' => $class['user_full_name'],
        'grpcls_tlang_name' => implode(' / ', array_column($class['grpcls_tlang_name'], 'name')),
        'testat_ratings' => $class['testat_ratings'],
        'testat_reviewes' => $class['testat_reviewes'],
        'user_username' => $class['user_username'],
        'grpcls_title' => $class['grpcls_title'],
        'grpcls_entry_fee' => MyUtility::formatMoney($class['grpcls_entry_fee']),
        'grpcls_total_seats_text' => str_replace(['{n}'], [$class['grpcls_total_seats']], $seatsLabel),
        'grpcls_description' => $class['grpcls_description'],
        'grpcls_sub_classes' => str_replace(['{n}'], [$class['grpcls_sub_classes']], $classesLabel),
        'grpcls_duration' => str_replace(['{n}'], [$class['grpcls_duration']], $durationLabel),
        'start_date' => MyDate::showDate($class['grpcls_start_datetime']),
        'start_time' => MyDate::showTime($class['grpcls_start_datetime']),
        'grpcls_start_datetime' => $class['grpcls_start_datetime'],
        'user_photo' => User::getPhoto($class['grpcls_teacher_id']),
        'bookBeforeTime' => intval($bookingBefore),
        'grpcls_already_booked' => $class['grpcls_already_booked'],
        'canBookClass' => $class['canBookClass'],
        'offline_session' => $class['grpcls_offline'],
        'grpcls_address' => $class['grpcls_address']??new stdClass(),
        'grpcls_formatted_address' => (isset($class['grpcls_address']) && !empty($class['grpcls_address']))?UserAddresses::format($class['grpcls_address']):'',
        'canBookTill' => date('Y-m-d H:i:s', strtotime($class['grpcls_start_datetime'] . ' - ' . $bookingBefore . ' minute')),
        'offers' => ($class['class_offer'] > 0) ? str_replace(['{duration}', '{percentages}'], [$class['grpcls_duration'], $class['class_offer']], $offerPriceLabel) : '',
    ];
    $classes[$key] = $class;
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_CLASS_SEARCH_LISTING'),
    'classes' => $classes,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
    'post' => $post
]);
