<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$issueStatusArr = Issue::getStatusArr();
foreach ($lessons as &$issue) {
    $issue = [
        'repiss_id' => $issue['repiss_id'],
        'repiss_reported_on' => MyDate::showDate($issue['repiss_reported_on']),
        'start_time' => MyDate::showDate($issue['ordles_lesson_starttime'], true),
        'repiss_title' => $issue['repiss_title'],
        'teacher_full_name' => $issue['teacher_full_name'],
        'repiss_status' => $issueStatusArr[$issue['repiss_status']],
        'tlang_name' => $issue['ordles_tlang_name'],
    ];
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_ISSUE_REPORT_LISTING'),
    'issues' => $lessons,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
