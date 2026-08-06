<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$issueStatusArr = Issue::getStatusArr();
$favourites = [];
foreach ($favouritesData['Favourites'] as $favourite) {
    array_push($favourites, [
        'teacherTeachLanguageName' => $favourite['teacherTeachLanguageName'],
        'uft_teacher_id' => $favourite['uft_teacher_id'],
        'user_username' => $favourite['user_username'],
        'user_first_name' => $favourite['user_first_name'],
        'user_last_name' => $favourite['user_last_name'],
        'country_name' => $countriesArr[$favourite['user_country_id']],
        'user_photo' => FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $favourite['uft_teacher_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME, '.jpg') . '?t=' . time(),
    ]);
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_FAVOURITE_LISTING'),
    'favourites' => $favourites,
    'recordCount' => intval($favouritesData['pagingArr']['recordCount']),
    'pageCount' => intval($favouritesData['pagingArr']['pageCount']),
]);
