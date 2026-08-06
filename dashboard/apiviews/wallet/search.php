<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($txns as &$txn) {
    $txn['usrtxn_transaction_id'] = Transaction::formatTxnId($txn['usrtxn_id']);
    $txn['usrtxn_datetime'] = MyDate::showDate(date('M d, Y H:i:s', strtotime($txn['usrtxn_datetime'])), true);
    $txn['usrtxn_type'] = Transaction::getTypes($txn['usrtxn_type']);
    $txn['usrtxn_amount'] = floatval($txn['usrtxn_amount']);
    $txn['usrtxn_comment'] = strval($txn['usrtxn_comment'] . ' ');
    $txn['usrtxn_format_amount'] = MyUtility::formatMoney($txn['usrtxn_amount']);
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_TRANSACTION_LISTING'),
    'transaction' => $txns,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
    'user_wallet_balance' => MyUtility::formatMoney($siteUser['user_wallet_balance']),
    'minimum_recharge' => intval(FatApp::getConfig('MINIMUM_WALLET_RECHARGE_AMOUNT')),
    'maximum_recharge' => 999999
]);
