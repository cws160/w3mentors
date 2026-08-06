<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArr = AppConstant::getYesNoArr();
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_VIEW_SUBSCRIPTION_PLAN_ORDER_DETAIL'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th><?php echo Label::getLabel('LBL_LEARNER_NAME'); ?>:</th>
            <td><?php echo $order['learner_first_name'] . ' ' . $order['learner_last_name']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_STATUS'); ?>:</th>
            <td><?php echo OrderSubscriptionPlan::getStatuses($order['ordsplan_status']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_START_DATE'); ?>:</th>
            <td><?php echo MyDate::showDate($order['ordsplan_start_date'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_END_DATE'); ?>:</th>
            <td><?php echo MyDate::showDate($order['ordsplan_end_date'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PLAN_NAME'); ?>:</th>
            <td><?php echo $order['plan_name']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PLAN_VALIDITY'); ?>:</th>
            <td><?php echo $order['ordsplan_validity']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_Lesson_duration'); ?>:</th>
            <td><?php echo $order['ordsplan_duration']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LESSON_COUNT'); ?>:</th>
            <td><?php echo $order['ordsplan_lessons']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PLAN_PRICE'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['order_total_amount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_DISCOUNT_TOTAL'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordsplan_discount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REWARD_DISCOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordsplan_reward_discount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_NET_AMOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney(($order['order_total_amount'] - ($order['ordsplan_reward_discount'] + $order['ordsplan_discount']))); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_REFUND_AMOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordsplan_refund']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ORDER_DATE'); ?>:</th>
            <td><?php echo MyDate::showDate($order['order_addedon'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ORDER_ID'); ?>:</th>
            <td><a class="link-text link-underline" target="_blank" href="<?php echo MyUtility::makeUrl('Orders', 'view', [$order['order_id']]); ?>"><?php echo Label::getLabel('LBL_VIEW') . ' ' . Order::formatOrderId($order['order_id']); ?> </a></td>
        </tr>
    </table>
</div>