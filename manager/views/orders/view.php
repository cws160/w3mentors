<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
switch ($order['order_type']) {
    case Order::TYPE_LESSON:
        $oderTypeLabel = 'LBL_LESSONS_DETAILS';
        break;
    case Order::TYPE_SUBSCR:
        $oderTypeLabel = 'LBL_RECURRING_LESSON_DETAILS';
        break;
    case Order::TYPE_GCLASS:
        $oderTypeLabel = 'LBL_GROUP_CLASS_DETAILS';
        break;
    case Order::TYPE_COURSE:
        $oderTypeLabel = 'LBL_COURSE_DETAILS';
        break;
    case Order::TYPE_PACKGE:
        $oderTypeLabel = 'LBL_PACKAGE_CLASS_DETAILS';
        break;
    case Order::TYPE_WALLET:
        $oderTypeLabel = 'LBL_WALLET_DETAILS';
        break;
    case Order::TYPE_GFTCRD:
        $oderTypeLabel = 'LBL_GIFT_CARD_DETAILS';
        break;
    case Order::TYPE_SUBPLAN:
        $oderTypeLabel = 'LBL_SUBSCRIPTION_PLAN_DETAILS';
        break;
}
$payins[0] = Label::getLabel('LBL_NA');
?>
<script>
    var order_id = '<?php echo $order["order_id"] ?>';
