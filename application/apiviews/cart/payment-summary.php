<?php

$pmethodField = $checkoutForm->getField('order_pmethod_id');
$summary = [];
foreach ($cartItems[Cart::LESSON] as $key => $value) {
    $summary = [
        [
            'title' => Label::getLabel('LBL_LESSON_QUANTITY'),
            'value' => str_replace(['{quantity}'], [$value['ordles_quantity']], Label::getLabel('LBL_{quantity}_LESSON'))
        ],
        [
            'title' => Label::getLabel('LBL_DURATION'),
            'value' => str_replace(['{duration}'], [$value['ordles_duration']], Label::getLabel('LBL_{duration}_MINS/LESSON')),
        ],
        [
            'title' => Label::getLabel('LBL_ITEM_PRICE'),
            'value' => str_replace(['{itemprice}'], [MyUtility::formatMoney($value['ordles_amount'])], Label::getLabel('LBL_{itemprice}/LESSON')),
        ],
        [
            'title' => Label::getLabel('LBL_TEACH_LANGUAGE'),
            'value' => $value['ordles_tlang']
        ]

    ];
    if ($value['ordles_address_id']) {
        $summary[] =    [
            'title' => Label::getLabel('LBL_ADDRESS'),
            'value' => $value['ordles_address']
        ];
    }
}
if (!empty($cartItems[Cart::SUBSCR])) {
    $subscr = $cartItems[Cart::SUBSCR];
    $summary = [
        [
            'title' => Label::getLabel('LBL_LESSON_QUANTITY'),
            'value' => str_replace(['{quantity}'], [$subscr['ordles_quantity']], Label::getLabel('LBL_{quantity}_LESSON')),
        ],
        [
            'title' => Label::getLabel('LBL_DURATION'),
            'value' => str_replace(['{duration}'], [$subscr['ordles_duration']], Label::getLabel('LBL_{duration}_MINS/LESSON')),
        ],
        [
            'title' => Label::getLabel('LBL_ITEM_PRICE'),
            'value' => str_replace(['{itemprice}'], [MyUtility::formatMoney($subscr['ordles_amount'])], Label::getLabel('LBL_{itemprice}/LESSON')),
        ],
        [
            'title' => Label::getLabel('LBL_TEACH_LANGUAGE'),
            'value' => $subscr['ordles_tlang']
        ]
    ];
    if ($subscr['ordles_address_id']) {
        $summary[] =    [
            'title' => Label::getLabel('LBL_ADDRESS'),
            'value' => $subscr['ordles_address']
        ];
    }
}
foreach ($cartItems[Cart::GCLASS] as $key => $class) {
    $summary = [
        [
            'title' => Label::getLabel('LBL_CLASS_NAME'),
            'value' => $class['grpcls_title']
        ],
        [
            'title' => Label::getLabel('LBL_ITEM_PRICE'),
            'value' => str_replace(['{itemprice}'], [MyUtility::formatMoney($class['ordcls_amount'])], Label::getLabel('LBL_{itemprice}/CLASS'))
        ],
        [
            'title' => Label::getLabel('LBL_START_TIME'),
            'value' => MyDate::formatDate($class['grpcls_start_datetime'])
        ],
        [
            'title' => Label::getLabel('LBL_END_TIME'),
            'value' => MyDate::formatDate($class['grpcls_end_datetime'])
        ]
    ];
    if ($class['grpcls_address_id']) {
        $summary[] =    [
            'title' => Label::getLabel('LBL_CLASS_ADDRESS'),
            'value' => $class['ordcls_address']
        ];
    }
}
foreach ($cartItems[Cart::PACKGE] as $key => $package) {
    $summary = [
        [
            'title' => Label::getLabel('LBL_PACKAGE_NAME'),
            'value' => $package['grpcls_title']
        ],
        [
            'title' => Label::getLabel('LBL_PACKAGE_PRICE'),
            'value' => str_replace(['{itemprice}'], [MyUtility::formatMoney($package['grpcls_amount'])], Label::getLabel('LBL_{itemprice}/PACKAGE'))
        ],
        [
            'title' => Label::getLabel('LBL_START_TIME'),
            'value' => MyDate::formatDate($package['grpcls_start_datetime'])
        ],
        [
            'title' => Label::getLabel('LBL_END_TIME'),
            'value' => MyDate::formatDate($package['grpcls_end_datetime'])
        ],
        [
            'title' => Label::getLabel('LBL_TOTAL_CLASSES'),
            'value' => count($package['classes'])
        ],
    ];
    if ($package['grpcls_address_id']) {
        $summary[] =    [
            'title' => Label::getLabel('LBL_PACKAGE_ADDRESS'),
            'value' => $package['ordcls_address']
        ];
    }
}


