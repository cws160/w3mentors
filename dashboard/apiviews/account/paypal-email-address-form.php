<?php

$fields = MyUtility::convertFormToJson($frm, ['btn_reset', 'btn_submit', 'btn_back', 'btn_next'], true);
MyUtility::dieJsonSuccess($fields + ['msg' => Label::getLabel('LBL_PAYPAL_EMAIL_ADDRESS_FORM')]);
