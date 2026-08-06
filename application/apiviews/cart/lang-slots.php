<?php

$teachLangSlots = [];
foreach ($langslots as $key => $value) {
    $langSlots = [
        "name" => $value['name'],
        "id" => $value['id'],
        "slots" => []
    ];
    foreach ($value['slots'] as $slot) {
        array_push($langSlots['slots'], [
            'title' => str_replace('{slot}', $slot, Label::getLabel('LBL_{slot}_MINUTE')),
            'slot' => $slot,
        ]);
    }
    array_push($teachLangSlots, $langSlots);
}
$teachLangSlots['msg'] = Label::getLabel('API_LANGUAGE_AND_DURATION');
MyUtility::dieJsonSuccess($teachLangSlots);
