<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');

foreach ($records as $key => $value) {
    $value['formatted_address'] = UserAddresses::format($value);
    $records[$key] = $value;
}

MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_ADDRESS_LISTING'),
    'records' => $records
]);