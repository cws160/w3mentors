<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($packages as &$package) {
    $package = [
        'package_id' => $package['grpcls_id'],
        'order_id' => $package['ordpkg_order_id'],
        'canCancel' => $package['canCancel'],
        'grpcls_title' => $package['grpcls_title'],
        'grpcls_start_datetime' => MyDate::showDate($package['grpcls_start_datetime']),
        'grpcls_start_time' => MyDate::showTime($package['grpcls_start_datetime']),
        'teacher_full_name' => $package['teacher_full_name'],
        'ordpkg_status' => $package['ordpkg_status'],
        'ordpkg_status_text' => OrderPackage::getStatuses($package['ordpkg_status']),
        'ordpkg_id' => $package['ordpkg_id'],
        'grpcls_sub_classes' => $classCounts[$package['grpcls_id']] ?? 0,
        'teacher_country' => $package['teacher_country'],
        'user_photo' => User::getPhoto($package['grpcls_teacher_id']),
        'offline_session' => $package['grpcls_offline']
    ];
}

MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_PACKAGE_LISTING'),
    'packages' => $packages,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
