<?php
$detail = [
    'frm' => MyUtility::convertFormToJson($frm, ['btn_submit', 'btn_reset']),
    'msg' => Label::getLabel('API_REFER_EARN'),
    'referralUrl' => MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL) . '?referral=' . $referCode,
    'shareText' => str_replace(['{full_name}', '{website_url}'], [$fullName, MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL)], Label::getLabel('ASR_SHARE_TEXT')),
    'creditBalance' => FatUtility::int($creditBalance),
];
MyUtility::dieJsonSuccess($detail);
