<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($orders) < 1) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive">
        <tr class="title-row">
            <th><?php echo $orderIdLabel = Label::getLabel('LBL_ORDER_ID'); ?></th>
            <th><?php echo $giftCardLabel = Label::getLabel('LBL_CODE'); ?></th>
            <th><?php echo $amountLabel = Label::getLabel('LBL_AMOUNT'); ?></th>
            <?php if ($post['giftcard_type'] == GiftCard::RECEIVED) { ?>
                <th><?php echo $senderLabel = Label::getLabel('LBL_SENDER'); ?></th>
            <?php } else { ?>
                <th><?php echo $receiverLabel = Label::getLabel('LBL_RECEIVER'); ?></th>
            <?php } ?>
            <th><?php echo $dateLabel = Label::getLabel('LBL_DATE'); ?></th>
            <th><?php echo $statusLabel = Label::getLabel('LBL_STATUS'); ?></th>
        </tr>
        <?php foreach ($orders as $giftcard) { ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $orderIdLabel; ?></div>
                        <div class="flex-cell__content"><?php echo Order::formatOrderId($giftcard['order_id']); ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $giftCardLabel; ?></div>
                        <div class="flex-cell__content"><?php echo $giftcard['code']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $amountLabel; ?></div>
                        <div class="flex-cell__content"><?php echo MyUtility::formatMoney($giftcard['order_total_amount']); ?></div>
                    </div>
                </td>
                <?php if ($giftcard['receiver_id'] == $siteUserId) { ?>
                    <td>
                        <div class="flex-cell">
                            <div class="flex-cell__label"><?php echo $senderLabel; ?></div>
                            <div class="flex-cell__content">
                                <div class="data-group">
                                    <span><?php echo $giftcard['user_full_name']; ?></span>
                                </div>
                            </div>
                        </div>
                    </td>
                <?php } else { ?>
                    <td>
                        <div class="flex-cell">
                            <div class="flex-cell__label"><?php echo $receiverLabel; ?></div>
                            <div class="flex-cell__content">
                                <div class="data-group">
                                    <span><?php echo $giftcard['receiver_name']; ?></span><br>
                                    <span><small><?php echo $giftcard['receiver_email'] ?></small></span>
                                </div>
                            </div>
                        </div>
                    </td>
                <?php } ?>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $dateLabel; ?></div>
                        <div class="flex-cell__content"><?php echo MyDate::showDate($giftcard['order_addedon'], true); ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $statusLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php $spnCls = ($giftcard['ordgift_status'] == Giftcard::STATUS_USED) ? 'secondary' : 'primary'; ?>
                            <span class="badge color-<?php echo $spnCls; ?> badge--curve"><?php echo Giftcard::getStatuses($giftcard['ordgift_status']); ?></span>
                        </div>
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php
$pagingArr = [
    'pageSize' => $post['pagesize'],
    'page' => $post['pageno'], $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>
</div>