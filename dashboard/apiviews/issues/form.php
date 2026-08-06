<?php
$titleOptions = $frm->getField('repiss_title');
$titles = [];
foreach ($titleOptions->options as $value => $title) {
    array_push($titles, [
        'title' => $title,
        'value' =>  $value
    ]);
}
MyUtility::dieJsonSuccess([
    'titles' => $titles,
    'msg' => Label::getLabel('API_REPORT_ISSUE_FORM'),
]);