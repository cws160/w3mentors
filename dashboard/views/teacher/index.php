<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php

$class = "col-lg-12 col-md-12 col-sm-12";
$isGrpClsEnabled = GroupClass::isEnabled();
$isCourseEnabled = Course::isEnabled();
if ($isCourseEnabled && $isGrpClsEnabled) {
    $class = "col-lg-4 col-md-6 col-sm-6";
}
if ($isCourseEnabled && !$isGrpClsEnabled) {
    $class = "col-lg-6 col-md-6 col-sm-6";
}
if (!$isCourseEnabled && $isGrpClsEnabled) {
    $class = "col-lg-6 col-md-6 col-sm-6";
}
?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="dashboard">
    <div class="dashboard__primary">
        <div class="page__head">
            <h1><?php echo Label::getLabel('LBL_DASHBOARD') ?></h1>
        </div>
        <div class="page__body">
            <?php if (!$siteUser['profile_progress']['isProfileCompleted']) { ?>
                <!-- [ INFO BAR ========= -->
                <div class="infobar infobar--primary">
                    <div class="row justify-content-between align-items-center position-relative">
                        <div class="col-sm-8 col-lg-6 col-xl-8">
                            <div class="d-flex">
                                <div class="infobar__media me-4">
                                    <div class="infobar__media-icon infobar__media-icon--alert is-profile-complete-js">!</div>
                                </div>
                                <div class="infobar__content">
                                    <h6 class="mb-1"><?php echo str_replace('{user-first-name}', $siteUser['user_first_name'], Label::getLabel('LBL_TEACHER_DASHBOARD_HEADING_{user-first-name}')); ?></h6>
                                    <p class="m-0"><?php echo Label::getLabel('LBL_TEACHER_DASHBOARD_INFO_TEXT'); ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-4 col-lg-3  col-xl-4">
                            <div class="-align-right">
                                <a href="<?php echo MyUtility::makeUrl('Account', 'ProfileInfo'); ?>" class="btn bg-secondary"><?php echo Label::getLabel('LBL_COMPLETE_PROFILE') ?></a>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- ] -->
            <?php } ?>

            <?php if ($zoomVerificationRequired) {
                $this->includeTemplate('_partial/zoom-verification-bar.php', ['zoomVerificationStatus' => $zoomVerificationStatus], false);
            } ?>


            <div class="stats-row align-item-stretch">
                <div class="row align-items-center g-4 mb-4">
                    <div class="<?php echo $class; ?>">
                        <div class="stat">
                            <div class="stat__amount">
                                <span><?php echo Label::getLabel('LBL_SCHEDULED_LESSONS'); ?></span>
                                <h5><?php echo $schLessonCount; ?></h5>
                            </div>
                            <div class="stat__media bg-secondary">
                                <svg class="icon icon--money icon--40 color-white">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#stats_1'; ?>"></use>
                                </svg>
                            </div>
                            <a href="<?php echo MyUtility::makeUrl('Lessons') . '?ordles_status=' . Lesson::SCHEDULED; ?>" class="stat__action"></a>
                        </div>
                    </div>
                    <?php if ($isGrpClsEnabled) { ?>
                        <div class="<?php echo $class; ?>">
                            <div class="stat">
                                <div class="stat__amount">
                                    <span><?php echo Label::getLabel('LBL_SCHEDULED_CLASSES'); ?></span>
                                    <h5><?php echo $schClassCount; ?></h5>
                                </div>
                                <div class="stat__media bg-secondary">
                                    <svg class="icon icon--money icon--40 color-white">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#stats_1'; ?>"></use>
                                    </svg>
                                </div>
                                <a href="<?php echo MyUtility::makeUrl('Classes') . '?grpcls_status=' . GroupClass::SCHEDULED; ?>" class="stat__action"></a>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if ($isCourseEnabled) { ?>
                        <div class="<?php echo $class; ?>">
                            <div class="stat">
                                <div class="stat__amount">
                                    <span><?php echo Label::getLabel('LBL_COURSES_SOLD'); ?></span>
                                    <h5><?php echo $courseCount; ?></h5>
                                </div>
                                <div class="stat__media bg-secondary">
                                    <svg class="icon icon--money icon--40 color-white">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#stats_1'; ?>"></use>
                                    </svg>
                                </div>
                                <a href="<?php echo MyUtility::makeUrl('Courses'); ?>" class="stat__action"></a>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="col-lg-6 col-md-6 col-sm-6">
                        <div class="stat">
                            <div class="stat__amount">
                                <span><?php echo Label::getLabel('LBL_TOTAL_EARNINGS'); ?></span>
                                <h5><?php echo MyUtility::formatMoney($earnings); ?></h5>
                            </div>
                            <div class="stat__media bg-yellow">
                                <svg class="icon icon--money icon--40 color-white">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#stats'; ?>"></use>
                                </svg>
                            </div>
                            <a href="javascript:void(0);" class="stat__action"></a>
                        </div>
                    </div>
                    <div class="col-lg-6 col-md-6 col-sm-6">
                        <div class="stat">
                            <div class="stat__amount">
                                <span> <?php echo Label::getLabel('LBL_WALLET_BALANCE'); ?></span>
                                <h5><?php echo MyUtility::formatMoney($walletBalance); ?></h5>
                            </div>
                            <div class="stat__media bg-primary">
                                <svg class="icon icon--money icon--40 color-white">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#stats_2'; ?>"></use>
                                </svg>
                            </div>
                            <a href="<?php echo MyUtility::makeUrl('Wallet'); ?>" class="stat__action"></a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="page-panel">
                <div class="page-panel__head border-bottom-0 pb-0">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h4><?php echo Label::getLabel('LBL_SALE_STATISTICS'); ?></h4>
                        </div>
                        <div class="col-6">
                            <div class="sale-stat__select">
                                <div class="form-inline__item">
                                    <select onchange="getStatisticalData(this.value);" name="duration_type">
                                        <?php foreach ($durationType as $key => $value) { ?>
                                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-panel__body">
                    <div class="row">
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="sale-stat sale-stat--primary sale-stat--yellow">
                                <div class="sale-stat__count">
                                    <span><?php echo Label::getLabel('LBL_EARNINGS'); ?></span>
                                    <h5 class="earing-amount-js"></h5>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-6">
                            <div class="sale-stat sale-stat--secondary sale-stat--sky">
                                <div class="sale-stat__count">
                                    <span><?php echo Label::getLabel('LBL_SESSION_SOLD'); ?></span>
                                    <h5 class="session-sold-count-js"></h5>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="graph-media" id="chart_div">
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="dashboard__secondary">
        <div class="status-bar">
            <div class="status-bar__head">
                <div class="status-title">
                    <h5><?php echo Label::getLabel('LBL_UPCOMING_LESSONS'); ?></h5>
                    <a href="<?php echo MyUtility::makeUrl('Lessons') . '?ordles_status=' . Lesson::SCHEDULED; ?>" class="color-secondary underline pt-3 pb-3"><?php echo Label::getLabel('LBL_View_All'); ?></a>
                </div>
                <div class="calendar">
                    <div id='d_calendar' class="dashboard-calendar calendar-view"></div>
                </div>
            </div>
            <div class="status-bar__body">
                <div class="listing-window" id="listItemsLessons">
                </div>
            </div>
        </div>
    </div>
</div>
</main>
<!-- ] -->
<script type="text/javascript" src="//www.gstatic.com/charts/loader.js"></script>
<script>
    $(document).ready(function() {
        getStatisticalData('<?php echo MyDate::TYPE_TODAY ?>');
        upcomingLesson('view=<?php echo AppConstant::VIEW_SHORT; ?>&pagesize=<?php echo AppConstant::PAGESIZE; ?>');
    });
    moreLinkTextLabel = '<?php echo Label::getLabel('LBL_VIEW_MORE'); ?>';
    var fecal = new FatEventCalendar(0, '<?php echo MyDate::getOffset($siteTimezone); ?>');
    fecal.TeacherDashboardCalendar('<?php echo MyDate::formatDate(date('Y-m-d H:i:s')); ?>', '<?php echo $siteUserId; ?>', '<?php echo Lesson::SCHEDULED ?>');
</script>