<?php
$upcomingSession = [];
$lessonStartUnix = null;
$lessons = $classes = $homeSlides = [];
$durationLabel = Label::getLabel('LBL_({n}_MINS)');
if (!empty($upComingLessons)) {
    $cancelDuration = FatApp::getConfig('CONF_LESSON_CANCEL_DURATION');
    $rescheduleDuration = FatApp::getConfig('CONF_LESSON_RESCHEDULE_DURATION');
    $reportIssueDuration = FatApp::getConfig('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION');
    $escalateIssueDuration = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
    $lessonTitle = Label::getLabel('LBL_{title}_WITH_{name}');
    foreach ($upComingLessons as $lesson) {
        array_push($lessons, [
            'ordles_id' => $lesson['ordles_id'],
            'user_id' => $lesson['user_id'],
            'plan' => $lesson['plan'],
            'ordles_title' => $lesson['lessonTitle'],
            'ordles_teacher_id' => $lesson['ordles_teacher_id'],
            'ordles_tlang_name' => $lesson['ordles_tlang_name'],
            'status' => $lesson['ordles_status'],
            'country_name' => $lesson['country_name'],
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
            'ordles_startDate' => MyDate::showDate($lesson['ordles_lesson_starttime']),
            'ordles_startTime' => MyDate::showTime($lesson['ordles_lesson_starttime']),
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
            'ordles_offline' => $lesson['ordles_offline'],
            'ordles_address' => $lesson['ordles_address'],
        ]);
    }
    $upcomingSession = [
        'id' => $lessons[0]['ordles_id'],
        'remaining_unix' => $lessons[0]['ordles_remaining_unix'],
        'ordles_starttime_unix' => $lessons[0]['ordles_starttime_unix'],
        'user_name' => $lessons[0]['user_name'],
        'order_type' => $lessons[0]['order_type'],
        'user_country_name' => $lessons[0]['country_name'],
        'duration' => str_replace(['{n}'], [$lessons[0]['ordles_duration']], $durationLabel),
        'ordles_lesson_starttime' => $lesson['ordles_lesson_starttime'],
        'ordles_lesson_endtime' => $lesson['ordles_lesson_endtime'],
        'startDate' => MyDate::showDate($lesson['ordles_lesson_starttime']),
        'startTime' => MyDate::showTime($lesson['ordles_lesson_starttime']),
        'title' => str_replace(['{title}', '{name}'], [$lessons[0]['ordles_title'], $lessons[0]['user_name']], $lessonTitle),
        'user_photo' => User::getPhoto($lessons[0]['user_id']),
        'offline_session' => $lessons[0]['ordles_offline'],
    ];
    $lessonStartUnix = $lessons[0]['ordles_starttime_unix'];
}
if (!empty($bookedClasses)) {
    $cancelDuration = FatApp::getConfig('CONF_CLASS_CANCEL_DURATION');
    $reportIssueDuration = FatApp::getConfig('CONF_REPORT_ISSUE_HOURS_AFTER_COMPLETION');
    $escalateIssueDuration = FatApp::getConfig('CONF_ESCALATED_ISSUE_HOURS_AFTER_RESOLUTION');
    foreach ($bookedClasses as $class) {
        array_push($classes, [
            'id' => $class['ordcls_id'],
            'plan' => $class['plan'],
            'title' => $class['grpcls_title'],
            'order_type' => $class['order_type'],
            'canEdit' => $class['canEdit'],
            'remaining_unix' => $class['grpcls_remaining_unix'],
            'grpcls_start_datetime' => $class['grpcls_start_datetime'],
            'grpcls_end_datetime' => $class['grpcls_end_datetime'],
            'grpcls_starttime_unix' => $class['grpcls_starttime_unix'],
            'duration' => str_replace(['{n}'], [$class['grpcls_duration']], $durationLabel),
            'startDate' => MyDate::showDate($class['grpcls_start_datetime']),
            'startTime' => MyDate::showTime($class['grpcls_start_datetime']),
            'user_name' => $class['teacher_first_name'] . ' ' . $class['teacher_last_name'],
            'user_country_name' => $class['teacher_country'],
            'user_photo' => FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $class['grpcls_teacher_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME, '.jpg') . '?t=' . time(),
            'grpcls_slug' => $class['grpcls_slug'],
            'canRateClass' => $class['canRateClass'],
            'canReportClass' => $class['canReportClass'],
            'canCancelClass' => $class['canCancelClass'],
            'cancelTime' => $cancelDuration * 60,
            'reportIssueTime' => $reportIssueDuration * 60,
            'escalateIssueTime' => $escalateIssueDuration * 60,
            'canCancelTill' => date('Y-m-d H:i:s', strtotime($class['grpcls_start_datetime'] . ' - ' . $cancelDuration . ' hour')),
            'canReportIssueTill' => date('Y-m-d H:i:s', strtotime($class['grpcls_end_datetime'] . ' + ' . $reportIssueDuration . ' hour')),
            'canEscalateIssueTill' => 'To be updated',
            'offline' => $class['grpcls_offline'],
            'address' => $class['ordcls_address'],
        ]);
    }
    $classStartUnix = $classes[0]['grpcls_starttime_unix'];
    if (empty($upComingLessons) || (!empty($lessonStartUnix) && $lessonStartUnix > $classStartUnix)) {
        $upcomingSession = $classes[0];
        $upcomingSession['offline_session'] = $upcomingSession['offline'];
        unset($upcomingSession['offline']);
    }
}

foreach ($topRatedTeachers as &$teacher) {
    $teacher['user_photo'] = FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['user_id'], Afile::SIZE_MEDIUM], CONF_WEBROOT_FRONT_URL), CONF_IMG_CACHE_TIME, '.jpg') . '?t=' . time();
    $teacher['user_country_name'] = $teacher['country_name']['name'];
}

