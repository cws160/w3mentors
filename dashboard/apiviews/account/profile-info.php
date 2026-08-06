<?php

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
$siteUser['msg'] = Label::getLabel('LBL_PROFILE_INFO');
unset($siteUser['profile_progress'], $siteUser['user_google_id'], $siteUser['user_facebook_id'], $siteUser['user_google_token'], $siteUser['user_facebook_token']);
MyUtility::dieJsonSuccess($siteUser);
