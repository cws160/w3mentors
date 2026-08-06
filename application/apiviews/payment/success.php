<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="payment-page">
    <div class="cc-payment">
        <div class="logo-payment">
            <?php echo MyUtility::getLogo(); ?>
        </div>
        <?php if ($siteUserId > 0) { ?>
            <div class="reff row">
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <p><?php echo Label::getLabel('LBL_PAYABLE_AMOUNT'); ?> : <strong><?php echo MyUtility::formatMoney($order['order_net_amount']) ?></strong> </p>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-12">
                    <p><?php echo Label::getLabel('LBL_ORDER_INVOICE'); ?>: <strong><?php echo Order::formatOrderId($order["order_id"]); ?></strong></p>
                </div>
            </div>
        <?php } ?>
        <div class="payment-from">
            <div class="payable-form__body">
                <div class="message-display--small align--center">
                    <div class="message-display__icon">
                        <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 120 120">
                            <path fill="#000" d="M105.823,52.206a4.4,4.4,0,0,0-4.4,4.393v4.425a43.258,43.258,0,0,1-43.3,43.188H58.092a43.213,43.213,0,1,1,.024-86.426h0.026a43.111,43.111,0,0,1,17.6,3.741A4.395,4.395,0,1,0,79.325,13.5,51.871,51.871,0,0,0,58.147,9H58.116a52,52,0,1,0-.029,104h0.031a52.054,52.054,0,0,0,52.108-51.973V56.6A4.4,4.4,0,0,0,105.823,52.206Z" transform="translate(-0.516 -1)" />
                            <path class="-color-fill" d="M113.706,15.075a4.409,4.409,0,0,0-6.226,0L58.117,64.335,46.918,53.16a4.4,4.4,0,0,0-6.226,6.213L55,73.655a4.409,4.409,0,0,0,6.226,0l52.476-52.367A4.386,4.386,0,0,0,113.706,15.075Z" transform="translate(-0.516 -1)" />
                        </svg>
                    </div>
                    <h1 class="-color-secondary"><?php echo Label::getLabel('MSG_THANKYOU_FOR_PURCHASE'); ?></h1>
                </div>
            </div>
        </div>
        <?php if (!API_CALL) { ?>
            <div class="max-scroll">
                <?php if (in_array($order['order_type'], [Order::TYPE_LESSON, Order::TYPE_SUBSCR])) { ?>
                    <?php if (!empty($lessons)) { ?>
                        <table class="table table--responsive">
                            <thead>
                                <tr class="row-trigger title-row">
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
                                                <div class="flex-cell__content"><?php echo ++$counter; ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex-cell">
                                                <div class="flex-cell__label"><?php echo Label::getLabel('LBL_START_TIME'); ?></div>
                                                <div class="flex-cell__content"><?php echo MyDate::formatDate($subOrder['ordles_lesson_starttime']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex-cell">
                                                <div class="flex-cell__label"><?php echo Label::getLabel('LBL_END_TIME'); ?></div>
                                                <div class="flex-cell__content"><?php echo MyDate::formatDate($subOrder['ordles_lesson_endtime']); ?></div>
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
                    <?php } ?>
                <?php } ?>
            </div>
            <div class="align--center">
                <?php if (in_array($order['order_type'], [Order::TYPE_LESSON, Order::TYPE_SUBSCR])) { ?>
                    <a href="<?php echo MyUtility::makeUrl('Lessons', '', [], CONF_WEBROOT_DASHBOARD) . '?ordles_status=' . ($subOrder['ordles_status'] ?? '-1'); ?>" class="btn btn--secondary"><?php echo Label::getLabel('MSG_GO_TO_LESSONS'); ?></a>
                <?php } elseif ($order['order_type'] == Order::TYPE_GCLASS) { ?>
                    <a href="<?php echo MyUtility::makeUrl('Classes', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--secondary"><?php echo Label::getLabel('MSG_GO_TO_CLASSES'); ?></a>
                <?php } elseif ($order['order_type'] == Order::TYPE_COURSE) { ?>
                    <a href="<?php echo MyUtility::makeUrl('Courses', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--secondary"><?php echo Label::getLabel('MSG_GO_TO_COURSES'); ?></a>
                <?php } elseif ($order['order_type'] == Order::TYPE_WALLET) { ?>
                    <a href="<?php echo MyUtility::makeUrl('Wallet', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--secondary"><?php echo Label::getLabel('MSG_GO_TO_WALLET'); ?></a>
                <?php } elseif ($order['order_type'] == Order::TYPE_GFTCRD) { ?>
                    <a href="<?php echo MyUtility::makeUrl('Giftcard', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--secondary"><?php echo Label::getLabel('MSG_GO_TO_GIFTCARDS'); ?></a>
                <?php } elseif ($order['order_type'] == Order::TYPE_PACKGE) { ?>
                    <a href="<?php echo MyUtility::makeUrl('Packages', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--secondary"><?php echo Label::getLabel('MSG_GO_TO_PACKAGES'); ?></a>
                <?php } ?>
            </div>
            <span class="-gap"></span>
        <?php } ?>
    </div>
</div>
<script>
    let SCHEDULED = <?php echo Lesson::SCHEDULED; ?>;
</script>