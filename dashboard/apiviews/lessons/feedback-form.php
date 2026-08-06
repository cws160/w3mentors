<?php
$overallRating = $frm->getField('ratrev_overall');
$ratings = [];
foreach ($overallRating->options as $value => $title) {
    array_push($ratings, [
        'title' => $title,
        'value' =>  $value
    ]);
}
MyUtility::dieJsonSuccess([
    'ratings' => $ratings,
    'msg' => Label::getLabel('API_REVIEW_FORM'),
]);