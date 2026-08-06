<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
MyUtility::dieJsonSuccess([
    'rows' => $records,
    'msg' => Label::getLabel('API_REFER_SEARCH_LIST'),
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
