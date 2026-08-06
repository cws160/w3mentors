<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$statusClsArr = [
    User::TEACHER => ['cls' => 'is-inprogress', 'html' => '<div class="log-item__media"><span class="log-icon" data-toggle="tooltip"data-placement="top" data-original-title="In-progress"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.463,4.433A10,10,0,0,1,20.19,17.74L17,12h3A8,8,0,0,0,6.46,6.228l-1-1.8ZM18.537,19.567A10,10,0,0,1,3.81,6.26L7,12H4a8,8,0,0,0,13.54,5.772Z"></path></svg></span></div>'],
    User::LEARNER => ['cls' => 'is-rejected', 'html' => '<div class="log-item__media"><span class="log-icon" data-toggle="tooltip"data-placement="top" data-original-title="Rejected"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,22A10,10,0,1,1,22,12,10,10,0,0,1,12,22Zm0-2a8,8,0,1,0-8-8A8,8,0,0,0,12,20Zm-1-5h2v2H11Zm0-8h2v6H11Z"></path></svg></span></div>'],
    User::SUPPORT =>  ['cls' => 'is-approved', 'html' => '<div class="log-item__media"><span class="log-icon" data-toggle="tooltip"data-placement="top" data-original-title="Approved"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,15.172l9.192-9.193,1.415,1.414L10,18,3.636,11.636,5.05,10.222Z"></path></svg></span></div>']
];
$logs = array_reverse($logs);

$orderPrice = 0;
$discountTotal = 0;
$rewardDiscount = 0;
$netAmount = 0;
if ($order['order_type'] == Order::TYPE_LESSON || $order['order_type'] == Order::TYPE_SUBSCR) {
    $orderPrice = $issue['ordles_amount'];
    $discountTotal = $issue['ordles_discount'];
    $rewardDiscount = $issue['ordles_reward_discount'];
} elseif ($order['order_type'] == Order::TYPE_GCLASS || $order['order_type'] == Order::TYPE_PACKGE) {
    $orderPrice = $issue['ordcls_amount'];
    $discountTotal = $issue['ordcls_discount'];
    $rewardDiscount = $issue['ordcls_reward_discount'];
}
$netAmount = $orderPrice - ($discountTotal + $rewardDiscount);
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_ISSUE_LOGS'); ?>
        </h3>
    </div>
    <h6><?php echo Label::getLabel('LBL_ISSUE_STATUS'); ?>:</strong><?php echo Issue::getStatusArr($issue['repiss_status']); ?></h6>
</div>
<div class="card">
    <div class="log-list">
        <?php foreach ($logs as $log) { ?>
            <div class="log-item <?php echo $statusClsArr[$log['reislo_added_by_type']]['cls']; ?>">
                <?php echo $statusClsArr[$log['reislo_added_by_type']]['html']; ?>
                <div class="log-item__content">
                    <span class="log-date"><?php echo MyDate::showDate($log['reislo_added_on'], true); ?></span>
                    <span class="log-title">
                        <span class="log-author"> <?php echo $log['user_fullname']; ?> <?php echo '(' . Issue::getUserTypeArr($log['reislo_added_by_type']) . ')'; ?></span><span class="log-message"> <?php echo $actionArr[$log['reislo_action']]; ?></span>
                    </span>
                    <div style="display:block;">
                        <div class="log-comments">
                            <div class="repeat-element">
                                <div class="repeat-element__title"><?php echo Label::getLabel('LBL_COMMENTS'); ?></div>
                                <div class="repeat-element__content"><?php echo nl2br($log['reislo_comment']); ?> </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        <?php } ?>
        <div class="log-item <?php echo $statusClsArr[User::LEARNER]['cls']; ?>">
            <?php echo $statusClsArr[User::LEARNER]['html']; ?>
            <div class="log-item__content">
                <span class="log-date"><?php echo MyDate::showDate($issue['repiss_reported_on'], true); ?></span>
                <span class="log-title">
                    <span class="log-author"> <?php echo $issue['learner_full_name'];; ?> <?php echo '(' . Issue::getUserTypeArr(User::LEARNER) . ')'; ?></span><span class="log-message"> <?php echo $issue['repiss_title']; ?></span>
                </span>
                <div style="display:block;">
                    <div class="log-comments">
                        <div class="repeat-element">
                            <div class="repeat-element__title"><?php echo Label::getLabel('LBL_COMMENTS'); ?></div>
                            <div class="repeat-element__content"><?php echo nl2br($issue['repiss_comment']); ?> </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="table-group">
    <div class="table-group-head">
        <h6 class="mb-0"><?php echo Label::getLabel('LBL_RECORD_DETAILS'); ?></h6>
    </div>
    <div class="table-group-body">
        <table class="table table-coloum">
            <tbody>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_LANGUAGE'); ?>:</th>
                    <td><?php echo $issue['ordles_tlang_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_ORDER_ID'); ?>:</th>
                    <td><?php echo Order::formatOrderId(FatUtility::int($order['order_id'])); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_RECORD_ID'); ?>:</th>
                    <td><?php echo $issue['repiss_record_id']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_TOTAL_ITEM'); ?>:</th>
                    <td><?php echo $order['order_item_count']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_PRICE'); ?>:</th>
                    <td><?php echo MyUtility::formatMoney($orderPrice); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_ORDER_DISCOUNT_TOTAL'); ?>:</th>
                    <td><?php echo MyUtility::formatMoney($discountTotal); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_REWARD_DISCOUNT'); ?>:</th>
                    <td><?php echo MyUtility::formatMoney($rewardDiscount); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_ORDER_NET_AMOUNT'); ?>:</th>
                    <td><?php echo MyUtility::formatMoney($netAmount); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_Teacher_Name'); ?>:</th>
                    <td> <?php echo $issue['teacher_full_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_Teacher_Join_Time'); ?>:</th>
                    <td><?php echo MyDate::showDate($issue['ordles_teacher_starttime'], true); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_Teacher_End_Time'); ?>:</th>
                    <td><?php echo MyDate::showDate($issue['ordles_teacher_endtime'], true); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_Learner_Name'); ?>:</th>
                    <td><?php echo $issue['learner_full_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_Learner_Join_Time'); ?>:</th>
                    <td><?php echo MyDate::showDate($issue['ordles_student_starttime'], true); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_Learner_end_Time'); ?>:</th>
                    <td><?php echo MyDate::showDate($issue['ordles_student_endtime'], true); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_ENDED_BY'); ?>:</th>
                    <td>
                        <?php
                        if (!empty($issue['ordles_ended_by'])) {
                            if ($issue['ordles_ended_by'] == User::TEACHER) {
                                echo $issue['teacher_full_name'];
                            } elseif ($issue['ordles_ended_by'] == User::LEARNER) {
                                echo $issue['learner_full_name'];
                            } else {
                                echo Label::getLabel('LBL_SYSTEM');
                            }
                        } else {
                            echo Label::getLabel('LBL_NA');
                        }
                        ?>
                    </td>
                </tr>

                <?php if ($order['order_discount_value'] > 0 || $order['order_reward_value'] > 0) { ?>
                    <tr>
                        <td colspan="2"><span class="link-primary"><?php echo Label::getLabel('LBL_NOTE_REFUND_WITH_DISCOUNT_OR_REWARDS'); ?></span></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>