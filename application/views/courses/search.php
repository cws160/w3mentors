<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="course-results">
    <?php
    if (count($courses)) {
        foreach ($courses as $course) { ?>
            <!-- [ COURSE CARD ========= -->
            <div class="course-card">
                <div class="course-grid">
                    <div class="course-grid__head">
                        <div class="course-media ratio ratio--16by9">
                            <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_COURSE_IMAGE, $course['course_id'], 'LARGE', $siteLangId], CONF_WEBROOT_FRONT_URL) . '?=' . time(); ?>" alt="">
                            <?php if (!empty($course['course_preview_video'])) { ?>
                                <a href="javascript:void(0);" class="course-preview__action" onclick="showPreviewVideo('<?php echo $course['course_id']; ?>');">
                                    <span></span>
                                </a>
                            <?php } ?>
                        </div>
                        <button onclick="toggleCourseFavorite('<?php echo $course['course_id'] ?>', this)" data-status="<?php echo $course['is_favorite']; ?>" class="mark-option <?php echo ($course['is_favorite'] == AppConstant::YES) ? 'is-active' : ''; ?>">
                            <svg class="fav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25.32 25.32">
                                <g>
                                    <path class="cls-1" d="M17.16,3.41c3.04,0,5.5,2.5,5.5,6,0,7-7.5,11-10,12.5-2.5-1.5-10-5.5-10-12.5,0-3.5,2.5-6,5.5-6,1.86,0,3.5,1,4.5,2,1-1,2.64-2,4.5-2Z" />
                                </g>
                            </svg>
                        </button>
                    </div>
                    <div class="course-grid__body">
                        <?php if ($course['course_certificate'] == AppConstant::YES) { ?>
                            <span class="course-tag">
                                <svg class="icon icon--award icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/sprite.svg#icon-course-certificate">
                                    </use>
                                </svg>
                                <span>
                                    <?php echo Label::getLabel('LBL_CERTIFICATE_ON_COMPLETION'); ?>
                                </span>
                            </span>
                        <?php } ?>
                        <div class="rating mb-2">
                            <svg class="rating__media">
                                <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/sprite.svg#rating"></use>
                            </svg>
                            <span class="rating__value"> <?php echo $course['course_ratings']; ?> </span>
                            <span class="rating__count"> <?php echo '(' . $course['course_reviews'] . ')' ?> </span>
                        </div>
                        <span class="course-card__label mb-1">
                            <a href="<?php echo MyUtility::generateUrl('Courses', 'index') . '?catg=' . $course['course_cate_id'] ?>"><?php echo $course['cate_name']; ?></a>
                            <?php
                            if (!empty($course['subcate_name'])) {
                                echo ' / '; ?>
                                <a href="<?php echo MyUtility::generateUrl('Courses', 'index') . '?catg=' . $course['course_subcate_id'] ?>"><?php echo $course['subcate_name']; ?></a>
                            <?php } ?>
                        </span>
                        <h4 class="course-card__title mb-2">
                            <a href="<?php echo MyUtility::makeUrl('Courses', 'view', [$course['course_slug']]); ?>" class="snakeline-hover">
                                <?php echo $course['course_title']; ?>
                            </a>
                        </h4>
                        <div class="course-stats mb-3">
                            <div class="course-stats__item">
                                <span> <?php echo Label::getLabel('LBL_LEVEL:') ?> <strong><?php echo $levels[$course['course_level']]; ?></strong> </span>
                            </div>
                            <div class="course-stats__item">
                                <span> <?php echo Label::getLabel('LBL_LECTURES'); ?>: <strong><?php echo $course['course_lectures']; ?></strong> </span>
                            </div>
                            <div class="course-stats__item">
                                <span> <?php echo Label::getLabel('LBL_TIME') ?>:
                                    <strong><?php echo CommonHelper::convertDuration($course['course_duration']); ?></strong>
                                </span>
                            </div>
                            <div class="course-stats__item">
                                <span>
                                    <?php echo Label::getLabel('LBL_Students'); ?>:
                                    <strong><?php echo $course['course_students']; ?></strong>
                                </span>
                            </div>
                        </div>
                        <?php if (!$course['is_purchased']) { ?>
                            <?php if ($course['course_type'] == Course::TYPE_FREE) { ?>
                                <h4 class="bold-700 color-red">
                                    <?php echo Label::getLabel('LBL_FREE'); ?>
                                </h4>
                            <?php } else { ?>
                                <h4 class="bold-700">
                                    <?php echo CourseUtility::formatMoney($course['course_price']); ?>
                                </h4>
                            <?php } ?>
                        <?php } ?>
                        <!-- <p class="course-card__subtitle">
                            <?php echo $course['course_subtitle']; ?>
                        </p> -->
                        <div class="course-actions border-top pt-4 mt-4">
                            <div class="course-actions__grid course-actions__grid-left">
                                <?php
                                $url = 'javascript:void(0);';
                                if ($course['is_profile_complete'] == true) {
                                    $url = MyUtility::makeUrl('teachers', 'view', [$course['teacher_username']]);
                                }
                                ?>
                                <a href="<?php echo $url; ?>" class="profile-meta d-flex align-items-center gap-3">
                                    <div class="profile-meta__media">
                                        <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($course['teacher_first_name']); ?>">
                                            <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $course['teacher_id'], Afile::SIZE_MEDIUM]), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $course['teacher_first_name'] . ' ' . $course['teacher_last_name']; ?>">
                                        </span>
                                    </div>
                                    <div class="profile-meta__details">
                                        <span class="color-black">
                                            <?php echo ucwords($course['teacher_first_name'] . ' ' . $course['teacher_last_name']); ?>
                                        </span>
                                    </div>
                                </a>
                            </div>
                            <div class="course-actions__grid course-actions__grid-right">
                                <div class="course-controls">
                                    <div class="course-controls__item">
                                        <?php if (!$course['is_purchased']) { ?>
                                            <?php if ($course['course_type'] == Course::TYPE_FREE) { ?>
                                                <button onclick="cart.addFreeCourse('<?php echo $course['course_id'] ?>');" class="btn btn--small btn--primary">
                                                    <?php echo Label::getLabel('LBL_ENROLL_NOW'); ?>
                                                </button>
                                            <?php } else { ?>
                                                <button onclick="cart.addCourse('<?php echo $course['course_id'] ?>');" class="btn btn--small btn--primary">
                                                    <?php echo Label::getLabel('LBL_ENROLL_NOW'); ?>
                                                </button>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <a href="<?php echo MyUtility::makeUrl('Tutorials', 'start', [$course['ordcrs_id']], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--small btn--primary">
                                                <?php echo Label::getLabel('LBL_GO_TO_COURSE'); ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                    <div class="course-controls__item">
                                        <a href="<?php echo MyUtility::makeUrl('Courses', 'view', [$course['course_slug']]); ?>" class="btn btn--primary-bordered btn--small"><?php echo Label::getLabel('LBL_VIEW_DETAILS'); ?></a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ] -->
        <?php } ?>
    <?php } else { ?>
        <div class="page-listing__body">
            <div class="box -padding-30" style="margin-bottom: 30px;">
                <div class="message-display">
                    <div class="message-display__icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 408">
                            <path d="M488.468,408H23.532A23.565,23.565,0,0,1,0,384.455v-16.04a15.537,15.537,0,0,1,15.517-15.524h8.532V31.566A31.592,31.592,0,0,1,55.6,0H456.4a31.592,31.592,0,0,1,31.548,31.565V352.89h8.532A15.539,15.539,0,0,1,512,368.415v16.04A23.565,23.565,0,0,1,488.468,408ZM472.952,31.566A16.571,16.571,0,0,0,456.4,15.008H55.6A16.571,16.571,0,0,0,39.049,31.566V352.891h433.9V31.566ZM497,368.415a0.517,0.517,0,0,0-.517-0.517H287.524c0.012,0.172.026,0.343,0.026,0.517a7.5,7.5,0,0,1-7.5,7.5h-48.1a7.5,7.5,0,0,1-7.5-7.5c0-.175.014-0.346,0.026-0.517H15.517a0.517,0.517,0,0,0-.517.517v16.04a8.543,8.543,0,0,0,8.532,8.537H488.468A8.543,8.543,0,0,0,497,384.455h0v-16.04ZM63.613,32.081H448.387a7.5,7.5,0,0,1,0,15.008H63.613A7.5,7.5,0,0,1,63.613,32.081ZM305.938,216.138l43.334,43.331a16.121,16.121,0,0,1-22.8,22.8l-43.335-43.318a16.186,16.186,0,0,1-4.359-8.086,76.3,76.3,0,1,1,19.079-19.071A16,16,0,0,1,305.938,216.138Zm-30.4-88.16a56.971,56.971,0,1,0,0,80.565A57.044,57.044,0,0,0,275.535,127.978ZM63.613,320.81H448.387a7.5,7.5,0,0,1,0,15.007H63.613A7.5,7.5,0,0,1,63.613,320.81Z"></path>
                        </svg>
                    </div>
                    <h5><?php echo Label::getLabel('LBL_NO_COURSE_FOUND!'); ?></h5>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<div class="pagination pagination--centered mt-5">
    <?php
    echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
    $pagingArr = ['page' => $post['pageno'], 'pageCount' => $pageCount, 'recordCount' => $recordCount, 'callBackJsFunc' => 'gotoPage'];
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
    ?>
</div>
<?php
$checkoutForm->setFormTagAttribute('class', 'd-none');
$checkoutForm->setFormTagAttribute('name', 'frmCheckout');
$checkoutForm->setFormTagAttribute('id', 'frmCheckout');
echo $checkoutForm->getFormHtml();
?>
<script>
    var _body = $('body');
    var _toggle = $('.js-filter-toggle');
    _toggle.each(function() {
        var _this = $(this),
            _target = $(_this.attr('href'));

        _this.on('click', function(e) {
            e.preventDefault();
            _target.toggleClass('is-filter-visible');
            _this.toggleClass('is-active');
            _body.toggleClass('is-filter-show');
        });
    });

    COURSE_COUNT = '<?php echo $recordCount; ?>';
    COUNT_LABEL_TXT = '<?php echo Label::getLabel('LBL_FOUND_THE_BEST_{recordcount}_ONLINE_COURSES_FOR_YOU'); ?>';
    COUNT_LABEL_TXT = COUNT_LABEL_TXT.replace('{recordcount}', COURSE_COUNT);
    $('.record-count-header').text(COUNT_LABEL_TXT);
</script>