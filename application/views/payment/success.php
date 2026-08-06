<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="checkout-payment bg-gradiant min-vh-100">
    <div class="payment-panel">
        <div class="payment-panel__head">
            <a href="<?php echo MyUtility::makeUrl(''); ?>" class="logo">
                <?php echo MyUtility::getLogo(); ?>
            </a>
        </div>
        <div class="payment-panel__body">
            <div class="checkout-thanku">
                <h4><?php echo Label::getLabel('MSG_THANKYOU_FOR_PURCHASE'); ?></h4>
                <div class="checkout-thanku__media"><img src="<?php echo CONF_WEBROOT_URL; ?>images/thanku-icon.svg" alt=""></div>
            </div>
            <div class="payment-order-details my-5">
                <div class="order-info">
                    <span class="order-info__label"><?php echo Label::getLabel('LBL_PAYABLE_AMOUNT'); ?></span>
                    <span class="order-info__value"><?php echo MyUtility::formatMoney($order['order_net_amount']); ?></span>
                </div>
                <div class="order-info">
                    <span class="order-info__label"><?php echo Label::getLabel('LBL_ORDER_NUMBER'); ?></span>
                    <span class="order-info__value"><?php echo Order::formatOrderId($order["order_id"]); ?></span>
                </div>
            </div>
            <div class="orders-tablewrap">
                <div class="table-orders">
                <?php if (in_array($order['order_type'], [Order::TYPE_LESSON, Order::TYPE_SUBSCR])) { ?>
                    <?php if (!empty($lessons)) { ?>
                        <table class="table table--responsive table--orders">
                            <thead>
                                <tr>
                                    <th><?php echo Label::getLabel('LBL_SRNO'); ?></th>
                                    <th><?php echo Label::getLabel('LBL_START_TIME'); ?></th>
                                    <th><?php echo Label::getLabel('LBL_END_TIME'); ?></th>
                                    <th><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 0; ?>
                                <?php foreach ($lessons as $subOrder) { ?>
                                    <tr>
                                        <td>
                                            <div class="flex-cell">
                                                <div class="flex-cell__label"><?php echo Label::getLabel('LBL_SR'); ?></div>
                                                <div class="flex-cell__content"><span class="row-num"><?php echo ++$counter; ?></span></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex-cell">
                                                <div class="flex-cell__label"><?php echo Label::getLabel('LBL_START_TIME'); ?></div>
                                                <div class="flex-cell__content"><?php echo MyDate::showDate($subOrder['ordles_lesson_starttime'], true); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex-cell">
                                                <div class="flex-cell__label"><?php echo Label::getLabel('LBL_END_TIME'); ?></div>
                                                <div class="flex-cell__content"><?php echo MyDate::showDate($subOrder['ordles_lesson_endtime'], true); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex-cell">
                                                <div class="flex-cell__label"><?php echo Label::getLabel('LBL_STATUS'); ?></div>
                                                <div class="flex-cell__content">
                                                    <?php if ($subOrder['ordles_status'] == Lesson::UNSCHEDULED) { ?>
                                                        <a href="javascript:scheduleForm(<?php echo $subOrder['ordles_id']; ?>)" class="btn btn--primary btn--small"><?php echo Label::getLabel('LBL_SCHEDULE'); ?></a>
                                                    <?php } else { ?>
                                                        <span class="badge color-primary badge--curve"><?php echo Lesson::getStatuses($subOrder['ordles_status']) ?></span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                <?php }
                } ?>
                </div>
            </div>
        </div>
        <div class="payment-panel__foot">
            <div class="align--center">
                <?php if ($order['pmethod_code'] == PaymentMethod::BANK_TRANSFER) { ?>
                    <a href="<?php echo MyUtility::makeUrl('Orders', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_ORDERS'); ?></a>
                <?php } else { ?>
                    <?php if ($order['order_type'] == Order::TYPE_LESSON) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Lessons', '', [], CONF_WEBROOT_DASHBOARD) . '?ordles_status=' . ($subOrder['ordles_status'] ?? '-1'); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_LESSONS'); ?></a>
                    <?php } elseif ($order['order_type'] == Order::TYPE_SUBSCR) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Subscriptions', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_RECURRING_LESSONS'); ?></a>
                    <?php } elseif ($order['order_type'] == Order::TYPE_GCLASS) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Classes', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_CLASSES'); ?></a>
                    <?php } elseif ($order['order_type'] == Order::TYPE_COURSE) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Courses', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_COURSES'); ?></a>
                    <?php } elseif ($order['order_type'] == Order::TYPE_WALLET) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Wallet', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_WALLET'); ?></a>
                    <?php } elseif ($order['order_type'] == Order::TYPE_GFTCRD) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Giftcard', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_GIFTCARDS'); ?></a>
                    <?php } elseif ($order['order_type'] == Order::TYPE_PACKGE) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Packages', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_PACKAGES'); ?></a>
                    <?php } elseif ($order['order_type'] == Order::TYPE_SUBPLAN) { ?>
                        <a href="<?php echo MyUtility::makeUrl('SubscriptionPlans', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary"><?php echo Label::getLabel('MSG_GO_TO_SUBSCRIPTIONS'); ?></a>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<script>
    let SCHEDULED = <?php echo Lesson::SCHEDULED; ?>;
</script>