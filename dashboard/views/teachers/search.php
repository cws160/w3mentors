<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($teachers) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive table--aligned-middle">
        <tr class="title-row">
            <th><?php echo $learnerLabel = Label::getLabel('LBL_TEACHER'); ?></th>
            <th><?php echo $lessonsLabel = Label::getLabel('LBL_LESSONS'); ?></th>
            <th><?php echo $classesLabel = Label::getLabel('LBL_CLASSES'); ?></th>
            <th><?php echo $lessonsOfferLabel = Label::getLabel('LBL_LESSONS_OFFER'); ?></th>
            <th><?php echo $classesOfferLabel = Label::getLabel('LBL_CLASSES_OFFER'); ?></th>
            <th><?php echo $packageLabel = Label::getLabel('LBL_PACKAGE_OFFER'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTIONS'); ?></th>
        </tr>
        <?php
        $offerPriceLabel = Label::getLabel('LBL_{percentages}%_OFF_ON_{duration}_MINUTES_SESSION');
        $packageOfferLabel = Label::getLabel('LBL_{percentages}%');
        $naLabel = Label::getLabel('LBL_NA');
        foreach ($teachers as $teacher) {
            $profileLink = 'javascript:void(0);';
            $aTarget = '';
            if (!empty($teacher['teacher_username'])) {
                $profileLink = MyUtility::makeUrl('Teachers', 'view', [$teacher['teacher_username']], CONF_WEBROOT_FRONTEND);
                $aTarget = 'target="_blank"';
            }

        ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $learnerLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="profile-meta">
                                <div class="profile-meta__media">
                                    <a <?php echo $aTarget ?> title="<?php echo $teacher['teacher_full_name']; ?>" href="<?php echo $profileLink; ?>">
                                        <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($teacher['teacher_full_name']); ?>">
                                            <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['offpri_teacher_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '"  alt="' . $teacher['teacher_full_name'] . '"/>'; ?>
                                        </span>
                                    </a>
                                </div>
                                <div class="profile-meta__details">
                                    <a <?php echo $aTarget ?> title="<?php echo $teacher['teacher_full_name']; ?>" href="<?php echo $profileLink; ?>">
                                        <p class="bold-600 color-black"><?php echo $teacher['teacher_full_name']; ?></p>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $lessonsLabel; ?></div>
                        <div class="flex-cell__content"><?php echo $teacher['offpri_lessons']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $classesLabel; ?></div>
                        <div class="flex-cell__content"><?php echo $teacher['offpri_classes']; ?></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $lessonsOfferLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php if (count($teacher['offpri_lesson_price'])) { ?>
                                <div class="offers-box__group">
                                    <?php foreach ($teacher['offpri_lesson_price'] as $offer) { ?>
                                        <span class="offers-box__item">
                                            <span class="offers-box__item-media me-2">
                                                <svg class="icon icon--offer" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                    <path d="M10.117,2.1,2.5,3.189,1.414,10.8l7.071,7.071a.769.769,0,0,0,1.088,0l7.616-7.616a.769.769,0,0,0,0-1.088ZM9.573,3.732l5.984,5.983L9.029,16.243,3.046,10.26l.815-5.712,5.712-.815Zm-1.631,4.9a1.539,1.539,0,1,0-2.177,0A1.539,1.539,0,0,0,7.942,8.628Z" transform="translate(2.586 1.9)"></path>
                                                </svg>
                                            </span>
                                            <span class="offers-box__item-label"><?php echo str_replace(['{duration}', '{percentages}'], [$offer['duration'], number_format(round($offer['offer'], 2), 2, '.', '')], $offerPriceLabel); ?></span>
                                        </span>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <?php echo $naLabel; ?>
                            <?php } ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $classesOfferLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php if (count($teacher['offpri_class_price'])) { ?>
                                <div class="offers-box__group">
                                    <?php foreach ($teacher['offpri_class_price'] as $offer) { ?>
                                        <span class="offers-box__item">
                                            <span class="offers-box__item-media me-2">
                                                <svg class="icon icon--offer" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                    <path d="M10.117,2.1,2.5,3.189,1.414,10.8l7.071,7.071a.769.769,0,0,0,1.088,0l7.616-7.616a.769.769,0,0,0,0-1.088ZM9.573,3.732l5.984,5.983L9.029,16.243,3.046,10.26l.815-5.712,5.712-.815Zm-1.631,4.9a1.539,1.539,0,1,0-2.177,0A1.539,1.539,0,0,0,7.942,8.628Z" transform="translate(2.586 1.9)"></path>
                                                </svg>
                                            </span>
                                            <span class="offers-box__item-label"><?php echo str_replace(['{duration}', '{percentages}'], [$offer['duration'], number_format(round($offer['offer'], 2), 2, '.', '')], $offerPriceLabel); ?></span>
                                        </span>
                                    <?php } ?>
                                </div>
                            <?php } else { ?>
                                <?php echo $naLabel; ?>
                            <?php } ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $packageLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php if (!empty($teacher['offpri_package_price'])) { ?>
                                <div class="offers-box__group">
                                    <span class="offers-box__item">
                                        <span class="offers-box__item-media me-2">
                                            <svg class="icon icon--offer" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                                <path d="M10.117,2.1,2.5,3.189,1.414,10.8l7.071,7.071a.769.769,0,0,0,1.088,0l7.616-7.616a.769.769,0,0,0,0-1.088ZM9.573,3.732l5.984,5.983L9.029,16.243,3.046,10.26l.815-5.712,5.712-.815Zm-1.631,4.9a1.539,1.539,0,1,0-2.177,0A1.539,1.539,0,0,0,7.942,8.628Z" transform="translate(2.586 1.9)"></path>
                                            </svg>
                                        </span>
                                        <span class="offers-box__item-label"><?php echo str_replace('{percentages}', number_format(round($teacher['offpri_package_price'], 2), 2, '.', ''), $packageOfferLabel); ?></span>
                                    </span>
                                </div>
                            <?php } else { ?>
                                <?php echo $naLabel; ?>
                            <?php } ?>
                        </div>
                    </div>
                </td>
                <td>
                    <?php if (empty($teacher['teacher_deleted'])) { ?>
                        <div class="flex-cell">
                            <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                            <div class="flex-cell__content">
                                <div class="actions-group">
                                    <a href="javascript:void(0);" onClick="threadForm(<?php echo $teacher['offpri_teacher_id']; ?>,<?php echo Thread::PRIVATE ?>);" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                        <svg class="icon icon--messaging">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#message'; ?>"></use>
                                        </svg>
                                        <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_Message'); ?></div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php
$pagingArr = [
    'page' => $post['pageno'],
    $post['pageno'],
    'pageSize' => $post['pagesize'],
    'pageCount' => $pageCount,
    'recordCount' => $recordCount,
];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
?>