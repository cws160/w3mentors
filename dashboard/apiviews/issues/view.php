<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');

$userTypes = User::getUserTypes();
$userTypes[User::SUPPORT] = Label::getLabel('LBL_SUPPORT');

$issueLogs = [];
$issueLogs[] = [
    'user_fullname' => $issue['learner_full_name'],
    'title' => $issue['repiss_title'],
    'comments' => $issue['repiss_comment'],
    'reislo_added_on' => MyDate::showDate($issue['repiss_reported_on'], true),
    'user_type' => User::LEARNER,
    'user_type_text' => $userTypes[User::LEARNER],
];

foreach ($logs as $log) {
    array_push($issueLogs, [
        'user_fullname' => $log['user_fullname'],
        'title' => Issue::getActionsArr($log['reislo_action']),
        'comments' => $log['reislo_comment'],
        'reislo_added_on' => MyDate::showDate($log['reislo_added_on'], true),
        'user_type' => $log['reislo_added_by_type'],
        'user_type_text' => $userTypes[$log['reislo_added_by_type']],
    ]);
}

$details = [
    [
        'title' => Label::getLabel('LBL_ISSUE_LOGS'),
        'layout_type' => 'logs',
        'lists' => $issueLogs
    ],
    [
        'title' => (AppConstant::GCLASS == $issue['repiss_record_type']) ? Label::getLabel('LBL_CLASS_DETAILS') : Label::getLabel('LBL_LESSON_DETAILS'),
        'layout_type' => 'session',
        'lists' => [
            [
                'title' => Label::getLabel('LBL_ORDER_ID'),
                'value' => Order::formatOrderId($issue['ordles_order_id']),
            ],
            [
                'title' => (AppConstant::GCLASS == $issue['repiss_record_type']) ? Label::getLabel('LBL_CLASS_ID') : Label::getLabel('LBL_LESSON_ID'),
                'value' => $issue['ordles_id'],
            ],
            [
                'title' => Label::getLabel('LBL_PRICE'),
                'value' => MyUtility::formatMoney($issue['ordles_amount']),
            ]
        ]
    ],
    [
        'title' => Label::getLabel('LBL_TEACHER_DETAILS'),
        'layout_type' => 'teacher',
        'lists' => [
            [
                'title' => Label::getLabel('LBL_JOIN_TIME'),
                'value' => MyDate::showDate($issue['ordles_teacher_starttime'], true)
            ],
            [
                'title' => Label::getLabel('LBL_END_TIME'),
                'value' => MyDate::showDate($issue['ordles_teacher_endtime'], true)
            ]
        ]
    ],
    [
        'title' => Label::getLabel('LBL_LEARNER_DETAILS'),
        'layout_type' => 'learner',
        'lists' => [
            [
                'title' => Label::getLabel('LBL_JOIN_TIME'),
                'value' => MyDate::showDate($issue['ordles_student_starttime'], true)
            ],
            [
                'title' => Label::getLabel('LBL_END_TIME'),
                'value' => MyDate::showDate($issue['ordles_student_endtime'], true)
            ]
        ]
    ]
];
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_ISSUE_DETAILS'),
    'repiss_id' => $issue['repiss_id'],
    'repiss_reported_on' => MyDate::showDate($issue['repiss_reported_on']),
    'start_time' => MyDate::showDate($issue['ordles_lesson_starttime'], true),
    'repiss_title' => $issue['repiss_title'],
    'teacher_full_name' => $issue['teacher_full_name'],
    'repiss_status' => Issue::getStatusArr($issue['repiss_status']),
    'tlang_name' => $issue['ordles_tlang_name'],
    'canEscalateIssue' => $issue['canEscalateIssue'],
    'issue_details' => $details
]);
