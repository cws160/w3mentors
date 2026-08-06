<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$package = [
    'grpcls_title' => $package['grpcls_title'],
    'grpcls_start_datetime' => MyDate::showDate($package['grpcls_start_datetime']),
    'grpcls_start_time' => MyDate::showTime($package['grpcls_start_datetime']),
    'teacher_full_name' => $package['teacher_full_name'],
    'teacher_country' => $package['teacher_country'],
    'ordpkg_status_text' => OrderPackage::getStatuses($package['ordpkg_status']),
    'user_photo' => MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $package['grpcls_teacher_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL) . '?t=' . time(),
    'sub_classes' => []
];

foreach ($subclasses as &$class) {
    array_push($package['sub_classes'], [
        'grpcls_title' => $class['grpcls_title'],
        'grpcls_startDate' => MyDate::showDate($class['grpcls_start_datetime']),
        'grpcls_startTime' => MyDate::showTime($class['grpcls_start_datetime']),
        'grpcls_duration' => str_replace(['{n}'], [$class['grpcls_duration']], Label::getLabel('LBL_({n}_MINS)')),
        'grpcls_offline' => $class['grpcls_offline']
    ]);
}
$package['msg'] = Label::getLabel('API_PACKAGE_DETAILS');
MyUtility::dieJsonSuccess($package);
