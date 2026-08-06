<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArr = AppConstant::getYesNoArr();
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_PACKAGE_DETAIL'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th><?php echo Label::getLabel('LBL_LEARNER_NAME'); ?>:</th>
            <td><?php echo $order['learner_first_name'] . ' ' . $order['learner_last_name']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_TEACHER_NAME'); ?>:</th>
            <td><?php echo $order['teacher_first_name'] . ' ' . $order['teacher_last_name']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PACKAGE_NAME'); ?>:</th>
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
            <th><?php echo Label::getLabel('LBL_PACKAGE_STATUS'); ?>:</th>
            <td><?php echo OrderPackage::getStatuses($order['ordpkg_status']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ORDER_PAYMENT_STATUS'); ?>:</th>
            <td><?php echo Order::getPaymentArr($order['order_payment_status']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PACKAGE_START_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['grpcls_start_datetime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PACKAGE_END_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['grpcls_end_datetime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PACKAGE_PRICE'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordpkg_amount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_DISCOUNT_TOTAL'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordpkg_discount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REWARD_DISCOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordpkg_reward_discount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_NET_AMOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney(($order['ordpkg_amount'] - ($order['ordpkg_reward_discount'] + $order['ordpkg_discount']))); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ORDER_ID'); ?>:</th>
            <td><a class="link-text link-underline" target="_blank" href="<?php echo MyUtility::makeUrl('Orders', 'view', [$order['order_id']]); ?>"><?php echo Label::getLabel('LBL_VIEW') . ' ' . Order::formatOrderId(FatUtility::int($order['order_id'])); ?> </a></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_VIEW_CLASSES'); ?>:</th>
            <td><a class="link-text link-underline" target="_blank" href="<?php echo MyUtility::makeUrl('Classes') . '?order_id=' . $order['order_id']; ?>"><?php echo Label::getLabel('LBL_VIEW_CLASSES'); ?> </a></td>
        </tr>
    </table>
</div>