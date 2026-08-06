<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($orders) < 1) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$pmethod = array_column($pmethods, 'pmethod_code', 'pmethod_id');
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive">
        <tr class="title-row">
            <th><?php echo $orderIdLabel = Label::getLabel('LBL_ORDER_ID'); ?></th>
            <th><?php echo $orderTypeLabel = Label::getLabel('LBL_TYPE'); ?></th>
            <th><?php echo $orderItemsLabel = Label::getLabel('LBL_ITEMS'); ?></th>
            <th><?php echo $netAmountLabel = Label::getLabel('LBL_NET_AMOUNT'); ?></th>
            <th><?php echo $paymentMethodLabel = Label::getLabel('LBL_PAYMENT_METHOD'); ?></th>
            <th><?php echo $paymentStatusLabel = Label::getLabel('LBL_PAYMENT'); ?></th>
            <th><?php echo $statusLabel = Label::getLabel('LBL_STATUS'); ?></th>
            <th><?php echo $datetimeLabel = Label::getLabel('LBL_DATETIME'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTION'); ?></th>
        </tr>
        <?php foreach ($orders as $order) { ?>
            <tr class="row-trigger">
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $orderIdLabel; ?> </div>
                        <div class="flex-cell__content order-action">
                            <div class="d-flex align-items-center" onclick="view('<?php echo $order['order_id']; ?>', '<?php echo $order['order_type']; ?>');">
                                <a href="javascript:void(0);" class="color-primary bold-600"><?php echo Order::formatOrderId($order['order_id']); ?></a>
                                <span class="arrow-icon"></span>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"> <?php echo $orderTypeLabel; ?> </div>
                        <div class="flex-cell__content">
                            <div style="max-width: 250px;"><?php echo Order::getTypeArr($order['order_type']); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"> <?php echo $orderItemsLabel; ?> </div>
                        <div class="flex-cell__content">
                            <div style="max-width: 250px;"><?php echo $order['order_item_count']; ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $netAmountLabel; ?></div>
                        <div class="flex-cell__content">
                            <div style="max-width: 250px;"><?php echo MyUtility::formatMoney($order['order_net_amount']); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $paymentMethodLabel; ?></div>
                        <div class="flex-cell__content">
                            <div style="max-width: 250px;"><?php echo $order['order_pmethod']; ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $paymentStatusLabel; ?></div>
                        <div class="flex-cell__content">
                            <div>
                                <div>
                                    <span class="badge badge--curve color-<?php echo ($order['order_payment_status'] == Order::ISPAID) ? 'green' : 'yellow'; ?>">
                                        <?php echo Order::getPaymentArr($order['order_payment_status']); ?>
                                    </span>
                                </div>
                                <?php if ($order['order_payment_status'] == Order::UNPAID && ($pmethod[$order['order_pmethod_id']] ?? '') == PaymentMethod::BANK_TRANSFER) { ?>
                                    <a href="<?php echo MyUtility::makeFullUrl('Payment', 'charge', [$order['order_id']], CONF_WEBROOT_FRONT_URL); ?>" class="color-secondary" target="_blank"><?php echo Label::getLabel('LBL_SUBMIT_DETAIL'); ?></a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $statusLabel; ?></div>
                        <div class="flex-cell__content">
                            <span><?php echo Order::getStatusArr($order['order_status']); ?></span>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $datetimeLabel; ?></div>
                        <div class="flex-cell__content">
                            <div style="max-width: 250px;"><?php echo MyDate::showDate($order['order_addedon'], true); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="actions-group">
                                <a href="javascript:view('<?php echo $order['order_id']; ?>', '<?php echo $order['order_type']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--cancel icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#list'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_VIEW') ?></div>
                                </a>
                                <a href="<?php echo MyUtility::makeUrl('Orders', 'viewInvoice', [$order['order_id']]) . '?t=' . time(); ?>" target="_blank" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--cancel icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#download'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_DOWNLOAD_INVOICE') ?></div>
                                </a>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr class="target-data target-data-js target-data-<?php echo $order['order_id']; ?>"></tr>
        <?php } ?>
    </table>
</div>
<?php
$pagingArr = [
    'pageSize' => $post['pagesize'],
    'page' => $post['pageno'],
    $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>
</div>