if (!empty($appliedCoupon['coupon_id'])) {
    array_push($summary, [
        'title' => Label::getLabel('LBL_COUPON_DISCOUNT'),
        'value' => MyUtility::formatMoney($appliedCoupon['coupon_discount'])
    ]);
}
if ($appliedReward == AppConstant::YES) {
    array_push($summary, [
        'title' => Label::getLabel('LBL_REWARD_DISCOUNT'),
        'value' => MyUtility::formatMoney($rewardDiscount)
    ]);
}
if ($addAndPay == AppConstant::YES && $walletBalance > 0 && $walletBalance < $cartNetAmount) {
    array_push($summary, [
        'title' => Label::getLabel('LBL_WALLET_DEDUCTION'),
        'value' => (string) MyUtility::formatMoney($walletBalance)
    ]);
    $cartNetAmount = $cartNetAmount - $walletBalance;
    array_push($summary, [
        'title' => Label::getLabel('LBL_NET_PAYABLE'),
        'value' => (string) MyUtility::formatMoney($cartNetAmount)
    ]);
} else {
    array_push($summary, [
        'title' => Label::getLabel('LBL_NET_PAYABLE'),
        'value' => (string) MyUtility::formatMoney($cartNetAmount)
    ]);
}
$paymentMethods = [];
foreach ($pmethodField->options as $id => $name) {
    $paymentMethods[] = [
        'id' => $id,
        'selected' => ($pmethodField->value == $id),
        'name' => ($id != $walletPayId) ? $name : str_replace(['{balance}'], [MyUtility::formatMoney($walletBalance)], Label::getLabel('LBL_WALLET_BALANCE_({balance})'))
    ];
}
$rewards = [
    'rewardBalance' => $rewardBalance,
    'rewardAmount' => MyUtility::formatMoney(round(RewardPoint::convertToValue($rewardBalance), 2)),
    'rewardDiscount' => MyUtility::formatMoney($rewardDiscount),
    'isRewardApplied' => $appliedReward
];

$paymentNote = str_replace("{currencycode}", $currencyData['currency_code'], Label::getLabel('LBL_ALL_PURCHASES_ARE_IN_{currencycode}'));
$paymentNote .= ' ' . Label::getLabel('LBL_FOREIGN_TRANSACTION_FEES_MIGHT_APPLY_ACCORDING_TO_YOUR_BANK_POLICIES');
MyUtility::dieJsonSuccess([
    'msg' => Label::getLabel('LBL_PAYMENT_SUMMARY'),
    'summary' => $summary,
    'paymentNote' => $paymentNote,
    'paymentMethods' => $paymentMethods,
    'walletBalance' => $walletBalance,
    'walletPayId' => $walletPayId,
    'availableCoupons' => array_values($availableCoupons),
    'isCouponApplied' => !empty($appliedCoupon) ? 1 : 0,
    'orderType' => intval($orderType),
    'netAmount' => $cartNetAmount,
    'addAndPay' => FatUtility::int($addAndPay),
    'addAndPayText' => str_replace(['{remaining}'], [MyUtility::formatMoney($walletBalance)], Label::getLabel('LBL_PAY_{remaining}_FROM_WALLET_BALANCE')),
    'appliedCoupon' => (!empty($appliedCoupon)) ? $appliedCoupon : new stdClass(),
    'rewards' => $rewards,
    'minimumRewardPointUsage' => FatUtility::int(FatApp::getConfig('CONF_REWARD_POINT_MINIMUM_USE')),
    'isRewardEnabled' => FatUtility::int(FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))
]);
