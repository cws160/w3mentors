<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$data['plan_level_text'] = Plan::getLevels($data['plan_level']);
$data['files'] = [];
$data['msg'] = Label::getLabel('API_LESSON_PLAN_DETAIL');
if (!empty($planFiles)) {
    foreach ($planFiles as $fileRow) {
        array_push($data['files'], ['url' => MyUtility::makeFullUrl('Image', 'downloadById', [$fileRow['file_id'], ucwords($fileRow['file_name'])], CONF_WEBROOT_FRONT_URL), 'name' => ucwords($fileRow['file_name'])]);
    }
}
MyUtility::dieJsonSuccess($data);
