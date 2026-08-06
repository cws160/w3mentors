<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$offerPriceLabel = Label::getLabel('LBL_{percentages}%_OFF_ON_{duration}_MINUTES_SESSION');
$offers = [];
foreach ($teacher['offers'] as $duration => $offer) {
    array_push($offers, str_replace(['{duration}', '{percentages}'], [$duration, $offer], $offerPriceLabel));
}
$teacher['offers'] = $offers;
$teacher['user_photo'] = User::getPhoto($teacher['user_id']);
$teacher['user_teach_languages'] = $teacher['teacherTeachLanguageName'];
$teacher['user_price'] = MyUtility::formatMoney($teacher['testat_minprice']) . ' - ' . MyUtility::formatMoney($teacher['testat_maxprice']);
$teacher['user_country_photo'] = MyUtility::makeFullUrl() . 'flags/' . strtolower($teacher['user_country_code']) . '.png';
$teacher['testat_sessions'] = $teacher['testat_lessons'] + $teacher['testat_classes'];
$teacher['user_profile_link'] = MyUtility::makeFullUrl('Teachers', 'view', [$teacher['user_username']]);
$teacher['zoom_verification_check'] = Meeting::zoomVerificationCheck($teacher['user_id'], 0, true);
$speakLanguagesArr = explode(",", $teacher['spoken_language_names']);
$speakLanguagesProficiencyArr = explode(",", $teacher['spoken_languages_proficiency']);
$proficiencyArr = $teacher['proficiencyArr'];
$first = true;
$spokenLangs = '';
foreach ($speakLanguagesArr as $index => $spokenLangName) {
    if (isset($proficiencyArr[$speakLanguagesProficiencyArr[$index] ?? ''])) {
        $spokenLangs .= (!$first) ? ',' : '';
        $spokenLangs .= $spokenLangName . ' (' . $teacher['proficiencyArr'][$speakLanguagesProficiencyArr[$index]] . ')';
        $first = false;
    }
}
$preferences = [];
foreach ($preferencesType as $type => $preference) {
    if (empty($userPreferences[$type])) {
        continue;
    }
    $preferences[] = [
        'title' => $preference,
        'preferences' => array_column($userPreferences[$type], 'prefer_title'),
    ];
}
$teacherQualifications = [];
foreach ($qualificationType as $type => $name) {
    if (empty($userQualifications[$type])) {
        continue;
    }
    $qualifications = [];
    foreach ($userQualifications[$type] as $key => $value) {
        $qualifications[] = [
            'uqualification_title' => $value['uqualification_title'],
            'uqualification_institute_name' => $value['uqualification_institute_name'],
            'uqualification_institute_address' => $value['uqualification_institute_address'],
            'uqualification_start_year' => $value['uqualification_start_year'],
            'uqualification_end_year' => $value['uqualification_end_year']
        ];
    }

    $teacherQualifications[] = [
        'title' => $name,
        'qualifications' => $qualifications,
    ];
}
$teacherLangPrices = [];
foreach ($userLangData as $tlang) {
    $slabs = [];
    foreach ($teacher['user_slots'] as $slot) {
        array_push($slabs, [
            'slot' => $slot . ' ' . Label::getLabel('LBL_MINUTES'),
            'price' => MyUtility::formatMoney(MyUtility::slotPrice($tlang['utlang_price'], $slot))
        ]);
    }
    array_push($teacherLangPrices, ['tlang_name' => $tlang['tlang_name'], 'slabs' => $slabs]);
}

$durationLabel = Label::getLabel('LBL_({n}_MINS)');
$seatsLabel = Label::getLabel('LBL_{n}_SEATS');
$classesLabel = Label::getLabel('LBL_{n}_CLASSES');
$bookingGap = intval(FatApp::getConfig('CONF_CLASS_BOOKING_GAP'));
foreach ($classes as &$class) {
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
        'bookBeforeTime' => $bookingGap,
        'grpcls_already_booked' => $class['grpcls_already_booked'],
        'canBookClass' => $class['canBookClass'],
        'canBookTill' => date('Y-m-d H:i:s', strtotime($class['grpcls_start_datetime'] . ' - ' . $bookingGap . ' minute')),
    ];
}
foreach ($reviews as &$review) {
    $review['user_image'] = MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $review['ratrev_user_id'], Afile::SIZE_SMALL]);
    $review['ratrev_created'] = MyDate::formatDate($review['ratrev_created'], 'M d, Y H:i');
}
$teacher['detail_list'] = [
    [
        'title' => Label::getLabel('LBL_INTRODUCTION'),
        'layout_type' => 'introduction',
        'lists' => [
            [
                'title' => Label::getLabel('LBL_TEACH'),
                'value' => $teacher['teacherTeachLanguageName'],
            ],
            [
                'title' => Label::getLabel('LBL_SPEAK'),
                'value' => $spokenLangs,
            ],
            [
                'title' => Label::getLabel('LBL_ABOUT_THE_TUTOR'),
                'value' => $teacher['user_biography'],
            ],
        ]
    ],
    [
        'title' => Label::getLabel('LBL_LESSON_PRICES'),
        'layout_type' => 'price',
        'lists' => $teacherLangPrices
    ],
    [
        'title' => Label::getLabel('LBL_TEACHING_EXPERTISE'),
        'layout_type' => 'expertise',
        'lists' => $preferences
    ],
    [
        'title' => Label::getLabel('LBL_QUALIFICATIONS'),
        'layout_type' => 'qualifications',
        'lists' => $teacherQualifications
    ],
];
if (!empty($reviews)) {
    array_push($teacher['detail_list'], [
        'title' => Label::getLabel('LBL_REVIEWS'),
        'layout_type' => 'reviews',
        'lists' => $reviews
    ]);
}
if (FatApp::getConfig('CONF_GROUP_CLASSES_DISABLED', FatUtility::VAR_INT, 0)) {
    array_splice($teacher['detail_list'], 2, 0, [[
        'title' => Label::getLabel('LBL_GROUP_CLASSES'),
        'layout_type' => 'classes',
        'lists' => []
    ]]);
}
$teacher['user_trial_enabled'] = FatUtility::int($freeTrialEnabled);
$teacher['user_trial_availed'] = FatUtility::int($isFreeTrailAvailed);
$teacher['user_trial_duration'] = FatApp::getConfig('CONF_TRIAL_LESSON_DURATION', FatUtility::VAR_INT);
unset($teacher['testat_timeslots'], $teacher['teacherTeachLanguageName'], $teacher['proficiencyArr']);
$teacher['msg'] = Label::getLabel('API_TEACHER_PROFILE_DETAILS');

MyUtility::dieJsonSuccess($teacher);
