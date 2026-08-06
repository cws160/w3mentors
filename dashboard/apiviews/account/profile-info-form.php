<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$imageExt = implode(", ", Afile::getAllowedExts(Afile::TYPE_USER_PROFILE_IMAGE));
$fileSize = MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_USER_PROFILE_IMAGE)) . ' MB';
$infoText = Label::getLabel('LBL_PROFILE_PICTURE_INFO_TEXT_{size}_{ext}');
$imageInfolabel = str_replace(['{size}', '{ext}'], [$fileSize, $imageExt], $infoText);
$fields = MyUtility::convertFormToJson($profileFrm, ['btn_reset', 'btn_submit', 'user_id', 'user_first_name', 'user_last_name', 'btn_next'], true);
MyUtility::dieJsonSuccess([
    'imageInfolabel' => $imageInfolabel,
    'genders' => $fields['user_gender']['options'],
    'countries' => $fields['user_country_id']['options'],
    'countries_code' => $fields['user_phone_code']['options'],
    'timezones' => $fields['user_timezone']['options'],
    'site_langs' => $fields['user_lang_id']['options'],
    'msg' => Label::getLabel('API_PROFILE_FORM')
]);
