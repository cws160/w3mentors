<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$techOrders = [Order::TYPE_LESSON, Order::TYPE_SUBSCR, Order::TYPE_GCLASS, Order::TYPE_PACKGE];
$order['order_type_text'] = Order::getTypeArr($order['order_type']);
$order['order_format_id'] = Order::formatOrderId($order['order_id']);
$order['order_addedon'] = MyDate::showDate($order['order_addedon'], true);
$order['order_status_text'] = Order::getStatusArr($order['order_status']);
$order['order_payment_status'] = Order::getPaymentArr($order['order_payment_status']);
$order['order_total_amount'] = MyUtility::formatMoney($order['order_total_amount']);
$order['order_discount_value'] = MyUtility::formatMoney($order['order_discount_value']);
$order['order_reward_value'] = MyUtility::formatMoney($order['order_reward_value']);
$order['order_net_amount'] = MyUtility::formatMoney($order['order_net_amount']);
$order['order_details'] = [];
$items = [];
foreach ($subOrders as $subOrder) {
    if (in_array($order['order_type'], [Order::TYPE_LESSON, Order::TYPE_SUBSCR])) {
        array_push($items, [
            'title' => str_replace('{id}', $subOrder['ordles_id'], Label::getLabel('LBL_LESSON_ID-{id}')),
            'status' => Lesson::getStatuses($subOrder['ordles_status']),
            'start_time' => MyDate::showDate($subOrder['ordles_lesson_starttime'], true),
            'end_time' => MyDate::showDate($subOrder['ordles_lesson_endtime'], true),
        ]);
    } else if (in_array($order['order_type'], [Order::TYPE_GCLASS, Order::TYPE_PACKGE])) {
        array_push($items, [
            'title' => str_replace('{id}', $subOrder['ordcls_id'], Label::getLabel('LBL_CLASS_ID_-{id}')),
            'status' => OrderClass::getStatuses($subOrder['ordcls_status']),
            'start_time' => MyDate::showDate($subOrder['grpcls_start_datetime'], true),
            'end_time' => MyDate::showDate($subOrder['grpcls_end_datetime'], true),
        ]);
    }
}
if (in_array($order['order_type'], $techOrders)) {
    $order['order_item_count'] = count($items);
    array_push($order['order_details'], [
        'title' => str_replace('{n}', $order['order_item_count'], Label::getLabel('LBL_ITEMS_IN_ORDER_{n}')),
        'layout_type' => 'items',
        'lists' => $items
    ]);
}
$summaryItems = [
    [
        'title' => Label::getLabel('LBL_ORDER_ID'),
        'value' => Order::formatOrderId($order['order_id'])
    ],
    [
        'title' => Label::getLabel('LBL_TYPE'),
        'value' => Order::getTypeArr($order['order_type'])
    ]
];
switch ($order['order_type']) {
    case Order::TYPE_LESSON:
    case Order::TYPE_SUBSCR:
        array_push($summaryItems,
                [
                    'title' => Label::getLabel('LBL_LANGUAGE'),
                    'value' => is_null($subOrder['tlang_name']) ? Label::getLabel('LBL_FREE_TRAIL') : $subOrder['tlang_name'],
                ],
                [
                    'title' => Label::getLabel('LBL_TIME_SLOTS'),
                    'value' => str_replace('{n}', $subOrder['ordles_duration'], Label::getLabel('LBL_{n}_MINS')),
                ],
                [
                    'title' => Label::getLabel('LBL_QUANTITY'),
                    'value' => count($subOrders),
                ],
                [
                    'title' => Label::getLabel('LBL_PRICE'),
                    'value' => MyUtility::formatMoney($subOrder['ordles_amount']) . '/' . Label::getLabel('LBL_Lesson'),
                ],
                [
                    'title' => Label::getLabel('LBL_LESSON_TYPE'),
                    'value' => (int)$subOrder['is_offline']?Label::getLabel('LBL_OFFLINE'):Label::getLabel('LBL_ONLINE'),
                ],
                [
                    'title' => Label::getLabel('LBL_ADDRESS'),
                    'value' => !empty($subOrder['teacher_address'])?$subOrder['teacher_address']:Label::getLabel('LBL_N/A'),
                ]
        );
        break;
    case Order::TYPE_GCLASS:
    case Order::TYPE_PACKGE:
        array_push($summaryItems,
                [
                    'title' => Label::getLabel('LBL_TITLE'),
                    'value' => ($order['order_type'] == Order::TYPE_GCLASS) ? $subOrder['grpcls_title'] : $subOrder['package_title'],
                ],
                [
                    'title' => Label::getLabel('LBL_LANGUAGE'),
                    'value' => $subOrder['tlang_name'],
                ],
                [
                    'title' => Label::getLabel('LBL_MINUTES'),
                    'value' => str_replace('{n}', $subOrder['grpcls_duration'], Label::getLabel('LBL_{n}_MINS')),
                ],
                [
                    'title' => Label::getLabel('LBL_PRICE'),
                    'value' => MyUtility::formatMoney($subOrder['ordcls_amount']) . '/' . Label::getLabel('LBL_CLASS'),
                ],
                [
                    'title' => Label::getLabel('LBL_CLASS_TYPE'),
                    'value' => (int)$subOrder['is_offline']?Label::getLabel('LBL_OFFLINE'):Label::getLabel('LBL_ONLINE'),
                ],
                [
                    'title' => Label::getLabel('LBL_ADDRESS'),
                    'value' =>  !empty($subOrder['teacher_address'])?$subOrder['teacher_address']:Label::getLabel('LBL_N/A')
                ]
        );
        break;
    case Order::TYPE_WALLET:
        array_push($summaryItems,
                [
                    'title' => Label::getLabel('LBL_AMOUNT_ADDED'),
                    'value' => $order['order_net_amount'],
                ]
        );
        break;
    case Order::TYPE_GFTCRD:
        array_push($summaryItems,
                [
                    'title' => Label::getLabel('LBL_RECIPIENT_NAME'),
                    'value' => $subOrder['ordgift_receiver_name'],
                ],
                [
                    'title' => Label::getLabel('LBL_RECIPIENT_EMAIL'),
                    'value' => $subOrder['ordgift_receiver_email'],
                ],
                [
                    'title' => Label::getLabel('LBL_GIFTCARD_STATUS'),
                    'value' => Giftcard::getStatuses($subOrder['ordgift_status']),
                ]
        );
        break;
}
array_push($summaryItems,
        [
            'title' => Label::getLabel('LBL_DISCOUNT'),
            'value' => $order["order_discount_value"],
        ],
        [
            'title' => Label::getLabel('LBL_REWARD_DISCOUNT'),
            'value' => $order["order_reward_value"],
        ],
        [
            'title' => Label::getLabel('LBL_TOTAL'),
            'value' => $order['order_net_amount'],
        ]
);
if (in_array($order['order_type'], [Order::TYPE_LESSON, Order::TYPE_SUBSCR, Order::TYPE_GCLASS, Order::TYPE_PACKGE])) {
    array_push($order['order_details'], [
        'title' => Label::getLabel('LBL_TEACHER_DETAIL'),
        'layout_type' => 'teacher_detail',
        'lists' => [
            [
                'title' => Label::getLabel('LBL_TEACHER'),
                'value' => $subOrder['user_first_name'] . ' ' . $subOrder['user_last_name']
            ],
            [
                'title' => Label::getLabel('LBL_FROM'),
                'value' => $countries[$subOrder['user_country_id']] ?? Label::getLabel('LBL_NA')
            ],
            [
                'title' => Label::getLabel('LBL_TIMEZONE'),
                'value' => $subOrder['user_timezone']
            ]
        ]
    ]);
}
array_push($order['order_details'], [
    'title' => Label::getLabel('LBL_ORDER_SUMMARY'),
    'layout_type' => 'summary',
    'lists' => $summaryItems
]);

$orderPayments = [];
$opayment = current($order['orderPayments']);
if (!empty($opayment)) {
    $orderPayments = [
        ['title' => Label::getLabel('LBL_DATETIME'), 'value' => $opayment['ordpay_datetime']],
        ['title' => Label::getLabel('LBL_TXN_ID'), 'value' => $opayment['ordpay_txn_id']],
        ['title' => Label::getLabel('LBL_AMOUNT'), 'value' => MyUtility::formatMoney($opayment['ordpay_amount'])],
        ['title' => Label::getLabel('LBL_PAYMENT_METHOD'), 'value' => $pmethods[$opayment['ordpay_pmethod_id']] ?? 'NA'],
    ];
    array_push($order['order_details'], [
        'title' => Label::getLabel('LBL_PAYMENT_HISTORY'),
        'layout_type' => 'payment',
        'lists' => $orderPayments
    ]);
}

unset($order['orderPayments']);
$order['msg'] = Label::getLabel('API_ORDER_DETAILS');
MyUtility::dieJsonSuccess($order);
