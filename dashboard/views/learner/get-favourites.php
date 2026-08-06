<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="table-scroll">
    <table class="table table--styled table--responsive table--aligned-middle">
        <tr class="title-row">
            <th><?php echo $teacherLabel = Label::getLabel('LBL_TEACHER'); ?></th>
            <th><?php echo $teachesLabel = Label::getLabel('LBL_TEACHES'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTION'); ?></th>
        </tr>
        <?php
        foreach ($favouritesData['Favourites'] as $favourite) {
            $teacherDetailPageUrl = MyUtility::makeUrl('Teachers', 'view', [$favourite['user_username']], CONF_WEBROOT_FRONTEND);
        ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $teacherLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="profile-meta">
                                <div class="profile-meta__media">
                                    <a title="<?php echo $favourite['user_first_name'] . ' ' . $favourite['user_last_name']; ?>" href="<?php echo $teacherDetailPageUrl; ?>">
                                        <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($favourite['user_first_name']); ?>">
                                            <?php
                                            $img = FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $favourite['uft_teacher_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '?' . time();
                                            echo '<img src="' . $img . '"  alt="' . $favourite['user_first_name'] . '"/>';
                                            ?>
                                        </span>
                                    </a>
                                </div>
                                <div class="profile-meta__details">
                                    <p class="bold-600 color-black"><?php echo $favourite['user_first_name'] . ' ' . $favourite['user_last_name']; ?></p>
                                    <p class="small"><?php echo $countriesArr[$favourite['user_country_id']]; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $teachesLabel; ?></div>
                        <div class="flex-cell__content"><?php echo implode(", ", explode(',', $favourite['teacherTeachLanguageName'] ?? '')); ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <a href="javascript:void(0);" onclick="toggleTeacherFavorite(<?php echo $favourite['uft_teacher_id']; ?>, this, 1);" class="btn btn--small bg-primary">
                                <?php echo Label::getLabel('LBL_UNFAVORITE'); ?>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'frmFavSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $favouritesData['pagingArr'], false);
if (empty($favouritesData['Favourites'])) {
    $this->includeTemplate('_partial/no-record-found.php');
}
