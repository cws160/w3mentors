<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if ($topRatedTeachers) { ?>
    <section class="section">
        <div class="container container--xxl">
            <div class="section__header" data-aos="fade-up" data-aos-duration="1000">
                <h2><?php echo Label::getLabel('LBL_HOME_TOP_RATED_TEACHERS_TITLE'); ?></h2>
            </div>
            <div class="section__body" data-aos="fade-up" data-aos-duration="1000">
                <div class="teacher-wrapper">
                    <div class="slider slider-onefifth slider-onefifth-js">
                        <?php foreach ($topRatedTeachers as $teacher) { ?>
                            <div class="slider__item">
                                <div class="tile-cover">
                                    <div class="tile">
                                        <div class="tile__head">
                                            <div class="tile__media ratio ratio--3by4">
                                                <a href="<?php echo MyUtility::makeUrl('Teachers', 'view', [$teacher['user_username']]); ?>">
                                                    <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['user_id'], Afile::SIZE_MEDIUM]), CONF_IMG_CACHE_TIME) ?>" alt="<?php echo $teacher['full_name']; ?>">
                                                </a>
                                            </div>
                                            <div class="rating">
                                                <svg class="rating__media">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#rating"></use>
                                                </svg>
                                                <span class="rating__value">
                                                    <?php echo $teacher['testat_ratings']; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="tile__body">
                                            <a href="<?php echo MyUtility::makeUrl('Teachers', 'view', [$teacher['user_username']]); ?>" class="tile__action-btn"><?php echo Label::getLabel('LBL_VIEW_DETAIL'); ?></a>
                                            <a class="tile__title" href="<?php echo MyUtility::makeUrl('Teachers', 'view', [$teacher['user_username']]); ?>">
                                                <h4><?php echo $teacher['full_name']; ?></h4>
                                            </a>
                                            <div class="card-element justify-content-center">
                                                <span class="card-element__item">
                                                    <?php echo $teacher['testat_students']; ?> <?php echo Label::getLabel('LBL_STUDENTS'); ?>
                                                </span>
                                                <span class="card-element__item">
                                                    <?php echo $teacher['testat_lessons'] + $teacher['testat_classes']; ?> <?php echo Label::getLabel('LBL_SESSIONS'); ?>
                                                </span>
                                                <?php if ($isCourseAvailable) { ?>
                                                    <span class="card-element__item">
                                                        <?php echo $teacher['courses']; ?> <?php echo Label::getLabel('LBL_COURSES'); ?>
                                                    </span>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
                <a href="<?php echo MyUtility::makeUrl('Teachers'); ?>" class="text-button">
                    <?php echo Label::getLabel('LBL_EXPLORE_ALL_TUTORS'); ?>
                    <span class="circle-arrow"></span>
                </a>
            </div>
        </div>
    </section>
<?php }