</script>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
                <a href="<?php echo MyUtility::makeUrl('Orders', 'viewInvoice', [$order['order_id']]) . '?t=' . time() ?>" class="btn btn-primary" target="_blank"><?php echo Label::getLabel('LBL_DOWNLOAD_INVOICE'); ?></a>
                <a href="<?php echo MyUtility::makeUrl('Orders'); ?>" class="btn btn-primary"><?php echo Label::getLabel('LBL_BACK_TO_ORDER'); ?></a>
            </div>
        </div>
        <div class="card">
            <div class="card-head">
                <div class="card-head-label">
                    <h3 class="card-head-title"><?php echo Label::getLabel('LBL_CUSTOMER_ORDER_DETAIL'); ?></h3>
                </div>
            </div>
            <div class="card-table">
                <div class="table-responsive">
                    <table class="table table-coloum">
                        <tr>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_ID'); ?>:</strong> <?php echo Order::formatOrderId($order["order_id"]); ?></td>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_DATE'); ?>: </strong> <?php echo MyDate::showDate($order['order_addedon'], true); ?></td>
                            <td><strong><?php echo Label::getLabel('LBL_PAYMENT_STATUS'); ?>:</strong> <?php echo Order::getPaymentArr($order['order_payment_status']); ?></td>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_TOTAL_AMOUNT'); ?>: </strong> <?php echo MyUtility::formatMoney($order["order_total_amount"]); ?> </td>
                        </tr>
                        <tr>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_DISCOUNT'); ?>: </strong> <?php echo MyUtility::formatMoney($order["order_discount_value"]); ?> </td>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_REWARDS'); ?>: </strong> <?php echo MyUtility::formatMoney($order["order_reward_value"]); ?> </td>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_NET_AMOUNT'); ?>: </strong> <?php echo MyUtility::formatMoney($order["order_net_amount"]); ?> </td>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_AMOUNT_PAID'); ?>: </strong><?php echo MyUtility::formatMoney($totalPaidAmount); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_AMOUNT_PENDING'); ?>: </strong><?php echo MyUtility::formatMoney($pendingAmount); ?></td>
                            <td><strong><?php echo Label::getLabel('LBL_ORDER_STATUS'); ?>: </strong><?php echo Order::getStatusArr($order["order_status"]); ?></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card card-height">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Label::getLabel('LBL_USER_DETAILS'); ?></h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <p>
                                <strong><?php echo Label::getLabel('LBL_NAME'); ?> : </strong><?php echo $order['learner_full_name']; ?>
                            </p>
                            <p>
                                <strong><?php echo Label::getLabel('LBL_EMAIL'); ?> : </strong><?php echo $order['learner_email']; ?>
                            </p>
                            <p>
                                <strong><?php echo Label::getLabel('LBL_USER_ID'); ?> : </strong><?php echo $order['order_user_id']; ?>
                            </p>
                            <p>
                                <strong><?php echo Label::getLabel('LBL_USER_TIMEZONE'); ?> : </strong><?php echo MyDate::formatTimeZoneLabel($order['user_timezone']); ?>
                            </p>

                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-height">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Label::getLabel('LBL_ORDER_DETAILS'); ?></h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <p>
                                <strong><?php echo Label::getLabel('LBL_ORDER_TYPE'); ?> : </strong><?php echo Order::getTypeArr($order['order_type']); ?>
                            </p>

                            <p> <strong><?php echo Label::getLabel('LBL_ORDER/INVOICE_ID'); ?> : </strong><?php echo Order::formatOrderId($order["order_id"]); ?></p>

                            <p> <strong><?php echo Label::getLabel('LBL_ORDER_AMOUNT_PAID'); ?> : </strong> <?php echo MyUtility::formatMoney($totalPaidAmount); ?></p>

                            <p> <strong><?php echo Label::getLabel('LBL_ORDER_DATE'); ?> : </strong> <?php echo MyDate::showDate($order['order_addedon'], true); ?> </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-height">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-title"><?php echo Label::getLabel($oderTypeLabel); ?></h3>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php
                            switch ($order['order_type']) {
                                case Order::TYPE_LESSON:
                                case Order::TYPE_SUBSCR:
                            ?>
                                    <?php if ($order['order_type'] == Order::TYPE_SUBSCR) { ?>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_RECURRING_LESSON_START_DATE'); ?> : </strong><?php echo MyDate::showDate($childeOrderDetails['ordsub_startdate'], true); ?>
                                        </p>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_RECURRING_LESSON_END_DATE'); ?> : </strong><?php echo MyDate::showDate($childeOrderDetails['ordsub_enddate'], true); ?>
                                        </p>
                                    <?php } ?>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_NAME'); ?> : </strong><?php echo $childeOrderDetails['user_first_name'] . ' ' . $childeOrderDetails['user_last_name']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_EMAIL'); ?> : </strong><?php echo $childeOrderDetails['user_email']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_ID'); ?> : </strong><?php echo $childeOrderDetails['ordles_teacher_id']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_TIMEZONE'); ?> : </strong><?php echo MyDate::formatTimeZoneLabel($childeOrderDetails['user_timezone']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_LESSON_TYPE'); ?> : </strong><?php echo Lesson::getTypes($childeOrderDetails['ordles_type']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_SERVICE_TYPE'); ?> : </strong><?php echo AppConstant::getServiceType($childeOrderDetails['is_offline']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_NO._OF_LESSONS'); ?> : </strong><?php echo $childeOrderDetails['order_item_count']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_LESSON_DURATION'); ?> : </strong><?php echo $childeOrderDetails['ordles_duration'] . ' ' . Label::getLabel('LBL_MINS') . '/' . Label::getLabel('LBL_PER_LESSON'); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_LESSON_PRICE'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordles_amount']) . '/' . Label::getLabel('LBL_PER_LESSON'); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_ADMIN_COMMISSION'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordles_commission_amount']); ?>
                                    </p>
                                    <?php if (User::isAffiliateEnabled()) { ?>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordles_affiliate_commission']); ?>
                                        </p>
                                    <?php } ?>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACH_LANGUAGE'); ?> : </strong><?php echo $childeOrderDetails['tlang_name']; ?>
                                    </p>
                                    <?php if ($childeOrderDetails['is_offline'] == AppConstant::YES) { ?>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_ADDRESS'); ?> : </strong><?php echo $childeOrderDetails['teacher_address']; ?>
                                        </p>
                                    <?php } ?>
                                    <p>
                                        <strong><a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Lessons', 'index') . '?order_id=' . $order['order_id']; ?>"><?php echo Label::getLabel('LBL_VIEW_LESSON_ORDER'); ?></a></strong>
                                    </p>

                                    <?php if ($order['order_type'] == Order::TYPE_SUBSCR) { ?>
                                        <p>
                                            <strong><a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Subscriptions') . '?order_id=' . $order['order_id']; ?>"><?php echo Label::getLabel('LBL_VIEW_RECURRING_LESSON_ORDER'); ?></a></strong>

                                        </p>
                                    <?php } ?>
                                <?php
                                    break;
                                case Order::TYPE_GCLASS:
                                ?>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_NAME'); ?> : </strong><?php echo $childeOrderDetails['user_first_name'] . ' ' . $childeOrderDetails['user_last_name']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_EMAIL'); ?> : </strong><?php echo $childeOrderDetails['user_email']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_ID'); ?> : </strong><?php echo $childeOrderDetails['grpcls_teacher_id']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_TIMEZONE'); ?> : </strong><?php echo MyDate::formatTimeZoneLabel($childeOrderDetails['user_timezone']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_CLASS_NAME'); ?> : </strong><?php echo $childeOrderDetails['grpcls_title']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_SERVICE_TYPE'); ?> : </strong><?php echo AppConstant::getServiceType($childeOrderDetails['is_offline']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_START_DATE_TIME'); ?> : </strong><?php echo MyDate::showDate($childeOrderDetails['grpcls_start_datetime'], true); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_END_DATE_TIME'); ?> : </strong> <?php echo MyDate::showDate($childeOrderDetails['grpcls_end_datetime'], true); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TOTAL_SEATS'); ?> : </strong><?php echo $childeOrderDetails['grpcls_total_seats']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_CLASS_PRICE'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordcls_amount']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_ADMIN_COMMISSION'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordcls_commission_amount']); ?>
                                    </p>
                                    <?php if (User::isAffiliateEnabled()) { ?>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordcls_affiliate_commission']); ?>
                                        </p>
                                    <?php } ?>
                                    <?php if ($childeOrderDetails['is_offline'] == AppConstant::YES) { ?>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_ADDRESS'); ?> : </strong><?php echo $childeOrderDetails['teacher_address']; ?>
                                        </p>
                                    <?php } ?>
                                    <p>
                                        <strong><a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Classes', 'index') . '?order_id=' . $order['order_id']; ?>"><?php echo Label::getLabel('LBL_VIEW_CLASS_ORDER'); ?></a></strong>

                                    </p>
                                <?php break;
                                case Order::TYPE_PACKGE:  ?>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_NAME'); ?> : </strong><?php echo $childeOrderDetails['user_first_name'] . ' ' . $childeOrderDetails['user_last_name']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_EMAIL'); ?> : </strong><?php echo $childeOrderDetails['user_email']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_ID'); ?> : </strong><?php echo $childeOrderDetails['grpcls_teacher_id']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_TIMEZONE'); ?> : </strong><?php echo MyDate::formatTimeZoneLabel($childeOrderDetails['user_timezone']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_PACKAGE_NAME'); ?> : </strong><?php echo $childeOrderDetails['package_title']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_SERVICE_TYPE'); ?> : </strong><?php echo AppConstant::getServiceType($childeOrderDetails['is_offline']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_START_DATE_TIME'); ?> : </strong><?php echo MyDate::showDate($childeOrderDetails['package_start'], true); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_END_DATE_TIME'); ?> : </strong> <?php echo MyDate::showDate($childeOrderDetails['package_end'], true); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TOTAL_SEATS'); ?> : </strong><?php echo $childeOrderDetails['grpcls_total_seats']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_PACKAGE_PRICE'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordpkg_amount']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_ADMIN_COMMISSION'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordcls_commission_amount']); ?>
                                    </p>
                                    <?php if (User::isAffiliateEnabled()) { ?>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordcls_affiliate_commission']); ?>
                                        </p>
                                    <?php } ?>
                                    <?php if ($childeOrderDetails['is_offline'] == AppConstant::YES) { ?>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_ADDRESS'); ?> : </strong><?php echo $childeOrderDetails['teacher_address']; ?>
                                        </p>
                                    <?php } ?>
                                    <p>
                                        <strong><a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Packages') . '?order_id=' . $order['order_id']; ?>"><?php echo Label::getLabel('LBL_VIEW_PACKAGES_ORDER'); ?></a></strong>
                                    </p>
                                    <p>
                                        <strong><a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Classes', 'index') . '?order_id=' . $order['order_id']; ?>"><?php echo Label::getLabel('LBL_VIEW_CLASS_ORDER'); ?></a></strong>
                                    </p>

                                <?php break;
                                case Order::TYPE_WALLET: ?>

                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_AMOUNT_ADDED'); ?> : </strong><?php echo MyUtility::formatMoney($order['order_net_amount']); ?>
                                    </p>

                                    <?php if (!empty($order['order_related_order_id'])) { ?>
                                        <p>
                                            <strong><?php echo Label::getLabel('LBL_RELATED_ORDER'); ?> : </strong><a target="_blank" class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Orders', 'view', [$order['order_related_order_id']]); ?>"><?php echo Label::getLabel('LBL_VIEW') . ' ' . Order::formatOrderId($order['order_related_order_id']); ?> </a>
                                        </p>
                                    <?php }
                                    break;
                                case Order::TYPE_GFTCRD:
                                    ?>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_GIFTCARD_CODE'); ?> : </strong><?php echo $childeOrderDetails['ordgift_code']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_RECIPIENT_NAME'); ?> : </strong><?php echo $childeOrderDetails['ordgift_receiver_name']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_RECIPIENT_EMAIL'); ?> : </strong><?php echo $childeOrderDetails['ordgift_receiver_email']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_GIFTCARD_STATUS'); ?> : </strong><?php echo Giftcard::getStatuses($childeOrderDetails['ordgift_status']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_AMOUNT'); ?> : </strong><?php echo MyUtility::formatMoney($order['order_net_amount']); ?>
                                    </p>
                                    <p>
                                        <strong><a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Giftcards', 'index') . '?order_id=' . $order['order_id']; ?>"><?php echo Label::getLabel('LBL_VIEW_GIFTCARD_ORDER'); ?></a></strong>
                                    <?php
                                    break;
                                case Order::TYPE_COURSE:
                                    ?>
                                        <strong><?php echo Label::getLabel('LBL_COURSE_TITLE'); ?> : </strong><?php echo $childeOrderDetails['course_title']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_NAME'); ?> : </strong><?php echo ucwords($childeOrderDetails['user_first_name'] . ' ' . $childeOrderDetails['user_last_name']); ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_TEACHER_EMAIL'); ?> : </strong><?php echo $childeOrderDetails['user_email']; ?>
                                    </p>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_AMOUNT'); ?> : </strong><?php echo MyUtility::formatMoney($order['ordcrs_amount']); ?>
                                    </p>
                                    <p><strong><?php echo Label::getLabel('LBL_ADMIN_COMMISSION'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordcrs_commission_amount']); ?>
                                        <?php if (User::isAffiliateEnabled()) { ?>
                                    <p>
                                        <strong><?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION'); ?> : </strong><?php echo MyUtility::formatMoney($childeOrderDetails['ordcrs_affiliate_commission']); ?>
                                    </p>
                                <?php } ?>
                            <?php break;
                                case Order::TYPE_SUBPLAN:
                            ?>
                                <p>
                                    <strong><?php echo Label::getLabel('LBL_SUBSCRIPTION_PLAN_NAME'); ?> : </strong><?php echo $childeOrderDetails['plan_name']; ?>
                                </p>
                                <p>
                                    <strong><?php echo Label::getLabel('LBL_SUBSCRIPTION_START_DATE'); ?> : </strong><?php echo MyDate::showDate($childeOrderDetails['ordsplan_start_date'], true); ?>
                                </p>
                                <p>
                                    <strong><?php echo Label::getLabel('LBL_SUBSCRIPTION_END_DATE'); ?> : </strong><?php echo MyDate::showDate($childeOrderDetails['ordsplan_end_date'], true); ?>
                                </p>
                                <p>
                                    <strong><?php echo Label::getLabel('LBL_LESSON_COUNT'); ?> : </strong><?php echo $childeOrderDetails['ordsplan_lessons']; ?>
                                </p>
                                <p>
                                    <strong><?php echo Label::getLabel('LBL_LESSON_DURATION'); ?> : </strong><?php echo $childeOrderDetails['ordsplan_duration']; ?>
                                </p>
                                <p>
                                    <strong><?php echo Label::getLabel('LBL_PLAN_VALIDITY'); ?> : </strong><?php echo $childeOrderDetails['ordsplan_validity']; ?>
                                </p>
                                <p>
                                    <strong><a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('OrderSubscriptionPlans', 'index') . '?order_id=' . $order['order_id']; ?>"><?php echo Label::getLabel('LBL_View_Subscription_Order'); ?></a></strong>
                                </p>
                        <?php break;
                            } ?>
                        </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-head">
                <div class="card-head-label">
                    <h3 class="card-head-title"><?php echo Label::getLabel('LBL_ORDER_PAYMENT_HISTORY'); ?></h3>
                </div>
            </div>
            <div class="card-table">
                <div class="table-responsive">
                    <table class="table table--listing table--payement">
                        <tbody>
                            <tr>
                                <th width="15%"><?php echo Label::getLabel('LBL_DATE_ADDED'); ?></th>
                                <th width="15%"><?php echo Label::getLabel('LBL_TXN_ID'); ?></th>
                                <th width="15%"><?php echo Label::getLabel('LBL_PAYMENT_METHOD'); ?></th>
                                <th width="15%"><?php echo Label::getLabel('LBL_AMOUNT'); ?></th>
                                <th width="40%"><?php echo Label::getLabel('LBL_GATEWAY_RESPONSE'); ?></th>
                            </tr>
                            <?php if (!empty($bankTransfers)) { ?>
                                <?php foreach ($bankTransfers as $row) { ?>
                                    <tr>
                                        <td><?php echo MyDate::showDate($row['bnktras_datetime'], true); ?></td>
                                        <td><?php echo $row['bnktras_txn_id']; ?></td>
                                        <td>
                                            <?php echo Label::getLabel('LBL_' . $payins[$bankTransferPay['pmethod_id']]); ?>
                                            <?php if ($canEdit) { ?>
                                                <?php if ($row['bnktras_status'] == BankTransferPay::PENDING) { ?>
                                                    <div>
                                                        <a href="javascript:updateStatus('<?php echo $row['bnktras_id']; ?>','<?php echo BankTransferPay::APPROVED; ?>')" class="link-text link-underline"><?php echo Label::getLabel('LBL_APPROVE'); ?></a> |
                                                        <a href="javascript:updateStatus('<?php echo $row['bnktras_id']; ?>','<?php echo BankTransferPay::DECLINED; ?>')" class="link-text link-underline"><?php echo Label::getLabel('LBL_DECLINE'); ?></a>
                                                    </div>
                                                <?php } else { ?>
                                                    (<?php echo BankTransferPay::getStatuses($row['bnktras_status']); ?>)<br />
                                                <?php } ?>
                                            <?php } ?>
                                            <?php if (FatUtility::int($row['file_id']) > 0) { ?>
                                                <a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Image', 'download', [Afile::TYPE_ORDER_PAY_RECEIPT, $row['file_record_id']]); ?>"><?php echo Label::getLabel('LBL_VIEW_PAYMENT_RECEIPT'); ?></a>
                                            <?php } ?>
                                        </td>
                                        <td><?php echo MyUtility::formatMoney($row['bnktras_amount']); ?></td>
                                        <td>
                                            <div class="break-me collapse-text show-less-div"><?php echo mb_substr(nl2br($row['bnktras_response']), 0, 200, 'utf-8'); ?></div>
                                            <div class="break-me collapse-text show-more-div hide"><?php echo $row['bnktras_response']; ?></div>
                                            <?php if (strlen($row['bnktras_response']) > 200) { ?>
                                                <a class="collapse-btn link-text show-more" href="javascript:void(0)" onclick="showText(this,true)"> <?php echo Label::getLabel('LBL_SHOW_MORE'); ?> </a>
                                            <?php } ?>
                                            <a class="collapse-btn link-text show-less hide" href="javascript:void(0)" onclick="showText(this,false)"> <?php echo Label::getLabel('LBL_SHOW_LESS'); ?> </a>
                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                            <?php foreach ($order['orderPayments'] as $row) { ?>
                                <tr>
                                    <td><?php echo MyDate::showDate($row['ordpay_datetime'], true); ?></td>
                                    <td>
                                        <div class="break-me"><?php echo $row['ordpay_txn_id']; ?></div>
                                    </td>
                                    <td><?php echo Label::getLabel('LBL_' . $payins[$row['ordpay_pmethod_id']]); ?></td>
                                    <td><?php echo MyUtility::formatMoney($row['ordpay_amount']); ?></td>
                                    <td>
                                        <div class="break-me show-less-div"><?php echo mb_substr($row['ordpay_response'], 0, 200, 'utf-8');  ?>
                                        </div>
                                        <div class="break-me show-more-div hide"><?php echo $row['ordpay_response'];  ?>
                                        </div>
                                        <?php if (strlen($row['ordpay_response']) > 200) { ?>
                                            <a class="collapse-btn link-text show-more" href="javascript:void(0)" onclick="showText(this,true)"> <?php echo Label::getLabel('LBL_SHOW_MORE'); ?> </a>
                                        <?php } ?>
                                        <a class="collapse-btn link-text show-less hide" href="javascript:void(0)" onclick="showText(this,false)"> <?php echo Label::getLabel('LBL_SHOW_LESS'); ?> </a>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php if ($canEdit && $order['order_payment_status'] == Order::UNPAID && $order['order_status'] == Order::STATUS_INPROCESS) { ?>
            <div class="card">
                <div class="card-head">
                    <div class="card-head-label">
                        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_ORDER_PAYMENTS'); ?></h3>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    $form->setFormTagAttribute('onsubmit', 'updatePayment(this); return(false);');
                    $form->setFormTagAttribute('class', 'form');
                    $form->developerTags['colClassPrefix'] = 'col-md-';
                    $form->developerTags['fld_default_col'] = 4;
                    $paymentFld = $form->getField('ordpay_response');
                    $paymentFld->developerTags['col'] = 12;
                    echo $form->getFormHtml();
                    ?>
                </div>
            </div>
        <?php } ?>
    </div>
</main>