<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<script src="https://www.google.com/jsapi"></script>
<!--main panel start here-->
<?php if ($canView) { ?>
    <?php $durationType = MyDate::getDurationTypesArr(); ?>
    <?php $isCourseEnabled = Course::isEnabled();
    $isSubsEnabled = SubscriptionPlan::isEnabled(); ?>
    <?php $isGroupClassEnabled = GroupClass::isEnabled(); ?>
    <?php
    $class = ($isCourseEnabled) ? 'col-lg-3 col-md-3 col-sm-3' : 'col-lg-4 col-md-4 col-sm-4';
    $count = 1;
    if (Course::isEnabled()) {
        $count = $count + 1;
    }
    if (GroupClass::isEnabled()) {
        $count = $count + 1;
    }
    $viewIndex = $count;
    ?>
    <main class="main is-dashboard">
        <div class="container container-fluid">
            <div class="grid-panel">
                <div class="grid-panel__item">
                    <div class="stats-grid" data-view="3">
                        <div class="stats-grid__item">
                            <div class="stats stats-bg-1">
                                <span class="stats__icon">
                                    <img src="<?php echo CONF_WEBROOT_URL ?>images/lesson-revenue.svg" alt="">
                                </span>
                                <div class="stats__content">
                                    <h6><?php echo Label::getLabel('LBL_LESSONS_REVENUE'); ?></h6>
                                    <h3 class="counter" data-currency="1"><?php echo MyUtility::formatMoney($stats['ALL_LESSONS_REVENUE'] ?? 0); ?></h3>
                                    <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo MyUtility::formatMoney($stats['TM_LESSONS_REVENUE'] ?? 0); ?></strong></p>
                                    <?php if ($objPrivilege->canViewLessonsOrders(true)) { ?>
                                        <a href="<?php echo MyUtility::makeUrl('Lessons') . '?order_payment_status=' . Order::ISPAID; ?>" class="stats__link"></a>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-head">
                                    <div class="card-head-label">
                                        <h2 class="card-head-caption large"><?php echo Label::getLabel("LBL_LESSONS_OVERVIEW"); ?></h2>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="stats-overview stats-overview--primary">
                                        <h6><?php echo Label::getLabel('LBL_TOTAL_LESSONS'); ?></h6>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h3 class="counter" data-currency="0"><?php echo $stats['ALL_LESSONS_TOTAL'] ?? 0; ?></h3>
                                            <?php if ($objPrivilege->canViewLessonsOrders(true)) { ?>
                                                <a href="<?php echo MyUtility::makeUrl('Lessons') . '?order_payment_status=' . Order::ISPAID; ?>" class="stats__link"></a>
                                            <?php } ?>
                                            <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_LESSONS_TOTAL'] ?? 0; ?></strong></p>
                                        </div>
                                    </div>
                                    <div class="stats-overview stats-overview--primary">
                                        <h6><?php echo Label::getLabel('LBL_COMPLETED_LESSONS'); ?></h6>
                                        <div class="d-flex align-items-center justify-content-between">
                                            <h3 class="counter" data-currency="0"><?php echo $stats['ALL_COMPLETED_LESSONS'] ?? 0; ?></h3>
                                            <?php if ($objPrivilege->canViewLessonsOrders(true)) { ?>
                                                <a href="<?php echo MyUtility::makeUrl('Lessons') . '?order_payment_status=' . Order::ISPAID . '&ordles_status=' . Lesson::COMPLETED; ?>" class="stats__link"></a>
                                            <?php } ?>
                                            <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_COMPLETED_LESSONS'] ?? 0; ?></strong></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($isGroupClassEnabled) { ?>
                            <div class="stats-grid__item">
                                <div class="stats stats-bg-2">
                                    <span class="stats__icon">
                                        <img src="<?php echo CONF_WEBROOT_URL ?>images/class-revenue.svg" alt="">
                                    </span>
                                    <div class="stats__content">
                                        <h6><?php echo Label::getLabel('LBL_CLASSES_REVENUE'); ?></h6>
                                        <h3 class="counter" data-currency="1"><?php echo MyUtility::formatMoney($stats['ALL_CLASSES_REVENUE'] ?? 0); ?></h3>
                                        <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo MyUtility::formatMoney($stats['TM_CLASSES_REVENUE'] ?? 0); ?></strong></p>
                                        <?php if ($objPrivilege->canViewClassesOrders(true)) { ?>
                                            <a href="<?php echo MyUtility::makeUrl('Classes') . '?order_payment_status=' . Order::ISPAID; ?>" class="stats__link"></a>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-head">
                                        <div class="card-head-label">
                                            <h2 class="card-head-caption large"><?php echo Label::getLabel("LBL_CLASSES_OVERVIEW"); ?></h2>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="stats-overview stats-overview--secondary">
                                            <h6><?php echo Label::getLabel('LBL_TOTAL_CLASSES'); ?></h6>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h3 class="counter" data-currency="0"><?php echo $stats['ALL_CLASSES_TOTAL'] ?? 0; ?></h3>
                                                <?php if ($objPrivilege->canViewGroupClasses(true)) { ?>
                                                    <a href="<?php echo MyUtility::makeUrl('GroupClasses'); ?>" class="stats__link"></a>
                                                <?php } ?>
                                                <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_CLASSES_TOTAL'] ?? 0; ?></strong></p>
                                            </div>
                                        </div>
                                        <div class="stats-overview stats-overview--secondary">
                                            <h6><?php echo Label::getLabel('LBL_PURCHASED_CLASSES'); ?></h6>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h3 class="counter" data-currency="0"><?php echo $stats['ALL_COMPLETED_CLASSES'] ?? 0; ?></h3>
                                                <?php if ($objPrivilege->canViewClassesOrders(true)) { ?>
                                                    <a href="<?php echo MyUtility::makeUrl('Classes') . '?order_payment_status=' . Order::ISPAID; ?>" class="stats__link"></a>
                                                <?php } ?>
                                                <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_COMPLETED_CLASSES'] ?? 0; ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($isSubsEnabled) { ?>
                            <div class="stats-grid__item">
                                <div class="stats stats-bg-3">
                                    <span class="stats__icon">
                                        <img src="<?php echo CONF_WEBROOT_URL ?>images/class-revenue.svg" alt="">
                                    </span>
                                    <div class="stats__content">
                                        <h6><?php echo Label::getLabel('LBL_SUBSCRIPTION_REVENUE'); ?></h6>
                                        <h3 class="counter" data-currency="1"><?php echo MyUtility::formatMoney($stats['ALL_SUBSCRIPTION_REVENUE'] ?? 0); ?></h3>
                                        <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo MyUtility::formatMoney($stats['TM_SUBSCRIPTION_REVENUE'] ?? 0); ?></strong></p>
                                        <?php if ($objPrivilege->canViewSubscriptionPlanOrders(true)) { ?>
                                            <a href="<?php echo MyUtility::makeUrl('OrderSubscriptionPlans') . '?order_payment_status=' . Order::ISPAID; ?>" class="stats__link"></a>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-head">
                                        <div class="card-head-label">
                                            <h2 class="card-head-caption large"><?php echo Label::getLabel("LBL_SUBSCRIPTIONS_OVERVIEW"); ?></h2>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="stats-overview stats-overview--secondary">
                                            <h6><?php echo Label::getLabel('LBL_PURCHASED_SUBSCRIPTIONS'); ?></h6>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h3 class="counter" data-currency="0"><?php echo $stats['ALL_SUBSCRIPTIONS_TOTAL'] ?? 0; ?></h3>
                                                <?php if ($objPrivilege->canViewSubscriptionPlanOrders(true)) { ?>
                                                    <a href="<?php echo MyUtility::makeUrl('OrderSubscriptionPlans') . '?order_payment_status=' . Order::ISPAID; ?>" class="stats__link"></a>
                                                <?php } ?>
                                                <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_SUBSCRIPTIONS_TOTAL'] ?? 0; ?></strong></p>
                                            </div>
                                        </div>
                                        <div class="stats-overview stats-overview--secondary">
                                            <h6><?php echo Label::getLabel('LBL_COMPLETED_SUBSCRIPTIONS'); ?></h6>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h3 class="counter" data-currency="0"><?php echo $stats['ALL_COMPLETED_SUBSCRIPTIONS'] ?? 0; ?></h3>
                                                <?php if ($objPrivilege->canViewSubscriptionPlanOrders(true)) { ?>
                                                    <a href="<?php echo MyUtility::makeUrl('OrderSubscriptionPlans') . '?ordsplan_status=' . OrderSubscriptionPlan::COMPLETED; ?>" class="stats__link"></a>
                                                <?php } ?>
                                                <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_COMPLETED_SUBSCRIPTIONS'] ?? 0; ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                        <?php if ($isCourseEnabled) { ?>
                            <div class="stats-grid__item">
                                <div class="stats stats-bg-1">
                                    <span class="stats__icon">
                                        <img src="<?php echo CONF_WEBROOT_URL ?>images/courses-revenue.svg" alt="">
                                    </span>
                                    <div class="stats__content">
                                        <h6><?php echo Label::getLabel('LBL_COURSES_REVENUE'); ?></h6>
                                        <h3 class="counter" data-currency="1"><?php echo MyUtility::formatMoney($stats['ALL_COURSES_REVENUE'] ?? 0); ?></h3>
                                        <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo MyUtility::formatMoney($stats['TM_COURSES_REVENUE'] ?? 0); ?></strong></p>
                                        <?php if ($objPrivilege->canViewCoursesOrders(true)) { ?>
                                            <a href="<?php echo MyUtility::makeUrl('CourseOrders') . '?order_payment_status=' . Order::ISPAID; ?>" class="stats__link"></a>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-head">
                                        <div class="card-head-label">
                                            <h2 class="card-head-caption large"><?php echo Label::getLabel("LBL_COURSES_OVERVIEW"); ?></h2>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0">
                                        <div class="stats-overview stats-overview--third">
                                            <h6><?php echo Label::getLabel('LBL_TOTAL_COURSES'); ?></h6>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h3 class="counter" data-currency="0"><?php echo $stats['ALL_COURSES_TOTAL'] ?? 0; ?></h3>
                                                <?php if ($objPrivilege->canViewCourses(true)) { ?>
                                                    <a href="<?php echo MyUtility::makeUrl('Courses'); ?>" class="stats__link"></a>
                                                <?php } ?>
                                                <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_COURSES_TOTAL'] ?? 0; ?></strong></p>
                                            </div>
                                        </div>
                                        <div class="stats-overview stats-overview--third">
                                            <h6><?php echo Label::getLabel('LBL_REFUNDED_COURSES'); ?></h6>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <h3 class="counter" data-currency="0"><?php echo $stats['ALL_REFUNDED_COURSES'] ?? 0; ?></h3>
                                                <?php if ($objPrivilege->canViewCourseRefundRequests(true)) { ?>
                                                    <a href="<?php echo MyUtility::makeUrl('CourseRefundRequests') . '?corere_status=' . Course::REFUND_APPROVED; ?>" class="stats__link"></a>
                                                <?php } ?>
                                                <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_REFUNDED_COURSES'] ?? 0; ?></strong></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="grid-panel__item">
                    <div class="stats stats--total-earning">
                        <div class="stats__content">
                            <h6><?php echo Label::getLabel('LBL_ADMIN_EARNINGS'); ?></h6>
                            <h3 class="counter" data-currency="1"><?php echo MyUtility::formatMoney($stats['ALL_ADMIN_EARNINGS'] ?? 0); ?></h3>
                            <p class="mb-2"><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo MyUtility::formatMoney($stats['TM_ADMIN_EARNINGS'] ?? 0); ?></strong></p>
                            <?php if ($objPrivilege->canViewAdminEarningsReport(true)) { ?>
                                <a href="<?php echo MyUtility::makeUrl('AdminEarnings'); ?>" class="btn btn-orange"> <?php echo Label::getLabel('LBL_VIEW_EARNING_REPORTS'); ?> </a>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="total-stats">
                        <div class="stats stats-bg-2">
                            <span class="stats__icon">
                                <img src="<?php echo CONF_WEBROOT_URL ?>images/total-orders.svg" alt="">
                            </span>
                            <div class="stats__content">
                                <h6><?php echo Label::getLabel('LBL_TOTAL_ORDERS'); ?></h6>
                                <h3 class="counter" data-currency="0"><?php echo $stats['ALL_ORDERS_TOTAL'] ?? 0; ?></h3>
                                <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_ORDERS_TOTAL'] ?? 0; ?></strong></p>
                                <?php if ($objPrivilege->canViewOrders(true)) { ?>
                                    <a href="<?php echo MyUtility::makeUrl('orders'); ?>" class="stats__link"></a>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="stats stats-bg-1">
                            <span class="stats__icon">
                                <img src="<?php echo CONF_WEBROOT_URL ?>images/total-users.svg" alt="">
                            </span>
                            <div class="stats__content">
                                <h6><?php echo Label::getLabel('LBL_TOTAL_USERS'); ?></h6>
                                <h3 class="counter" data-currency="0"><?php echo $stats['ALL_USERS_TOTAL'] ?? 0; ?></h3>
                                <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_USERS_TOTAL'] ?? 0; ?></strong></p>
                                <?php if ($objPrivilege->canViewUsers(true)) { ?>
                                    <a href="<?php echo MyUtility::makeUrl('Users'); ?>" class="stats__link"></a>
                                <?php } ?>
                            </div>
                        </div>
                        <?php if (User::isAffiliateEnabled()) { ?>
                            <div class="stats stats-bg-1">
                                <span class="stats__icon">
                                    <img src="<?php echo CONF_WEBROOT_URL ?>images/total-users.svg" alt="">
                                </span>
                                <div class="stats__content">
                                    <h6><?php echo Label::getLabel('LBL_TOTAL_AFFILIATES'); ?></h6>
                                    <h3 class="counter" data-currency="0"><?php echo $stats['ALL_AFFILIATES_TOTAL'] ?? 0; ?></h3>
                                    <p><?php echo Label::getLabel('LBL_THIS_MONTH'); ?> <strong><?php echo $stats['TM_AFFILIATES_TOTAL'] ?? 0; ?></strong></p>
                                    <?php if ($objPrivilege->canViewUsers(true)) { ?>
                                        <a href="<?php echo MyUtility::makeUrl('Users') . '?type=' . User::AFFILIATE; ?>" class="stats__link"></a>
                                    <?php } ?>

                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="grid-panel__item">
                    <div class="card height-100">
                        <div class="card-head d-md-flex">
                            <div class="card-head-label">
                                <h3 class="card-head-caption"><?php echo Label::getLabel('LBL_STATISTICS'); ?></h3>
                            </div>
                            <div class="card-head-toolbar">
                                <ul class="nav nav--button statistics-nav-js">
                                    <li><a class="active" rel="tabs_1" data-chart="true" href="javascript:void(0)"><?php echo Label::getLabel('LBL_LESSONS_COMMISSION'); ?></a></li>
                                    <?php if ($isGroupClassEnabled) { ?>
                                        <li><a rel="tabs_2" data-chart="true" href="javascript:void(0)"><?php echo Label::getLabel('LBL_CLASSES_COMMISSION'); ?></a></li>
                                    <?php } ?>
                                    <?php if ($isCourseEnabled) { ?>
                                        <li><a rel="tabs_3" data-chart="true" href="javascript:void(0)"><?php echo Label::getLabel('LBL_COURSES_COMMISSION'); ?></a></li>
                                    <?php } ?>
                                    <li><a rel="tabs_4" data-chart="true" href="javascript:void(0)"><?php echo Label::getLabel('LBL_SIGN_UPS'); ?></a></li>
                                </ul>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="tabs-wrap statistics-tab-js height-100">
                                <div id="tabs_1" class="tabs_panel" style="width:100%;height:100%">
                                    <div id="lessonEarning--js" class="ct-chart ct-perfect-fourth graph--sales"></div>
                                </div>
                                <?php if ($isGroupClassEnabled) { ?>
                                    <div id="tabs_2" class="tabs_panel" style="width:100%;height:100%">
                                        <div id="classEarning--js" class="ct-chart ct-perfect-fourth graph--sales"></div>
                                    </div>
                                <?php } ?>
                                <?php if ($isCourseEnabled) { ?>
                                    <div id="tabs_3" class="tabs_panel" style="width:100%;height:100%">
                                        <div id="courseEarning--js" class="ct-chart ct-perfect-fourth graph--sales"></div>
                                    </div>
                                <?php } ?>
                                <div id="tabs_4" class="tabs_panel" style="width:100%;height:100%">
                                    <div id="userSignups--js" class="ct-chart ct-perfect-fourth graph--sales"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="d-grid mt-4 pt-1 pt-lg-0 mt-lg-0" data-view="<?php echo $viewIndex; ?>">
                <div class="card card-height">
                    <div class="card-head">
                        <div class="card-head-label">
                            <h3 class="card-head-caption"><?php echo Label::getLabel('LBL_TOP_LESSON_LANGUAGES'); ?></h3>
                        </div>
                        <div class="card-head-toolbar">
                            <div class="dropdown">
                                <button type="button" class="btn btn-select dropdown-toggle dropdownBtnJs languageDurationType-js2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <?php echo $durationType[MyDate::TYPE_ALL]; ?> </button>
                                <div class="nav nav-tabs navTabsJs dropdown-menu dropdown-menu-right dropdown-menu-anim" role="tablist" data-popper-placement="bottom-start">
                                    <div class="dropdown-menu-scroll">
                                        <ul>
                                            <?php
                                            foreach ($durationType as $key => $value) {
                                                $datetime = MyDate::getStartEndDate($key);
                                                $days = ($key == MyDate::TYPE_ALL) ? 0 : 1;
                                                $datetime['startDate'] = date('Y-m-d', strtotime($datetime['startDate']));
                                                $datetime['endDate'] = date('Y-m-d', strtotime($datetime['endDate'] . ' -' . $days . ' day'));
                                            ?>
                                                <li><a class="dropdown-item" href="javascript:void(0);" onClick="getTopLessonLanguage('<?php echo $key; ?>', '<?php echo $value; ?>')"><?php echo $value; ?> <span>( <?php echo MyDate::showDate($datetime['startDate']) . ' - ' . MyDate::showDate($datetime['endDate']) ?>)</span></a> </li>
                                            <?php } ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-table topLessonLanguage h-100"></div>
                </div>
                <?php if ($isGroupClassEnabled) { ?>
                    <div class="card card-height">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h2 class="card-head-caption"><?php echo Label::getLabel('LBL_TOP_CLASS_LANGUAGES') ?></h2>
                            </div>
                            <div class="card-head-toolbar">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-select dropdown-toggle dropdownBtnJs languageDurationType-js" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <?php echo $durationType[MyDate::TYPE_ALL]; ?> </button>
                                    <div class="nav nav-tabs navTabsJs dropdown-menu dropdown-menu-right dropdown-menu-anim" role="tablist" data-popper-placement="bottom-start">
                                        <div class="dropdown-menu-scroll">
                                            <ul>
                                                <?php
                                                foreach ($durationType as $key => $value) {
                                                    $datetime = MyDate::getStartEndDate($key);
                                                    $days = ($key == MyDate::TYPE_ALL) ? 0 : 1;
                                                    $datetime['startDate'] = date('Y-m-d', strtotime($datetime['startDate']));
                                                    $datetime['endDate'] = date('Y-m-d', strtotime($datetime['endDate'] . ' -' . $days . ' day'));
                                                ?>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" onClick="getTopClassLanguage('<?php echo $key; ?>', '<?php echo $value; ?>')"><?php echo $value; ?> <span>( <?php echo MyDate::showDate($datetime['startDate']) . ' - ' . MyDate::showDate($datetime['endDate']) ?>)</span></a> </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-table topClassLanguage h-100"></div>
                    </div>
                <?php } ?>
                <?php if ($isCourseEnabled) { ?>
                    <div class="card card-height">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h3 class="card-head-caption"><?php echo Label::getLabel('LBL_TOP_COURSE_CATEGORIES'); ?></h3>
                            </div>
                            <div class="card-head-toolbar">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-select dropdown-toggle dropdownBtnJs crsCatgDurationType-js2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <?php echo $durationType[MyDate::TYPE_ALL]; ?> </button>
                                    <div class="nav nav-tabs navTabsJs dropdown-menu dropdown-menu-right dropdown-menu-anim" role="tablist" data-popper-placement="bottom-start">
                                        <div class="dropdown-menu-scroll">
                                            <ul>
                                                <?php
                                                foreach ($durationType as $key => $value) {
                                                    $datetime = MyDate::getStartEndDate($key);
                                                    $days = ($key == MyDate::TYPE_ALL) ? 0 : 1;
                                                    $datetime['startDate'] = date('Y-m-d', strtotime($datetime['startDate']));
                                                    $datetime['endDate'] = date('Y-m-d', strtotime($datetime['endDate'] . ' -' . $days . ' day'));
                                                ?>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" onClick="getTopCourseCategories('<?php echo $key; ?>', '<?php echo $value; ?>')"><?php echo $value; ?> <span>( <?php echo MyDate::showDate($datetime['startDate']) . ' - ' . MyDate::showDate($datetime['endDate']) ?>)</span></a> </li>

                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-table topCourseCategories h-100"></div>
                    </div>
                <?php } ?>
            </div>
            <div class="gap"></div>
            <div class="d-grid" data-view="2">
                <div class="d-grid__item">
                    <div class="card card-height">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h3 class="card-head-caption"><?php echo Label::getLabel('LBL_ANALYTICS_EVENT_MEASUREMENTS'); ?></h3>
                            </div>
                            <div class="card-head-toolbar">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-select dropdown-toggle dropdownBtnJs eventDurationType-js" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <?php echo $durationType[MyDate::TYPE_ALL]; ?> </button>

                                    <div class="nav nav-tabs navTabsJs dropdown-menu dropdown-menu-right dropdown-menu-anim" role="tablist" data-popper-placement="bottom-start">
                                        <div class="dropdown-menu-scroll">
                                            <ul>
                                                <?php
                                                foreach ($durationType as $key => $value) {
                                                    if ($key == MyDate::TYPE_TODAY) {
                                                        continue;
                                                    }
                                                    $datetime = MyDate::getStartEndDate($key);
                                                    $days = ($key == MyDate::TYPE_ALL) ? 0 : 1;
                                                    $datetime['startDate'] = date('Y-m-d', strtotime($datetime['startDate']));
                                                    $datetime['endDate'] = date('Y-m-d', strtotime($datetime['endDate'] . ' -' . $days . ' day'));
                                                ?>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" onClick="getEventMeasurements('<?php echo $key; ?>', '<?php echo $value; ?>')"><?php echo $value; ?> <span>( <?php echo MyDate::showDate($datetime['startDate']) . ' - ' . MyDate::showDate($datetime['endDate']) ?>)</span></a> </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="analytics-box">
                                <div id="analytic-event-chart" class="analytic-event-chart w-100"></div>
                                <div class="graph-data-table scrollbar scrollbar-js analytics-event-measurements"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-grid__item">
                    <div class="card card-height">
                        <div class="card-head">
                            <div class="card-head-label">
                                <h3 class="card-head-caption"><?php echo Label::getLabel('LBL_ANALYTICS_TRAFFIC_ACQUITIONS'); ?></h3>
                            </div>
                            <div class="card-head-toolbar">
                                <div class="dropdown">
                                    <button type="button" class="btn btn-select dropdown-toggle dropdownBtnJs trafficDurationType-js" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="true"> <?php echo $durationType[MyDate::TYPE_ALL]; ?> </button>
                                    <div class="nav nav-tabs navTabsJs dropdown-menu dropdown-menu-right dropdown-menu-anim" role="tablist" data-popper-placement="bottom-start">
                                        <div class="dropdown-menu-scroll">
                                            <ul>
                                                <?php
                                                foreach ($durationType as $key => $value) {
                                                    if ($key == MyDate::TYPE_TODAY) {
                                                        continue;
                                                    }
                                                    $datetime = MyDate::getStartEndDate($key);
                                                    $days = ($key == MyDate::TYPE_ALL) ? 0 : 1;
                                                    $datetime['startDate'] = date('Y-m-d', strtotime($datetime['startDate']));
                                                    $datetime['endDate'] = date('Y-m-d', strtotime($datetime['endDate'] . ' -' . $days . ' day'));
                                                ?>
                                                    <li><a class="dropdown-item" href="javascript:void(0);" onClick="getTrafficAcquitions('<?php echo $key; ?>', '<?php echo $value; ?>')"><?php echo $value; ?> <span>( <?php echo MyDate::showDate($datetime['startDate']) . ' - ' . MyDate::showDate($datetime['endDate']) ?>)</span></a> </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="analytics-box">
                                <div id="analytic-traffic-chart" class="analytic-traffic-chart w-100"></div>
                                <div class="graph-data-table scrollbar scrollbar-js analytics-traffic--acquitions"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <script>
        var w = $('.tabs-wrap').width();
        google.load('visualization', '1', {
            'packages': ['corechart', 'bar']
        });
    </script>
    <style>
        .analytics-box {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 360px;
            width: 100%;
            flex-direction: column;
        }
    </style>
<?php } else { ?>
    <main class="main is-dashboard">
        <div class="container container-fluid">
            <div class="container container-fluid">
                <div class="col-lg-12 col-md-12 col-sm-12">
                    <div class="box--scroll box box--white box--height box--stats">
                        <div class="box__body">
                            <h4 class="-txt-bold">
                                <?php
                                $label = Label::getLabel('LBL_WELCOME_TO_THE_{sitename}');
                                $sitename = FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId);
                                echo str_replace('{sitename}', $sitename, $label);
                                ?>
                            </h4>
                            <ul class="actions right">
                                <li class="droplink">
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
<?php } ?>
<script>
    var canView = "<?php echo (int)$canView; ?>";
</script>