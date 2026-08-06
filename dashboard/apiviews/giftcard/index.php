<?php

$fields = MyUtility::convertFormToJson($frm);
$fields['msg'] = Label::getLabel('API_GIFTCARD_SEARCH_FORM');
MyUtility::dieJsonSuccess($fields);
