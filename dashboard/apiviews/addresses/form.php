<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$fields = MyUtility::convertFormToJson($frm, ['btn_reset', 'btn_submit', 'btn_next'], true);
MyUtility::dieJsonSuccess([
    'fields' => $fields,
    'info_text' => Label::getLabel('LBL_INFO_TEXT'),
    'msg' => Label::getLabel('API_ADDRESS_FORM')
]);
