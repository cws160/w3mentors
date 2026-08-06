<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="profile-cover">
    <div class="profile-head">
        <div class="detail-wrapper">
            <div class="profile__media">
                <div class="avtar avtar--xlarge" data-title="<?php echo CommonHelper::getFirstChar($teacher['user_first_name']); ?>">
                    <?php
                    $img = MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['user_id'], Afile::SIZE_MEDIUM]);
                    echo '<img src="' . $img . '" alt="' . $teacher['user_first_name'] . ' ' . $teacher['user_last_name'] . '" />';
                    ?>
                </div>
            </div>
            <div class="profile-detail">
                <div class="profile-detail__head">
                    <div class="tutor-name">
                        <h4><?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name']; ?></h4>
                        <div class="flag">
                            <?php if ($teacher['user_country_id'] > 0) { ?>
                                <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_COUNTRY_FLAG, $teacher['user_country_id'], Afile::SIZE_MEDIUM]); ?>" alt="<?php echo $teacher['user_country_name']; ?>" />
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="profile-detail__body">
                    <div class="info-wrapper">
                        <div class="info-tag location">
                            <svg class="icon icon--location">
                                <use xlink:href=" <?php echo CONF_WEBROOT_URL . 'images/sprite.svg#location' ?>"></use>
                            </svg>
                            <span class="lacation__name"><?php echo $teacher['user_country_name']; ?></span>
                        </div>
                        <div class="info-tag ratings">
                            <svg class="icon icon--rating">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#rating' ?>"></use>
                            </svg>
                            <span class="value"><?php echo $teacher['testat_ratings']; ?></span>
                            <span class="count"><?php echo '(' . $teacher['testat_reviewes'] . ')'; ?></span>
                        </div>
                        <div class="info-tag list-count">
                            <div class="total-count"><span class="value"><?php echo $teacher['testat_students']; ?></span><?php echo Label::getLabel('LBL_Students') ?></div>
                            <div class="total-count"><span class="value"><?php echo $teacher['testat_lessons']; ?></span><?php echo Label::getLabel('LBL_Lessons'); ?></div>
                        </div>
                    </div>
                    <div class="har-rate"><?php echo Label::getLabel('LBL_HOURLY_RATE'); ?><b> <?php echo MyUtility::formatMoney($teacher['testat_minprice']); ?> - <?php echo MyUtility::formatMoney($teacher['testat_maxprice']); ?></b></div>
                    <div class="tutor-lang"><b><?php echo Label::getLabel('LBL_TEACHES:'); ?></b> <?php echo $teacher['teacherTeachLanguageName']; ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="profile-primary">
        <div class="panel-cover">
            <div class="panel-cover__head panel__head-trigger panel__head-trigger-js">
                <h3><?php echo Label::getLabel('LBL_About'); ?> <?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name']; ?></h3>
            </div>
            <div class="panel-cover__body panel__body-target panel__body-target-js" style="display:block;">
                <div class="content__row">
                    <p><?php echo nl2br($teacher['user_biography']); ?></p>
                </div>
                <div class="content__row">
                    <h4><?php echo Label::getLabel('LBL_Speaks'); ?></h4>
                    <p><?php $this->includeTemplate('teachers/_partial/SpeakLanguages.php', $teacher, false); ?></p>
                </div>
            </div>
        </div>
    </div>