foreach ($payoutMethods as &$payoutMethod) {
    $payoutMethod['pmethod_fees'] = json_decode(html_entity_decode($payoutMethod['pmethod_fees']), true);
    if (!empty($payoutMethod['pmethod_fees']['fee'])) {
        $payoutFee = MyUtility::formatMoney($payoutMethod['pmethod_fees']['fee']);
        if ($payoutMethod['pmethod_fees']['type'] == AppConstant::PERCENTAGE) {
            $payoutFee = MyUtility::formatPercent($payoutMethod['pmethod_fees']['fee']);
        }
        $payoutMethod['pmethod_fees']['fee'] = $payoutFee;
    }
}
$siteUser['user_offline_sessions'] = (int)FatApp::getConfig('CONF_ENABLE_OFFLINE_SESSIONS');
$siteUser['isProfileImageEmpty'] = empty($userImage);
$siteUser['payoutMethods'] = array_values($payoutMethods);
$siteUser['user_wallet_balance'] = MyUtility::formatMoney($siteUser['user_wallet_balance']);
$siteUser['user_timezone_text'] = MyDate::timeZoneListing()[$siteUser['user_timezone']] ?? Label::getLabel('LBL_NA');
$siteUser['user_photo'] = User::getPhoto($siteUserId);
$siteUser['enable_refer_rewards'] =  FatUtility::int(FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'));
$siteUser['zoom_verification_required'] = false;
if (isset($zoomVerificationStatus)) {
    $siteUser['zoom_verification_status'] = $zoomVerificationStatus;
}
unset($siteUser['profile_progress'], $siteUser['user_google_id'], $siteUser['user_facebook_id'], $siteUser['user_google_token'], $siteUser['user_facebook_token']);
$lists = [
    [
        'title' => Label::getLabel('LBL_SCHEDULED_LESSONS'),
        'value' => $schLessonCount,
        'type' => 'scheduled_lessons'
    ],
    [
        'title' => Label::getLabel('LBL_TOTAL_LESSONS'),
        'value' => $totalLesson,
        'type' => 'total_lessons',
    ],
    [
        'title' => Label::getLabel('LBL_WALLET_BALANCE'),
        'value' => MyUtility::formatMoney($walletBalance),
        'type' => 'wallet_balance'
    ]
];
if (FatApp::getConfig('CONF_GROUP_CLASSES_DISABLED', FatUtility::VAR_INT, 0)) {
    $lists[] = [
        'title' => Label::getLabel('LBL_TOTAL_CLASSES'),
        'value' => $totalClasses,
        'type' => 'total_classes',
    ];
}
if (isset($slides) && count($slides)) {
    foreach ($slides as $slide) {
        $slideImage = $slideImages[$slide['slide_id']]??[];
        $slide['image'] = MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_HOME_BANNER_MOBILE, $slide['slide_id'], Afile::SIZE_LARGE],CONF_WEBROOT_FRONT_URL);
        $homeSlides[] = $slide;
    }
}
if (!empty($upComingClasses)) {
    foreach ($upComingClasses as $key => &$class) {
        $class['user_photo'] =  FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $class['user_photo'], Afile::SIZE_LARGE], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME, '.jpg') . '?t=' . time();
        $class['grpcls_banner'] =  FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_GROUP_CLASS_BANNER, $class['grpcls_id'], Afile::SIZE_LARGE], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME, '.jpg') . '?t=' . time();
        $class['startDate'] = MyDate::showDate($class['grpcls_start_datetime']);
        $class['startTime'] = MyDate::showTime($class['grpcls_end_datetime']);
        $class['grpcls_entry_fee'] = MyUtility::formatMoney($class['grpcls_entry_fee']);
        $class['testat_ratings'] = round($class['testat_ratings'], 1);
    }
}
$languages = [];
if (!empty($popularLanguages)) {
    foreach ($popularLanguages as $key => $popularLanguage) {
        array_push($languages, [
            'tlang_img' => FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_TEACHING_LANGUAGES, $popularLanguage['tlang_id'], Afile::SIZE_LARGE],CONF_WEBROOT_FRONT_URL), CONF_IMG_CACHE_TIME, '.jpg') . '?t=' . time(),
            'tlang_id' => $popularLanguage['tlang_id'],
            'tlang_name' => $popularLanguage['tlang_name'],
            'tlang_slug' => $popularLanguage['tlang_slug'],
            'tutor_count' => $popularLanguage['tutor_count'],
        ]);
    }
}

