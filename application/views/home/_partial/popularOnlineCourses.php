<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (count($courses) > 0) { ?>
    <section class="section section--cardslider">
        <div class="container container--xxl">
            <div class="section__header mb-xl-3" data-aos="fade-up" data-aos-duration="1000">
                <h2><?php echo Label::getLabel('LBL_ONLINE_COURSES_AT_ONE_PLACE'); ?></h2>
            </div>
            <div class="section__body" data-aos="fade-up" data-aos-duration="1000">
                <div class="slider slider-oneforth slider-oneforth-js">
                    <?php foreach ($courses as $id => $course) { ?>
                        <div class="slider__item">
                            <div class="card-cover">
                                <div class="short-card">
                                    <div class="short-card__head">
                                        <div class="short-card__media ratio ratio--16by9">
                                            <a href="<?php echo MyUtility::makeUrl('Courses', 'view', [$course['course_slug']]); ?>">
                                                <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_COURSE_IMAGE, $course['course_id'], 'MEDIUM', $siteLangId], CONF_WEBROOT_FRONT_URL), CONF_IMG_CACHE_TIME); ?>" alt="<?php echo $course['course_title']; ?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="short-card__body">
                                        <div class="rating">
                                            <svg class="rating__media">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#rating'; ?>"></use>
                                            </svg>
                                            <span class="rating__value">
                                                <?php echo $course['course_ratings']; ?>
                                            </span>
                                            <span class="rating__count">
                                                <?php echo '(' . $course['course_reviews'] . ')'; ?>
                                            </span>
                                        </div>
                                        <h6 class="short-card__title">
                                            <a href="<?php echo MyUtility::makeUrl('Courses', 'view', [$course['course_slug']]); ?>" class="">
                                                <?php echo CommonHelper::renderHtml($course['course_title']); ?>
                                            </a>
                                        </h6>
                                        <div class="card-element">
                                            <span class="card-element__item">
                                                <svg width="16" height="16">
                                                    <use xlink:href="<?php echo  CONF_WEBROOT_URL . 'images/sprite.svg#icon-clock'; ?>"></use>
                                                </svg>
                                                <?php echo CommonHelper::convertDuration($course['course_duration']); ?>
                                            </span>
                                            <span class="card-element__item">
                                                <svg width="20" height="14">
                                                    <use xlink:href="<?php echo  CONF_WEBROOT_URL . 'images/sprite.svg#icon-lectures'; ?>"></use>
                                                </svg>
                                                <?php echo $course['course_lectures'] . ' ' . Label::getLabel('LBL_LECTURES'); ?>
                                            </span>
                                        </div>
                                        <?php if ($course['course_type'] != Course::TYPE_FREE) { ?>
                                            <h4 class="bold-700 price-value">
                                                <?php echo CourseUtility::formatMoney($course['course_price']); ?>
                                            </h4>
                                        <?php } else { ?>
                                            <h4 class="bold-700 price-value color-red">
                                                <?php echo Label::getLabel('LBL_FREE'); ?>
                                            </h4>
                                        <?php } ?>
                                    </div>
                                    <div class="short-card__footer">
                                        <?php
                                        $url = 'javascript:void(0);';
                                        if ($course['is_profile_complete'] == true) {
                                            $url = MyUtility::makeUrl('Teachers', 'view', [$course['teacher_username']]);
                                        }
                                        ?>
                                        <a href="<?php echo $url; ?>" class="profile-meta d-flex align-items-center">
                                            <div class="profile-meta__media">
                                                <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($course['teacher_first_name'], true); ?>">
                                                    <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $course['teacher_id'], Afile::SIZE_SMALL]); ?>" alt="">
                                                </span>
                                            </div>
                                            <div class="profile-meta__details">
                                                <h6 class="profile-meta__title">
                                                    <?php echo ucwords($course['teacher_first_name'] . ' ' . $course['teacher_last_name']); ?>
                                                </h6>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
                <a href="<?php echo MyUtility::makeUrl('Courses'); ?>" class="text-button">
                    <?php echo Label::getLabel('LBL_SHOW_ALL_COURSES'); ?>
                    <span class="circle-arrow"></span>
                </a>
            </div>
        </div>
    </section>
<?php
}