<?php

$offerPriceLabel = Label::getLabel('LBL_{percentages}%_OFF_ON_{duration}_MINUTES_SESSION');
$naLabel = Label::getLabel('LBL_NA');
$packageOfferLabel = Label::getLabel('LBL_{percentages}%');
foreach ($teachers as &$teacher) {
    $teacher['teacher_country_name'] = $countries[$teacher['teacher_country_id']]['name'] ?? '';
    $teacher['user_country_photo'] = MyUtility::makeFullUrl() . 'flags/' . strtolower($countries[$teacher['teacher_country_id']]['user_country_code'] ?? '') . '.svg';
    $teacher['user_photo'] = FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['offpri_teacher_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME, '.jpg') . '?t=' . time();
    if (!empty($teacher['offpri_class_price'])) {
        foreach ($teacher['offpri_class_price'] as &$offer) {
            $offer['title'] = str_replace(['{duration}', '{percentages}'], [$offer['duration'], $offer['offer']], $offerPriceLabel);
        }
    }
    if (!empty($teacher['offpri_lesson_price'])) {
        foreach ($teacher['offpri_lesson_price'] as &$offer) {
            $offer['title'] = str_replace(['{duration}', '{percentages}'], [$offer['duration'], $offer['offer']], $offerPriceLabel);
        }
    }
    $teacher['package_offer_title'] = !empty($teacher['offpri_package_price']) ? str_replace('{percentages}', $teacher['offpri_package_price'], $packageOfferLabel) : $naLabel;
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_MY_TEACHERS'),
    'teachers' => array_values($teachers),
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
