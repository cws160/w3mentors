<?php

MyUtility::dieJsonSuccess([
    'fields' => ['ordles_id', 'comment'],
    'refundText' => ($lesson['ordles_type'] == LESSON::TYPE_FTRAIL ? '' : str_replace('{percent}', $refundPercentage, Label::getLabel('LBL_Refund_Would_Be_{percent}_Percent.'))),
    'msg' => Label::getLabel('API_CANCEL_LESSON_FORM'),
]);