$protocol = (FatApp::getConfig('CONF_USE_SSL')) ? 'https://' : 'http://';
$bannerImg =  $protocol . $_SERVER['SERVER_NAME'] . urldecode('/images/2000x600.jpg');

$detail = [
    [
        'title' => 'Profile Info',
        'layout_type' => 'profile',
        'value' => $siteUser
    ],
    [
        'title' => Label::getLabel('LBL_SLIDES'),
        'layout_type' => 'slides',
        'lists' => $homeSlides
    ],
    [
        'title' => Label::getLabel('LBL_NEXT_SESSION'),
        'layout_type' => 'next_session',
        'lists' => empty($upcomingSession) ? [] : [$upcomingSession]
    ],
    [
        'title' => Label::getLabel('LBL_POPULAR_LANGUAGES'),
        'layout_type' => 'popular_languages',
        'lists' => $languages
    ],
    [
        'title' => Label::getLabel('LBL_MY_CLASSES'),
        'layout_type' => 'classes',
        'lists' => (FatApp::getConfig('CONF_GROUP_CLASSES_DISABLED', FatUtility::VAR_INT, 0) == 1) ? $classes : []
    ],
    [
        'title' => Label::getLabel('API_DASHBOARD_FIND_TUTORS_TEXT'),
        'layout_type' => 'findtutors'
    ],
    [
        'title' => Label::getLabel('LBL_BANNER_IMAGE'),
        'layout_type' => 'banner_image',
        'lists' => [[
            'text' => Label::getLabel('LBL_BANNER_IMAGE_TEXT_MOBILE'),
            'img' => $bannerImg
        ]]
    ],
    [
        'title' => Label::getLabel('LBL_TOP_RATED_TEACHER'),
        'layout_type' => 'topteachers',
        'lists' => array_values($topRatedTeachers)
    ],
    [
        'title' => Label::getLabel('LBL_UPCOMING_CLASSES'),
        'layout_type' => 'upcoming_classes',
        'lists' => (FatApp::getConfig('CONF_GROUP_CLASSES_DISABLED', FatUtility::VAR_INT, 0) == 1) ? $upComingClasses : []
    ],
    [
        'title' => Label::getLabel('LBL_STATS'),
        'layout_type' => 'stats',
        'lists' => $lists
    ],
    [
        'title' => Label::getLabel('LBL_MY_LESSONS'),
        'layout_type' => 'lessons',
        'lists' => $lessons
    ]
];
if ((isset($zoomVerificationStatus) && $zoomVerificationRequired) && (isset($zoomVerificationStatus) && ($zoomVerificationStatus = ZoomMeeting::ACC_SYNCED_AND_VERIFIED))) {
    array_splice($detail,3, 0, [[
        'title' => Label::getLabel('LBL_ZOOM_STATUS'),
        'layout_type' => 'zoom_status',
        'lists' => []
    ]]);
}
if (FatApp::getConfig('CONF_GROUP_CLASSES_DISABLED', FatUtility::VAR_INT, 0)) {
    array_splice($detail, 2, 0, [[
        'title' => Label::getLabel('LBL_ZOOM_STATUS'),
        'layout_type' => 'zoom_status',
        'lists' => []
    ]]);
}

$detail['msg'] = Label::getLabel('API_DASHBOARD_DETAILS');
MyUtility::dieJsonSuccess($detail);
