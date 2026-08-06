<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($subscriptions) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive table--aligned-middle">
        <tr class="title-row">
            <th><?php echo $nameLabel = Label::getLabel('LBL_PLAN_NAME'); ?></th>
            <th><?php echo $startLabel = Label::getLabel('LBL_START_DATE'); ?></th>
            <th><?php echo $endLabel = Label::getLabel('LBL_END_DATE'); ?></th>
            <th><?php echo $planValidity = Label::getLabel('LBL_PLAN_VALIDITY'); ?></th>
            <th><?php echo $lessonDuration = Label::getLabel('LBL_Lesson_duration'); ?></th>
            <th><?php echo $lessonLabel = Label::getLabel('LBL_LESSON_COUNT'); ?></th>
            <th><?php echo $statusLabel = Label::getLabel('LBL_STATUS'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTIONS'); ?></th>
        </tr>
        <?php
        $naLabel = Label::getLabel('LBL_NA');
        $statuses = OrderSubscriptionPlan::getStatuses();
        foreach ($subscriptions as $subscription) {
        ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $nameLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="profile-meta">
                                <div class="profile-meta__details">
                                    <p class="bold-600 color-black"><?php echo $subscription['name']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $startLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyDate::showDate($subscription['ordsplan_start_date']); ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $endLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyDate::showDate($subscription['ordsplan_end_date']); ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $planValidity; ?></div>
                        <div class="flex-cell__content"><?php echo $subscription['ordsplan_validity']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $lessonDuration; ?></div>
                        <div class="flex-cell__content"><?php echo $subscription['ordsplan_duration']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $lessonLabel; ?></div>
                        <div class="flex-cell__content"><?php echo $subscription['ordsplan_used_lesson_count'] . '/' . $subscription['ordsplan_lessons']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $statusLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php
                            $status = $statuses[$subscription['ordsplan_status']];
                            echo $status;
                            ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php if ($subscription['ordsplan_status'] == OrderSubscriptionPlan::ACTIVE) { ?>
                                <a href="javascript:void(0);" onclick="cancelPlan('<?php echo $subscription['ordsplan_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--cancel icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#cancel'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_CANCEL'); ?></div>
                                </a>
                            <?php } ?>
                            <?php if ($subscription['ordsplan_status'] == OrderSubscriptionPlan::EXPIRED) { ?>
                                <a href="javascript:void(0);" onclick="renewForm('<?php echo $subscription['ordsplan_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--cancel icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#renew'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_RENEW'); ?></div>
                                </a>
                            <?php } ?>
                            <a href="<?php echo MyUtility::makeUrl('Lessons') . '?ordles_status=-1&ordles_ordsplan_id=' . $subscription['ordsplan_id']; ?>" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--cancel icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#view'; ?>"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_VIEW_LESSONS'); ?></div>
                            </a>
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
    'page' => $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize'])
];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
?>