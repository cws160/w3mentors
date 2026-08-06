<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="col-xl-8 col-lg-10">
    <div class="author-bio">
        <?php if ($teacher) { ?>
            <div class="author-bio__head">
                <h5 class="bold-700"><?php echo Label::getLabel('LBL_ABOUT_TUTOR'); ?></h5>
                <div class="author-box">
                    <div class="author-box__media">
                        <div class="avtar avtar--large" data-title="<?php echo strtoupper($teacher['user_first_name'][0]); ?>">
                            <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['user_id'], Afile::SIZE_MEDIUM], CONF_WEBROOT_FRONTEND); ?>" alt="<?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name'] ?>">
                        </div>
                    </div>
                    <div class="author-box__content">
                        <h4 class="author-name mb-2">
                            <?php echo ucfirst($teacher['user_first_name'] . ' ' . $teacher['user_last_name']) ?>
                        </h4>
                        <div class="rating mb-3">
                            <svg class="rating__media">
                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#rating"></use>
                            </svg>
                            <span class="rating__value"><?php echo $teacher['testat_ratings']; ?></span>
                            <span class="rating__count"><?php echo '(' . $teacher['testat_reviewes'] . ' ' . Label::getLabel('LBL_REVIEWS') . ')'; ?></span>
                        </div>
                        <div class="course-counts mb-2">
                            <div class="course-counts__item">
                                <div class="course-info">
                                    <span class="course-info__media">
                                        <svg class="icon icon--teaches icon--18">
                                            <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND ?>images/sprite.svg#icon-lecture">
                                            </use>
                                        </svg>
                                    </span>
                                    <span class="course-info__title">
                                        <strong><?php echo Label::getLabel('LBL_COURSES'); ?></strong>
                                        <?php echo $courses ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <?php if ($isProfileComplete[$teacher['user_id']] == true) { ?>
                            <a href="<?php echo MyUtility::makeUrl('teachers', 'view', [$teacher['user_username']], CONF_WEBROOT_FRONTEND) ?>" class="btn btn--primary-bordered btn--small">
                                <?php echo Label::getLabel('LBL_VIEW_PROFILE'); ?>
                            </a>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="author-bio__body mt-5">
                <?php if (!empty($biography)) { ?>
                    <h5 class="bold-700"><?php echo Label::getLabel('LBL_BIOGRAPHY'); ?></h5>
                    <div class="author-box__desc">
                        <p><?php echo nl2br($biography); ?></p>
                    </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <div class="author-bio__head">
                <h5 class=""><?php echo Label::getLabel('LBL_TUTOR_PROFILE_NOT_AVAILABLE'); ?></h5>
            </div>
        <?php } ?>
    </div>
</div>