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
            <th><?php echo $nameLabel = ($siteUserType == User::LEARNER) ? Label::getLabel('LBL_TEACHER') : Label::getLabel('LBL_LEARNER'); ?></th>
            <th><?php echo $startLabel = Label::getLabel('LBL_START_DATE'); ?></th>
            <th><?php echo $endLabel = Label::getLabel('LBL_END_DATE'); ?></th>
            <th><?php echo $languageLabel = Label::getLabel('LBL_LANGUAGE'); ?></th>
            <th><?php echo $lessonLabel = Label::getLabel('LBL_LESSONS'); ?></th>
            <th><?php echo $statusLabel = Label::getLabel('LBL_STATUS'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTIONS'); ?></th>
        </tr>
        <?php
        $naLabel = Label::getLabel('LBL_NA');
        $statuses = Subscription::getStatuses();
        foreach ($subscriptions as $subscription) {
        ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $nameLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="profile-meta">
                                <div class="profile-meta__media">
                                    <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($subscription['first_name']); ?>">
                                        <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $subscription['user_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '"  alt="' . $subscription['first_name'] . ' ' . $subscription['last_name'] . '"/>'; ?>
                                    </span>
                                </div>
                                <div class="profile-meta__details">
                                    <p class="bold-600 color-black"><?php echo $subscription['first_name'] . ' ' . $subscription['last_name']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $startLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyDate::showDate($subscription['ordsub_startdate']); ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $endLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyDate::showDate($subscription['ordsub_enddate']); ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $languageLabel; ?></div>
                        <div class="flex-cell__content"><?php echo $subscription['langName']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $lessonLabel; ?></div>
                        <div class="flex-cell__content"><?php echo $subscription['lessonCount']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $statusLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php
                            $status = $statuses[$subscription['ordsub_status']];
                            if ($subscription['ordsub_status'] == Subscription::ACTIVE && strtotime($subscription['ordsub_enddate']) < $subscription['ordsub_currenttime_unix']) {
                                $status = Label::getLabel('LBL_EXPIRED');
                            }
                            echo $status;
                            ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <a href="<?php echo MyUtility::makeUrl('Lessons') . '?ordles_status=-1&order_id=' . $subscription['order_id']; ?>" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--cancel icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . '/images/sprite.svg#view'; ?>"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_VIEW_LESSONS'); ?></div>
                            </a>
                            <?php if ($subscription['canCancel']) { ?>
                                <a href="javascript:void(0);" onclick="cancelForm('<?php echo $subscription['ordsub_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--cancel icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#cancel'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_CANCEL'); ?></div>
                                </a>
                            <?php } ?>
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