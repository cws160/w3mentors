<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$classes = [];
$durationLabel = Label::getLabel('LBL_({n}_MINS)');
$cancelDuration = FatApp::getConfig('CONF_CLASS_CANCEL_DURATION');
$reportIssueDuration = FatApp::getConfig('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION');
$escalateIssueDuration = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
foreach ($allClasses as $class) {
    $address = new stdClass();
    if (!empty($class['grpcls_address'])) {
        $address = [
            'usradd_id' => $class['grpcls_address']['usradd_id'],
            'usradd_latitude' => $class['grpcls_address']['usradd_latitude'],
            'usradd_longitude' => $class['grpcls_address']['usradd_longitude'],
            'usradd_address' => UserAddresses::format($class['grpcls_address']),
        ];
    }
    array_push($classes, [
        'plan' => $class['plan'],
        'ordcls_id' => $class['ordcls_id'],
        'grpcls_id' => $class['grpcls_id'],
        'grpcls_tlang_id' => $class['grpcls_tlang_id'],
        'grpcls_title' => $class['grpcls_title'],
        'grpcls_status' => $class['grpcls_status'],
        'ordcls_status' => $class['ordcls_status'],
        'order_type' => $class['order_type'],
        'user_name' => $class['teacher_first_name'] . ' ' . $class['teacher_last_name'],
        'isClassCanceled' => $class['isClassCanceled'],
        'canEdit' => $class['canEdit'],
        'showTimer' => $class['showTimer'],
        'grpcls_start_datetime' => $class['grpcls_start_datetime'],
        'grpcls_end_datetime' => $class['grpcls_end_datetime'],
        'grpcls_remaining_unix' => $class['grpcls_remaining_unix'],
        'grpcls_duration' => str_replace(['{n}'], [$class['grpcls_duration']], $durationLabel),
        'grpcls_startTime' => MyDate::showTime($class['grpcls_start_datetime']),
        'grpcls_startDate' => MyDate::showDate($class['grpcls_start_datetime']),
        'user_photo' => User::getPhoto($class['user_id']),
        'canRateClass' => $class['canRateClass'],
        'canReportClass' => $class['canReportClass'],
        'canCancelClass' => $class['canCancelClass'],
        'cancelTime' => $cancelDuration * 60,
        'reportIssueTime' => $reportIssueDuration * 60,
        'escalateIssueTime' => $escalateIssueDuration * 60,
        'canCancelTill' => date('Y-m-d H:i:s', strtotime($class['grpcls_start_datetime'] . ' - ' . $cancelDuration . ' hour')),
        'canReportIssueTill' => date('Y-m-d H:i:s', strtotime($class['grpcls_end_datetime'] . ' + ' . $reportIssueDuration . ' hour')),
        'canEscalateIssueTill' => 'To be updated',
        'repiss_id' => $class['repiss_id'],
        'playback_url' => $class['playback_url'],
        'offline_session' => $class['grpcls_offline'],
        'grpcls_address' => $address,
    ]);
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_CLASSES_LISTING'),
    'classes' => $classes,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
