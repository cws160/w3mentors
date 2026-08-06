<?php
$teachLanguge = $frm->getField('ordles_tlang_id');
$statusFld = $frm->getField('ordles_status');
$serviceTypeFld = $frm->getField('ordles_offline');
$langauges = [];
foreach ($teachLanguge->options as $value => $title) {
    array_push($langauges, [
        'title' => $title,
        'value' =>  $value
    ]);
}
$status = [];
foreach ($statusFld->options as $value => $title) {
    array_push($status, [
        'title' => $title,
        'value' =>  $value
    ]);
}
$serviceTypes = [];
foreach ($serviceTypeFld->options as $value => $title) {
    array_push($serviceTypes, [
        'title' => $title,
        'value' =>  $value
    ]);
}
MyUtility::dieJsonSuccess([
    'teach_language' => $langauges,
    'status' => $status,
    'ordles_offline' => $serviceTypes,
    'msg' => Label::getLabel('API_LESSON_SEARCH_FORM'),
]);