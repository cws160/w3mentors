<?php

MyUtility::dieJsonSuccess([
    'txn_fee' => $txnFee,
    'balance' => $balance,
    'balance_text' => MyUtility::formatMoney($balance),
    'fields' => MyUtility::convertFormToJson($frm,[], true),
    'minimum_withdrawal_amount' => FatApp::getConfig("CONF_MIN_WITHDRAW_LIMIT"),
    'msg' => Label::getLabel('API_REQUEST_WITHDRAWAL')
]);
