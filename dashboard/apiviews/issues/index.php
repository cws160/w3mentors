<?php
$fields  = MyUtility::convertFormToJson($frm);
$fields['msg'] =  Label::getLabel('API_ISSUE_REPORT_SEARCH_FORM');
MyUtility::dieJsonSuccess($fields);