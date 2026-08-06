<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$data['msg'] = Label::getLabel('API_TEACHER_AVAILABILITY');
MyUtility::dieJsonSuccess($data);
