<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$offerPriceLabel = Label::getLabel('LBL_{percentages}%_OFF_ON_{duration}_MINUTES_SESSION');
foreach ($teachers as &$teacher) {
    $teacher['user_photo'] = User::getPhoto($teacher['user_id']);
    $teacher['user_teach_languages'] = $teacher['teacherTeachLanguageName'];
    $teacher['testat_sessions'] = $teacher['testat_lessons'] + $teacher['testat_classes'];
    $teacher['user_country_photo'] = MyUtility::makeFullUrl() . 'flags/' . strtolower($teacher['user_country_code']) . '.png';
    $teacher['user_price'] = MyUtility::formatMoney($teacher['testat_minprice']) . ' - ' . MyUtility::formatMoney($teacher['testat_maxprice']);
    $offers = [];
    foreach ($teacher['offers'] as $duration => $offer) {
        array_push($offers, str_replace(['{duration}', '{percentages}'], [$duration, $offer], $offerPriceLabel));
    }
    $teacher['offers'] = $offers;
    unset($teacher['user_biography']);
    unset($teacher['testat_timeslots']);
    unset($teacher['teacherTeachLanguageName']);
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_TEACHERS_SEARCH_LISTING'),
    'teachers' => $teachers,
    'post' => $post,
    'pageCount' => intval($pageCount),
    'recordCount' => intval($recordCount),
]);
