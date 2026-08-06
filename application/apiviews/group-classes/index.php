<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$options = [];
if (FatApp::getConfig('CONF_ENABLE_OFFLINE_SESSIONS')) {
    $options = [
        'grpcls_offline' => Label::getLabel('LBL_OFFLINE_SESSIONS'),
    ];
}
$srchFrm->addSelectBox(Label::getLabel('LBL_OTHERS'), 'others', $options);
$fields = MyUtility::convertFormToJson($srchFrm, ['keyword', 'btn_reset', 'btn_submit', 'pageno', 'pagesize',  'grpcls_offline']);
$fields['msg'] = Label::getLabel('API_GROUP_CLASS_SEARCH_FORM');
MyUtility::dieJsonSuccess($fields);
