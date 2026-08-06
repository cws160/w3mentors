<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArr = AppConstant::getYesNoArr();
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_VIEW_GIFTCARDS_DETAIL'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th><?php echo Label::getLabel('LBL_USER_NAME'); ?></th>
            <td><?php echo $order['user_full_name']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PAYMENT_STATUS'); ?></th>
            <td><?php echo Order::getPaymentArr($order['order_payment_status']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_GIFTCARD_CODE'); ?></th>
            <td><?php echo $order['code']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_RECIPIENT_NAME'); ?></th>
            <td><?php echo $order['receiver_name']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_RECIPIENT_EMAIL'); ?></th>
            <td><?php echo $order['receiver_email']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_GIFTCARD_STATUS'); ?></th>
            <td><?php echo Giftcard::getStatuses($order['ordgift_status']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_AMOUNT'); ?></th>
            <td><?php echo MyUtility::formatMoney($order['order_net_amount']); ?></td>
        </tr>
    </table>
</div>