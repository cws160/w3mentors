<?php

foreach ($reviews as &$review) {
    $review['user_image'] = User::getPhoto($review['ratrev_user_id']);
    $review['ratrev_created'] = MyDate::showDate($review['ratrev_created'], true, $siteLangId);
}

MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_TEACHERS_SEARCH_LISTING'),
    'reviews' => $reviews,
    'pageCount' => intval($pageCount),
    'recordCount' => intval($pageCount),
]);
