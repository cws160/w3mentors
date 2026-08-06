<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$options = [
    'user_lastseen' => Label::getLabel('LBL_ONLINE'),
    'user_featured' => Label::getLabel('LBL_FEATURED')
];
if (FatApp::getConfig('CONF_ENABLE_OFFLINE_SESSIONS')) {
    $options['user_offline_sessions'] = Label::getLabel('LBL_OFFLINE_SESSIONS');
}
$priceOptions = [
    floor(MyUtility::formatMoney($priceRange['minPrice'], false)) => Label::getLabel('LBL_PRICE_FROM'),
    ceil(MyUtility::formatMoney($priceRange['maxPrice'], false)) => Label::getLabel('LBL_PRICE_TILL')
];
$srchFrm->addSelectBox(Label::getLabel('LBL_OTHERS'), 'others', $options);
$srchFrm->addCheckBoxes(Label::getLabel('LBL_PRICES'), 'prices', $priceOptions);
$fields = MyUtility::convertFormToJson($srchFrm, [
    'keyword', 'price_from', 'price_till', 'btn_reset',
    'btn_submit', 'langslug', 'pageno', 'pagesize', 'user_lastseen', 'user_featured', 'user_offline_sessions'
]);
$fields['msg'] = Label::getLabel('API_TEACHERS_SEARCH_FORM');
MyUtility::dieJsonSuccess($fields);
