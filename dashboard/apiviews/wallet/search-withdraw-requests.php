<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
foreach ($requests as &$request) {
    $request['formattedWithdrawalId'] = WithdrawRequest::formatRequestNumber($request['withdrawal_id']);
    $request['withdrawal_request_date'] = MyDate::showDate($request['withdrawal_request_date'], true);
    $request['withdrawal_status_value'] = FatUtility::int($request['withdrawal_status']);
    $request['withdrawal_status'] = WithdrawRequest::getStatuses($request['withdrawal_status']);
    $request['withdrawal_amount'] = MyUtility::formatMoney($request['withdrawal_amount']);
    $request['withdrawal_transaction_fee'] = MyUtility::formatMoney($request['withdrawal_transaction_fee']);
}
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_WITHDRAWAL_LISTING'),
    'requests' => $requests,
    'balance' => $balance,
    'balance_text' => MyUtility::formatMoney($balance),
    'canWithdraw' => $canWithdraw,
    'recordCount' => intval($recordCount),
    'pageCount' => intval(ceil($recordCount / $post['pagesize'])),
]);
