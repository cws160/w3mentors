<?php

$methods = $form->getField('pmethod_id');
$methodOptions = [];
foreach ($methods->options as $value => $title) {
    array_push($methodOptions, ['title' => $title, 'value' => $value]);
}
$labelstr = Label::getLabel('LBL_*_ALL_PURCHASES_ARE_IN_{currencycode}._FOREIGN_TRANSACTION_FEES_MIGHT_APPLY,_ACCORDING_TO_YOUR_BANK_POLICIES');
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('API_MONEY_ADD_FORM'),
    'wallet_balance' => FatUtility::float($balance),
    'wallet_balance_text' => MyUtility::formatMoney($balance),
    'payment_methods' => $methodOptions,
    'payment_note' => str_replace("{currencycode}", $currency['currency_code'], $labelstr)
]);
