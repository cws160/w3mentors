<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArr = AppConstant::getYesNoArr();
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_CLASS_DETAIL'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th><?php echo Label::getLabel('LBL_LEARNER_NAME'); ?>:</th>
            <td><?php echo $order['learner_first_name'] . ' ' . $order['learner_last_name']; ?></td>
        </tr>
        <tr>
            <th> <?php echo Label::getLabel('LBL_TEACHER_NAME'); ?>:</th>
            <td><?php echo $order['teacher_first_name'] . ' ' . $order['teacher_last_name']; ?></td>
        <tr>
            <th><?php echo Label::getLabel('LBL_CLASS_NAME'); ?>:</th>
            <td><?php echo $order['grpcls_title']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LANGUAGE'); ?>:</th>
            <td><?php echo $order['grpcls_tlang_name']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_SERVICE_TYPE'); ?>:</th>
            <td><?php echo AppConstant::getServiceType($order['grpcls_offline']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_CLASS_STATUS'); ?>:</th>
            <td><?php echo OrderClass::getStatuses($order['ordcls_status']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ORDER_PAYMENT_STATUS'); ?>:</th>
            <td><?php echo Order::getPaymentArr($order['order_payment_status']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_START_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['grpcls_start_datetime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_END_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['grpcls_end_datetime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_TEACHER_START_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['teacher_format_starttime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_TEACHER_END_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['teacher_format_endtime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LEARNER_START_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['ordcls_format_starttime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LEARNER_END_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['ordcls_format_endtime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_CLASS_PRICE'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordcls_amount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_DISCOUNT_TOTAL'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordcls_discount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REWARD_DISCOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordcls_reward_discount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_NET_AMOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney(($order['ordcls_amount'] - ($order['ordcls_reward_discount'] + $order['ordcls_discount']))); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ADMIN_COMMISSION'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordcls_commission_amount']); ?></td>
        </tr>
        <?php if (User::isAffiliateEnabled()) { ?>
            <tr>
                <th><?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION'); ?>:</th>
                <td><?php echo MyUtility::formatMoney($order['ordcls_affiliate_commission']); ?></td>
            </tr>
        <?php } ?>
        <tr>
            <th><?php echo Label::getLabel('LBL_TEACHER_PAID'); ?>:</th>
            <td><?php echo (empty(FatUtility::float($order['ordcls_teacher_paid']))) ? Label::getLabel('LBL_NO') : Label::getLabel('LBL_YES'); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_REVIEWED_ON_CLASS'); ?>:</th>
            <td><?php echo $yesNoArr[$order['ordcls_reviewed']]; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ISSUE_REPORTED'); ?>:</th>
            <td><?php echo ($order['repiss_id'] > 0) ? $yesNoArr[AppConstant::YES] : $yesNoArr[AppConstant::NO]; ?></td>
        </tr>
        <tr>
            <th> <?php echo Label::getLabel('LBL_REFUND'); ?>:</th>
            <td><?php echo ($order['ordcls_refund'] > 0) ? MyUtility::formatMoney($order['ordcls_refund']) : Label::getLabel('LBL_NA'); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ORDER_ID'); ?>:</th>
            <td><a class="link-text link-underline" target="_blank" href="<?php echo MyUtility::makeUrl('Orders', 'view', [$order['order_id']]); ?>"><?php echo Label::getLabel('LBL_VIEW') . ' ' . Order::formatOrderId(FatUtility::int($order['order_id'])); ?> </a></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ENDED_BY'); ?>:</th>
            <td>
                <?php
                if (!empty($order['ordcls_ended_by'])) {
                    if ($order['ordcls_ended_by'] == User::TEACHER) {
                        echo $order['teacher_first_name'] . ' ' . $order['teacher_last_name'];
                    } elseif ($order['ordcls_ended_by'] == User::LEARNER) {
                        echo $order['learner_first_name'] . ' ' . $order['learner_last_name'];
                    } else {
                        echo Label::getLabel('LBL_SYSTEM');
                    }
                } else {
                    echo Label::getLabel('LBL_NA');
                };
                ?>
            </td>
        </tr>
    </table>
</div>