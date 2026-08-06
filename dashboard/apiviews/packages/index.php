<?php
$fields  = MyUtility::convertFormToJson($frm);
$fields['msg'] =  Label::getLabel('API_CLASS_SEARCH_FORM');
MyUtility::dieJsonSuccess($fields);
