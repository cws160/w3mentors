<?php

MyUtility::dieJsonSuccess([
    'fields' => ['classId', 'comment'],
    'refundText' => str_replace('{percent}', $refundPercentage, Label::getLabel('LBL_Refund_Would_Be_{percent}_Percent.')),
    'msg' => Label::getLabel('API_CANCEL_CLASS_FORM'),
]);
