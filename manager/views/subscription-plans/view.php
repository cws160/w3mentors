<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArr = AppConstant::getYesNoArr();
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_VIEW_SUBSCRIPTION_PLAN_DETAIL'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th><?php echo Label::getLabel('LBL_PLAN_IDENTIFIER'); ?>:</th>
            <td><?php echo $plan['subplan_title'] ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PLAN_NAME'); ?>:</th>
            <td><?php echo $plan['lang_name'] ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PLAN_VALIDITY'); ?>:</th>
            <td><?php echo $plan['subplan_validity']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_Lesson_duration'); ?>:</th>
            <td><?php echo $plan['subplan_lesson_duration']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_LESSON_COUNT'); ?>:</th>
            <td><?php echo $plan['subplan_lesson_count']; ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PLAN_PRICE'); ?>:</th>
            <td><?php echo MyUtility::formatMoney($plan['subplan_price']); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_PLAN_STATUS'); ?>:</th>
            <td><?php echo AppConstant::getActiveArr($plan['subplan_active'], $siteLangId); ?></td>
        </tr>
        <tr>
            <th><?php echo Label::getLabel('LBL_CREATED_DATE'); ?>:</th>
            <td><?php echo  MyDate::showDate($plan['subplan_created'], true); ?></td>
        </tr>
    </table>
</div>