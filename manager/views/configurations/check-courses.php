<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card">
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title"><?php echo Label::getLabel('LBL_CONFIRMATION'); ?></h3>
        </div>
    </div>
    <div class="card-body">
        <?php if ((isset($stats['teachers']) && isset($stats['courses'])) || ($stats['orders'] > 0 && $stats['amount'] > 0)) { ?>
            <h6><?php echo Label::getLabel('LBL_CONFIRM_COURSE_DEACTIVATION'); ?></h6>

            <div class="row mt-4">
                <?php if (isset($stats['teachers']) && isset($stats['courses'])) { ?>
                    <div class="col-md-6">
                        <div class="stats">
                            <div class="stats__content">
                                <h6 class="text-uppercase"> <?php echo Label::getLabel('LBL_COURSES'); ?></h6>
                                <h3 class="counter"><?php echo $stats['courses']; ?></h3>
                                <p class="mb-0"><strong><?php echo $stats['teachers']; ?></strong> <?php echo Label::getLabel('LBL_TEACHERS'); ?></p>
                                <a class="stats__link" href="<?php echo MyUtility::makeUrl('Courses') ?>" target="_blank"></a>
                            </div>
                            <span class="stats__icon">
                                <img src="<?php echo CONF_WEBROOT_URL ?>images/stat-courses.svg" alt="">
                            </span>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($stats['orders'] > 0 && $stats['amount'] > 0) { ?>
                    <div class="col-md-6">
                        <div class="stats">
                            <div class="stats__content">
                                <h6 class="text-uppercase"><?php echo Label::getLabel('LBL_COURSE_ORDERS'); ?></h6>
                                <h3 class="counter"><?php echo $stats['orders']; ?></h3>
                                <p class="mb-0"><strong><?php echo CourseUtility::formatMoney($stats['amount']); ?></strong> <?php echo Label::getLabel('LBL_NET_AMOUNT'); ?></p>
                                <a class="stats__link" href="<?php echo MyUtility::makeUrl('CourseOrders') ?>" target="_blank"></a>
                            </div>
                            <span class="stats__icon">
                                <img src="<?php echo CONF_WEBROOT_URL ?>images/total-lessons.svg" alt="">
                            </span>
                        </div>
                    </div>
                <?php } ?>
            </div>


            <div class="confirm-action pt-3">
                <a class="btn btn-primary" onclick="disableCourses();">
                    <?php echo Label::getLabel('LBL_PROCEED_WITH_DEACTIVATION'); ?>
                </a>
                <a class="btn btn-primary btn-outline-brand ms-4" onclick="contactTeam();">
                    <?php echo Label::getLabel('LBL_CONNECT_WITH_TECH_TEAM'); ?>
                </a>
            </div>


        <?php } else { ?>
            <h3><?php echo Label::getLabel('LBL_ARE_YOU_SURE_YOU_WANT_TO_DEACTIVATE_COURSES?'); ?></h3>
            <div class="row">
                <div class="col-md-12">&nbsp;</div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-primary" onclick="disableCourses();">
                        <?php echo Label::getLabel('LBL_PROCEED_WITH_DEACTIVATION'); ?>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>