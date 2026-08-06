<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($records) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive table--aligned-middle">
        <tr class="title-row">
            <th><?php echo $nameLabel = Label::getLabel('LBL_LEARNER_NAME'); ?></th>
            <th><?php echo $planName = Label::getLabel('LBL_PLAN_NAME'); ?></th>
            <th><?php echo $lessonPrice = Label::getLabel('LBL_LESSON_PRICE'); ?></th>
            <th><?php echo $lessons = Label::getLabel('LBL_LESSONS'); ?></th>
            <th><?php echo $statusLabel = Label::getLabel('LBL_SUBSRIPTION_STATUS'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTIONS'); ?></th>
        </tr>
        <?php
        $naLabel = Label::getLabel('LBL_NA');
        $statuses = OrderSubscriptionPlan::getStatuses();
        foreach ($records as $row) {
        ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $nameLabel ?></div>
                        <div class="flex-cell__content">
                            <div class="profile-meta">
                                <div class="profile-meta__details">
                                    <p class="bold-600 color-black"><?php echo $row['user_first_name'] . ' ' . $row['user_last_name']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $planName; ?></div>
                        <div class="flex-cell__content">
                            <?php echo $row['plan_name'] ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $lessonPrice ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyUtility::formatMoney(round($row['ordsplan_amount'] / $row['ordsplan_lessons'], 2)); ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $lessonPrice ?></div>
                        <div class="flex-cell__content">
                            <?php echo $row['lessons']; ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $statusLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php
                            $status = $statuses[$row['ordsplan_status']];
                            echo $status;
                            ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <a href="<?php echo MyUtility::makeUrl('Lessons') . '?ordles_status=-1&ordles_ordsplan_id=' . $row['ordsplan_id']; ?>" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
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