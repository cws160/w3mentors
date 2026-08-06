<?php

$walletTitle = str_replace('{balance}', MyUtility::formatMoney($balance), Label::getLabel('LBL_WALLET_({balance})'));
$payMethods = [];
foreach ($methods as $value => $title) {
    $title = ($value == $walletPayId) ? $walletTitle : $title;
    array_push($payMethods, ['title' => $title, 'value' => $value]);
}
$labelstr = Label::getLabel('LBL_*_ALL_PURCHASES_ARE_IN_{currencycode}._FOREIGN_TRANSACTION_FEES_MIGHT_APPLY,_ACCORDING_TO_YOUR_BANK_POLICIES');
MyUtility::dieJsonSuccess([
    'methods' => $payMethods,
    'walletPayId' => $walletPayId,
    'msg' => Label::getLabel('API_PAYMENT_METHODS'),
    'wallet_balance' => FatUtility::float($balance),
    'wallet_balance_text' => MyUtility::formatMoney($balance),
    'payment_note' => str_replace("{currencycode}", $currency['currency_code'], $labelstr),
    'addAndPayText' => str_replace(['{remaining}'], [MyUtility::formatMoney($balance)], Label::getLabel('LBL_PAY_{remaining}_FROM_WALLET_BALANCE')),
    'min_amount' => FatUtility::int(FatApp::getConfig('MINIMUM_GIFT_CARD_AMOUNT')),
    'max_amount' => 99999,
]);
