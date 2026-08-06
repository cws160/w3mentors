<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="slider slider-onethird-js">
    <?php foreach ($moreCourses as $crs) { ?>
        <!-- [ SLIDER ITEM ========= -->
        <div class="slider__item">
            <!-- [ COURSE CARD ========= -->
            <div class="card-cover">
                <div class="short-card">
                    <div class="short-card__head">
                        <div class="short-card__media ratio ratio--16by9">
                            <a href="<?php echo MyUtility::makeUrl('Courses', 'view', [CommonHelper::htmlEntitiesDecode($crs['course_slug'])]); ?>">
                                <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_COURSE_IMAGE, $crs['course_id'], 'MEDIUM', $siteLangId], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $crs['course_title']; ?>">
                            </a>
                        </div>
                    </div>
                    <div class="short-card__body">
                        <div class="rating">
                            <svg class="rating__media">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#rating'; ?>"></use>
                            </svg>
                            <span class="rating__value">
                                <?php echo $crs['course_ratings']; ?> 
                            </span>
                            <span class="rating__count"> (<?php echo $crs['course_reviews']; ?>) </span>
                        </div>
                        <h6 class="short-card__title">
                            <a href="<?php echo MyUtility::makeUrl('Courses', 'view', [CommonHelper::htmlEntitiesDecode($crs['course_slug'])]); ?>">
                                <?php echo CommonHelper::renderHtml($crs['course_title']); ?>
                            </a>
                        </h6>
                        <div class="card-element">
                            <span class="card-element__item">
                                <svg width="16" height="16">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-clock'; ?>"></use>
                                </svg>
                                <?php echo CommonHelper::convertDuration($crs['course_duration']); ?>
                            </span>
                            <span class="card-element__item">
                                <svg width="20" height="14">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-lectures'; ?>"></use>
                                </svg>
                                <?php echo $crs['course_lectures'] . ' ' . Label::getLabel('LBL_LECTURES'); ?>
                            </span>
                        </div>
                        <?php if ($crs['course_type'] != Course::TYPE_FREE) { ?>
                            <h4 class="bold-700 price-value">
                                <?php echo CourseUtility::formatMoney($crs['course_price']); ?>
                            </h4>
                        <?php } else { ?>
                            <h4 class="free-text color-red bold-700 price-value">
                                <?php echo Label::getLabel('LBL_FREE'); ?>
                            </h4>
                        <?php } ?>
                    </div>
                    <div class="short-card__footer">
                        <?php
                        $url = 'javascript:void(0);';
                        if ($crs['is_profile_complete'] == true) {
                            $url = MyUtility::makeUrl('Teachers', 'view', [$crs['teacher_username']]);
                        }
                        ?>
                        <a href="<?php echo $url; ?>" class="profile-meta d-flex align-items-center">
                            <div class="profile-meta__media">
                                <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($crs['teacher_first_name'], true); ?>">
                                    <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $crs['teacher_id'], Afile::SIZE_SMALL]); ?>" alt="<?php echo ucwords($crs['teacher_first_name'] . ' ' . $crs['teacher_last_name']); ?>">
                                </span>
                            </div>
                            <div class="profile-meta__details">
                                <h6 class="bold-600 profile-meta__title">
                                    <?php echo ucwords(CommonHelper::renderHtml($crs['teacher_first_name'] . ' ' . $crs['teacher_last_name'])); ?>
                                </h6>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <!-- ] -->
        </div>
        <!-- ] -->
    <?php } ?>
</div>