<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($lessons) < 1) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$issueStatusArr = Issue::getStatusArr();
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive table--aligned-middle">
        <tr class="title-row">
            <th><?php echo $userLabel = ($siteUserType == User::LEARNER) ? Label::getLabel('LBL_TEACHER') : Label::getLabel('LBL_LEARNER'); ?></th>
            <th><?php echo $languageLabel = Label::getLabel('LBL_LANGUAGE'); ?></th>
            <th><?php echo $classtimeLabel = Label::getLabel('LBL_SESSION_TIME'); ?></th>
            <th><?php echo $classStatusLabel = Label::getLabel('LBL_SESSION_STATUS'); ?></th>
            <th><?php echo $issueTitleLabel = Label::getLabel('LBL_ISSUE_TITLE'); ?></th>
            <th><?php echo $issueStatusLabel = Label::getLabel('LBL_ISSUE_STATUS'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTIONS'); ?></th>
        </tr>
        <?php
        foreach ($lessons as $issue) {
            $userId = ($siteUserType == User::LEARNER) ? $issue['teacher_id'] : $issue['learner_id'];
        ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $userLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="profile-meta">
                                <div class="profile-meta__media">
                                    <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($issue['learner_first_name']); ?>">
                                        <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $userId, Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '"  alt="' . $issue['learner_first_name'] . '"/>'; ?>
                                    </span>
                                </div>
                                <div class="profile-meta__details">
                                    <p class="color-black">
                                        <?php
                                        if ($siteUserType == User::LEARNER) {
                                            echo $issue['teacher_full_name'];
                                            echo '<br/><small>' . $issue['teacher_country_name'] . '</small>';
                                        } else {
                                            echo $issue['learner_full_name'];
                                            echo '<br/><small>' . $issue['learner_country_name'] . '</small>';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $languageLabel; ?></div>
                        <div class="flex-cell__content"><?php echo $issue['ordles_tlang_name'] ?? Label::getLabel('LBL_NA'); ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $classtimeLabel; ?></div>
                        <div class="flex-cell__content"><?php echo MyDate::showDate($issue['ordles_lesson_starttime'], true); ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $classStatusLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="data-group">
                                <?php if ($issue['repiss_record_type'] == AppConstant::LESSON) { ?>
                                    <span><?php echo Lesson::getStatuses($issue['ordles_status']); ?></span>
                                <?php } elseif ($issue['repiss_record_type'] == AppConstant::GCLASS) { ?>
                                    <span><?php echo OrderClass::getStatuses($issue['ordcls_status']); ?></span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $issueTitleLabel; ?></div>
                        <div class="flex-cell__content"><?php echo $issue['repiss_title']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $issueStatusLabel; ?></div>
                        <div class="flex-cell__content"><span class="badge color-secondary badge--curve"><?php echo $issueStatusArr[$issue['repiss_status']]; ?></span></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="actions-group">
                                <a href="javascript:void(0);" onclick="viewIssue('<?php echo $issue['repiss_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--issue icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#view'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel("LBL_VIEW_DETAIL"); ?></div>
                                </a>
                                <?php if ($issue['repiss_status'] == Issue::STATUS_PROGRESS && $siteUserType == User::TEACHER) { ?>
                                    <a href="javascript:void(0);" onclick="resolveForm('<?php echo $issue['repiss_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                        <svg class="icon icon--issue icon--small">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#resolve-issue'; ?>"></use>
                                        </svg>
                                        <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel("LBL_RESOLVE_ISSUE"); ?></div>
                                    </a>
                                <?php } ?>
                            </div>
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
    $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
