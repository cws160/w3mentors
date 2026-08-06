<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArr = AppConstant::getYesNoArr();
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_VIEW_LESSON_DETAIL'); ?></h3>
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
            <th><?php echo Label::getLabel('LBL_LANGUAGE'); ?>:</th>
            <td><?php echo $order['ordles_tlang_name']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_SERVICE_TYPE'); ?>:</th>
            <td><?php echo AppConstant::getServiceType($order['ordles_offline']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_STATUS'); ?>:</th>
            <td><?php echo Lesson::getStatuses($order['ordles_status']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_START_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['ordles_lesson_starttime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ENDS'); ?>:</th>
            <td><?php echo MyDate::showDate($order['ordles_lesson_endtime'], true); ?></td>
        </tr>
        <tr>
            <th> <?php echo Label::getLabel('LBL_TEACHER_START_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['teacher_format_starttime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_TEACHER_END_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['teacher_format_endtime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LEARNER_START_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['student_format_starttime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LEARNER_END_TIME'); ?>:</th>
            <td><?php echo MyDate::showDate($order['student_format_endtime'], true); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LESSON_PRICE'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordles_amount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_DISCOUNT_TOTAL'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordles_discount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REWARD_DISCOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordles_reward_discount']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ORDER_NET_AMOUNT'); ?>:</th>
            <td><?php echo MyUtility::formatMoney(($order['ordles_amount'] - ($order['ordles_reward_discount'] + $order['ordles_discount']))); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ADMIN_COMMISSION'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($order['ordles_commission_amount']); ?></td>
        </tr>
        <?php if (User::isAffiliateEnabled()) { ?>
            <tr>
                <th><?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION'); ?>:</th>
                <td><?php echo MyUtility::formatMoney($order['ordles_affiliate_commission']); ?></td>
            </tr>
        <?php } ?>
        <tr>
            <th><?php echo Label::getLabel('LBL_TEACHER_PAID'); ?>:</th>
            <td><?php echo (empty(FatUtility::float($order['ordles_teacher_paid']))) ? Label::getLabel('LBL_NO') : Label::getLabel('LBL_YES'); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_REVIEWED_ON_LESSON'); ?>:</th>
            <td><?php echo $yesNoArr[$order['ordles_reviewed']]; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ISSUE_REPORTED'); ?>:</th>
            <td><?php echo ($order['repiss_id'] > 0) ? $yesNoArr[AppConstant::YES] : $yesNoArr[AppConstant::NO]; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_REFUND'); ?>:</th>
            <td><?php echo ($order['ordles_refund'] > 0) ? MyUtility::formatMoney($order['ordles_refund']) : Label::getLabel('LBL_NA'); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_DURATION'); ?>:</th>
            <td><?php echo $order['ordles_duration'] . ' ' . Label::getLabel('LBL_MINS'); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_ORDER_ID'); ?>:</th>
            <td><a class="link-text link-underline" target="_blank" href="<?php echo MyUtility::makeUrl('Orders', 'view', [$order['ordles_order_id']]); ?>"><?php echo Label::getLabel('LBL_VIEW') . ' ' . Order::formatOrderId($order['ordles_order_id']); ?> </a></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LESSON_ENDED_BY'); ?>:</th>
            <td>
                <?php
                if (!empty($order['ordles_ended_by'])) {
                    if ($order['ordles_ended_by'] == User::TEACHER) {
                        echo $order['teacher_first_name'] . ' ' . $order['teacher_last_name'];
                    } elseif ($order['ordles_ended_by'] == User::LEARNER) {
                        echo $order['learner_first_name'] . ' ' . $order['learner_last_name'];
                    } else {
                        echo Label::getLabel('LBL_SYSTEM');
                    }
                } else {
                    echo Label::getLabel('LBL_NA');
                }
                ?>
            </td>
        </tr>
    </table>
</div>