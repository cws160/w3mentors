<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$subOrder = current($subOrders);
$dir = Language::getAttributesById($siteLangId, 'language_direction');
$classLogo = ($dir == 'rtl') ? 'right' : 'left';
$classText = ($dir == 'rtl') ? 'left' : 'right';
$cssAlign = $dir == 'rtl' ? 'right' : 'left';
$orderDetailAlign = $dir == 'rtl' ? 'left' : 'right';
$borderStyle = $dir == 'rtl' ? 'style="border-left: 1px solid;"' : 'style="border-right: 1px solid;"';
?>
<style>
    table {
        color: #000;
        font-size: 12px;
        line-height: 1.4;
        padding: 0;
        margin: 0;
    }

    .sub-order-details {
        border-bottom: solid 1px #000;
        line-height: 1.5;
        vertical-align: top;
    }

    .order-items-heading {
        padding: 10px;
        text-align: <?php echo $cssAlign; ?>;
        border-bottom: 1px solid #eee;
        background-color: #eee;
    }

    .order-items-heading th {
        text-align: <?php echo $cssAlign; ?>;
    }
</style>
<?php
$type = Order::getTypeArr($order['order_type']);
$itemName = $qty = $duration = $teacherName = '';
switch ($order['order_type']) {
    case Order::TYPE_LESSON:
    case Order::TYPE_SUBSCR:
        $itemName = is_null($subOrder['tlang_name']) ? Label::getLabel('LBL_FREE_TRIAL') : $subOrder['tlang_name'];
        $lesDuration = $subOrder['ordles_duration'] . ' ' . Label::getLabel('LBL_MINUTES');
        $qty = count($subOrders);
        if ($subOrderId) {
            $order["order_total_amount"] = $subOrder['ordles_amount'];
            $order["order_discount_value"] = $subOrder['ordles_discount'];
            $order["order_reward_value"] = $subOrder['ordles_reward_discount'];
            $order["order_net_amount"] = $subOrder['ordles_amount'] - ($subOrder['ordles_discount'] + $subOrder['ordles_reward_discount']);
        }
        $teacherName = ucwords($subOrder['user_first_name'] . ' ' . $subOrder['user_last_name']);
        break;
    case Order::TYPE_GCLASS:
    case Order::TYPE_PACKGE:
        $itemName = ($order['order_type'] == Order::TYPE_GCLASS) ? $subOrder['grpcls_title'] : $subOrder['package_title'];
        if ($subOrderId) {
            $order["order_total_amount"] = $subOrder['ordcls_amount'];
            $order["order_discount_value"] = $subOrder['ordcls_discount'];
            $order["order_reward_value"] = $subOrder['ordcls_reward_discount'];
            $order["order_net_amount"] = $subOrder['ordcls_amount'] - ($subOrder['ordcls_discount'] + $subOrder['ordcls_reward_discount']);
        }
        $teachLang = $subOrder['tlang_name'];
        $duration = $subOrder['grpcls_duration'] . ' ' . Label::getLabel('LBL_MINUTES');
        $teacherName = ucwords($subOrder['user_first_name'] . ' ' . $subOrder['user_last_name']);
        break;
    case Order::TYPE_COURSE:
        $itemName = $subOrder['course_title'];
        $teachLang = $subOrder['clang_name'];
        $duration = CommonHelper::convertDuration($subOrder['course_duration']);
        $teacherName = ucwords($subOrder['user_first_name'] . ' ' . $subOrder['user_last_name']);
        break;
    case Order::TYPE_WALLET:
        break;
    case Order::TYPE_GFTCRD:
        $receiverName = $subOrder['ordgift_receiver_name'];
        $receiverEmail = $subOrder['ordgift_receiver_email'];
        break;
    case Order::TYPE_SUBPLAN:
        $itemName = $subOrder['plan_name'];
        $startDate = MyDate::showDate($subOrder['ordsplan_start_date'], true);
        $endDate = MyDate::showDate($subOrder['ordsplan_end_date'], true);
        $lessons = $subOrder['ordsplan_lessons'];
        $subLesDuration = $subOrder['ordsplan_duration'];
        $validity = $subOrder['ordsplan_validity'];
        if ($subOrderId) {
            $order["order_total_amount"] = $subOrder['ordsplan_amount'];
            $order["order_discount_value"] = $subOrder['ordsplan_discount'];
            $order["order_reward_value"] = $subOrder['ordsplan_reward_discount'];
            $order["order_net_amount"] = $subOrder['ordsplan_amount'] - ($subOrder['ordsplan_discount'] + $subOrder['ordsplan_reward_discount']);
        }
        break;
}
?>
<div style="max-width:1000px; margin:1rem auto;">
    <table cellpadding="5" cellspacing="0" align="center" style="border: solid 1px #000; width:100%">
        <tr>
            <td style="border-bottom: solid 1px #000; text-align:center; line-height:1.5;" colspan="2">
                <table cellpadding="5" cellspacing="0" style="width:100%">
                    <tr>
                        <td align="<?php echo $classLogo; ?>"><?php echo MyUtility::getLogo($siteLangId); ?> </td>
                        <td align="<?php echo $classText; ?>"> <strong
                                style="padding:10px;display:block;font-size: 20px;"><?php echo Label::getlabel('LBL_ORDER_RECEIPT'); ?></strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td class="sub-order-details" width="50%" <?php echo $borderStyle; ?>>
                <table style="width: 100%;">
                    <tbody>
                        <tr>
                            <td>
                                <h3><?php echo Label::getLabel('LBL_BILL_TO'); ?></h3>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900; width: 30%;"><?php echo Label::getLabel('LBL_NAME') . ':'; ?></td>
                            <td><?php echo ucwords($order['learner_full_name']); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;"><?php echo Label::getLabel('LBL_EMAIL') . ':'; ?></td>
                            <td> <?php echo ucwords($order['learner_email']); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;"><?php echo Label::getLabel('LBL_ORDER_ID') . ':'; ?></td>
                            <td> <?php echo Order::formatOrderId($order['order_id']); ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;"><?php echo Label::getLabel('LBL_ORDER_DATE') . ':'; ?></td>
                            <td style="font-weight: 900;"><?php echo MyDate::showDate($order['order_addedon'], true); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;"><?php echo Label::getLabel('LBL_PAY_METHOD') . ':'; ?></td>
                            <td style="font-weight: 900;"><?php echo $pmethods[$order['order_pmethod_id']] ?? Label::getLabel('LBL_NA'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </td>
            <td class="sub-order-details" width="50%">
                <table style="width: 100%;">
                    <tbody>
                        <tr>
                            <td>
                                <h3><?php echo Label::getLabel('LBL_ORDER_DETAIL'); ?></h3>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900; width: 30%;"><?php echo Label::getLabel('LBL_TYPE') . ':'; ?></td>
                            <td><?php echo $type; ?></td>
                        </tr>
                        <?php if (!empty($itemName)) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_NAME') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $itemName; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($lesDuration)) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_DURATION') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $lesDuration; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($teacherName)) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_TEACHER') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $teacherName; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($order['service_type']) && in_array($order['order_type'], [Order::TYPE_LESSON, Order::TYPE_SUBSCR, Order::TYPE_GCLASS, Order::TYPE_PACKGE])) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_SERVICE_TYPE') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $order['service_type']; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($qty)) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_QUANTITY') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $qty; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($teachLang)) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_LANGUAGE') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $teachLang; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($duration)) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_DURATION') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $duration; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($receiverName)) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_RECIPIENT_NAME') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $receiverName; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <?php if (!empty($receiverEmail)) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_RECIPIENT_EMAIL') . ':'; ?></td>
                                <td style="font-weight: 900;"> <?php echo $receiverEmail; ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td style="font-weight: 900;">
                                <?php echo Label::getLabel('LBL_PAYMENT_STATUS') . ':'; ?></td>
                            <td style="font-weight: 900;">
                                <?php echo Order::getPaymentArr($order['order_payment_status']); ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;">
                                <?php echo Label::getLabel('LBL_STATUS') . ':'; ?></td>
                            <td style="font-weight: 900;">
                                <?php echo Order::getStatusArr($order['order_status']); ?>
                            </td>
                        </tr>
                        <?php if ($order['order_related_order_id'] > 0) { ?>
                            <tr>
                                <td style="font-weight: 900;">
                                    <?php echo Label::getLabel('LBL_RELATED_ORDER') . ':'; ?></td>
                                <td style="font-weight: 900;">
                                    <?php echo Order::formatOrderId($order['order_related_order_id']); ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <?php if(!in_array($order['order_type'], [Order::TYPE_COURSE, Order::TYPE_WALLET])) { ?>
            <td style="line-height:1.5; border-bottom: 1px solid;" colspan="2">
                <table width="100%" border="0" cellpadding="10" cellspacing="0">
                    <thead>
                        <?php if (in_array($order['order_type'], [Order::TYPE_LESSON, Order::TYPE_SUBSCR]) && $subOrders) { ?>
                            <tr class="order-items-heading">
                                <th><?php echo Label::getLabel('LBL_LESSON_ID'); ?></th>
                                <th><?php echo Label::getLabel('LBL_LESSON_STARTTIME'); ?></th>
                                <th><?php echo Label::getLabel('LBL_LESSON_ENDTIME'); ?></th>
                                <th><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                            </tr>
                        <?php } elseif (in_array($order['order_type'], [Order::TYPE_GCLASS, Order::TYPE_PACKGE]) && $subOrders) { ?>
                            <tr class="order-items-heading">
                                <th><?php echo Label::getLabel('LBL_CLASS_ID'); ?></th>
                                <th><?php echo Label::getLabel('LBL_CLASS_STARTTIME'); ?></th>
                                <th><?php echo Label::getLabel('LBL_CLASS_ENDTIME'); ?></th>
                                <th><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                            </tr>
                        <?php } else if ($order['order_type'] == Order::TYPE_GFTCRD) { ?>
                            <tr class="order-items-heading">
                                <th><?php echo Label::getLabel('LBL_ITEM'); ?></th>
                                <th><?php echo Label::getLabel('LBL_AMOUNT'); ?></th>
                            </tr>
                        <?php } else if ($order['order_type'] == Order::TYPE_SUBPLAN) { ?>
                            <tr class="order-items-heading">
                                <th><?php echo Label::getLabel('LBL_STARTTIME'); ?></th>
                                <th><?php echo Label::getLabel('LBL_ENDTIME'); ?></th>
                                <th><?php echo Label::getLabel('MSG_INVOICE_SUBPLAN_VALIDITY'); ?></th>
                                <th><?php echo Label::getLabel('LBL_LESSONS'); ?></th>
                                <th><?php echo Label::getLabel('MSG_INVOICE_SUBPLAN_LESSON_DURATION'); ?></th>
                                <th><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                            </tr>
                        <?php } ?>
                    </thead>
                    <tbody>
                        <?php if ($order['order_type'] == Order::TYPE_GFTCRD) { ?>
                            <tr>
                                <td>
                                    <?php echo $type; ?>
                                </td>
                                <td>
                                    <?php echo MyUtility::formatMoney($order['order_total_amount']); ?>
                                </td>
                            </tr>
                        <?php } else { ?>
                            <?php foreach ($subOrders as $subOrder) { ?>
                                <?php if (in_array($order['order_type'], [Order::TYPE_LESSON, Order::TYPE_SUBSCR])) { ?>
                                    <tr>
                                        <td>
                                            <?php echo $subOrder['ordles_id']; ?>
                                        </td>
                                        <td>
                                            <?php echo MyDate::showDate($subOrder['ordles_lesson_starttime'], true); ?>
                                        </td>
                                        <td>
                                            <?php echo MyDate::showDate($subOrder['ordles_lesson_endtime'], true); ?>
                                        </td>
                                        <td>
                                            <span class="badge color-primary badge--curve"><?php echo Lesson::getStatuses($subOrder['ordles_status']) ?></span>
                                        </td>
                                    </tr>
                                <?php }
                                if (in_array($order['order_type'], [Order::TYPE_GCLASS, Order::TYPE_PACKGE])) { ?>
                                    <tr>
                                        <td>
                                            <?php echo $subOrder['ordcls_id']; ?>
                                        </td>
                                        <td>
                                            <?php echo MyDate::showDate($subOrder['grpcls_start_datetime'], true); ?>
                                        </td>
                                        <td>
                                            <?php echo MyDate::showDate($subOrder['grpcls_end_datetime'], true); ?>
                                        </td>
                                        <td>
                                            <span class="badge color-primary badge--curve"><?php echo OrderClass::getStatuses($subOrder['ordcls_status']) ?></span>
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php if (in_array($order['order_type'], [Order::TYPE_SUBPLAN])) { ?>
                                    <tr>
                                        <td>
                                            <?php echo $startDate; ?>
                                        </td>
                                        <td>
                                            <?php echo $endDate; ?>
                                        </td>
                                        <td>
                                            <?php echo $validity; ?>
                                        </td>
                                        <td>
                                            <?php echo $lessons; ?>
                                        </td>
                                        <td>
                                            <?php echo $subLesDuration; ?>
                                        </td>
                                        <td>
                                            <span class="badge color-primary badge--curve"><?php echo OrderSubscriptionPlan::getStatuses($subOrder['ordsplan_status']) ?></span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        <?php } ?>
                    </tbody>
                </table>
            </td>
            <?php } ?>
        </tr>
        <tr>
            <td></td>
            <td>
                <table style="width: 100%; padding-top: 10px; padding-bottom: 10px; text-align: <?php echo $orderDetailAlign; ?>;">
                    <tbody>
                        <tr>
                            <td style="font-weight: 900;"><?php echo Label::getLabel('LBL_TOTAL_ORDER_AMOUNT') . ':'; ?></td>
                            <td><?php echo MyUtility::formatMoney($order["order_total_amount"]) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;"><?php echo Label::getLabel('LBL_ORDER_DISCOUNT') . ':'; ?></td>
                            <td><?php echo MyUtility::formatMoney($order["order_discount_value"]) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;"><?php echo Label::getLabel('LBL_ORDER_REWARD') . ':'; ?></td>
                            <td><?php echo MyUtility::formatMoney($order["order_reward_value"]) ?></td>
                        </tr>
                        <tr>
                            <td style="font-weight: 900;"><?php echo Label::getLabel('LBL_ORDER_NET_AMOUNT') . ':'; ?></td>
                            <td><?php echo MyUtility::formatMoney($order["order_net_amount"]) ?></td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </table>
</div>