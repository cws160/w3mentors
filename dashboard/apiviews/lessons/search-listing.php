<?php

$lessons = [];
$cancelDuration = FatApp::getConfig('CONF_LESSON_CANCEL_DURATION');
$rescheduleDuration = FatApp::getConfig('CONF_LESSON_RESCHEDULE_DURATION');
$reportIssueDuration = FatApp::getConfig('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION');
$escalateIssueDuration = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
$durationLabel = Label::getLabel('LBL_({n}_MINS)');
$lessonTitle = Label::getLabel('LBL_{title}_WITH_{name}');
foreach ($allLessons as $lesson) {
    array_push($lessons, [
        'plan' => $lesson['plan'],
        'ordles_id' => $lesson['ordles_id'],
        'ordles_title' => $lesson['lessonTitle'],
        'ordles_teacher_id' => $lesson['ordles_teacher_id'],
        'ordles_tlang_name' => $lesson['ordles_tlang_name'],
        'status' => $lesson['ordles_status'],
        'order_type' => $lesson['order_type'],
        'user_name' => $lesson['first_name'] . ' ' . $lesson['last_name'],
        'ordles_lesson_time_info' => $lesson['ordles_lesson_time_info'],
        'ordles_lesson_starttime' => $lesson['ordles_lesson_starttime'],
        'ordles_lesson_endtime' => $lesson['ordles_lesson_endtime'],
        'ordles_currenttime_unix' => $lesson['ordles_currenttime_unix'],
        'ordles_starttime_unix' => FatUtility::int($lesson['ordles_starttime_unix']),
        'ordles_endtime_unix' => FatUtility::int($lesson['ordles_endtime_unix']),
        'ordles_remaining_unix' => ($lesson['ordles_remaining_unix'] >= 0) ? $lesson['ordles_remaining_unix'] : 0,
        'ordles_endtime_remaining_unix' => ($lesson['ordles_endtime_remaining_unix'] >= 0) ? $lesson['ordles_endtime_remaining_unix'] : 0,
        'ordles_duration' => $lesson['ordles_duration'],
        'ordles_duration_lbl' => str_replace(['{n}'], [$lesson['ordles_duration']], $durationLabel),
        'ordles_startTime' => MyDate::showTime($lesson['ordles_lesson_starttime']),
        'ordles_startDate' => MyDate::showDate($lesson['ordles_lesson_starttime']),
        'user_photo' => User::getPhoto($lesson['user_id']),
        'canRateLesson' => $lesson['canRateLesson'],
        'canReportIssue' => $lesson['canReportIssue'],
        'canCancelLesson' => $lesson['canCancelLesson'],
        'canScheduleLesson' => $lesson['canScheduleLesson'],
        'canRescheduleLesson' => $lesson['canRescheduleLesson'],
        'cancelTime' => $cancelDuration * 60,
        'rescheduleTime' => $rescheduleDuration * 60,
        'reportIssueTime' => $reportIssueDuration * 60,
        'escalateIssueTime' => $escalateIssueDuration * 60,
        'canCancelTill' => date('Y-m-d H:i:s', strtotime($lesson['ordles_lesson_starttime'] . ' - ' . $cancelDuration . ' hour')),
        'canRescheduleTill' => date('Y-m-d H:i:s', strtotime($lesson['ordles_lesson_starttime'] . ' - ' . $rescheduleDuration . ' hour')),
        'canReportIssueTill' => date('Y-m-d H:i:s', strtotime($lesson['ordles_lesson_endtime'] . ' + ' . $reportIssueDuration . ' hour')),
        'canEscalateIssueTill' => 'To be updated',
        'repiss_id' => $lesson['repiss_id'],
        'playback_url' => $lesson['playback_url'],
        'ordles_address' => $lesson['ordles_address'],
        'ordles_offline' => $lesson['ordles_offline']
    ]);
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_LESSONS_LISTING'),
    'lessons' => $lessons,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
