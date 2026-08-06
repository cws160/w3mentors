<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($cards as &$card) {
    $card['flashcard_addedon'] = MyDate::showDate($card['flashcard_addedon'], true);
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_NOTES_LISTING'),
    'notes' => $cards,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $postedData['pagesize'])),
]);
