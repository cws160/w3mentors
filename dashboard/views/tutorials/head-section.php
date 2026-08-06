<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$websiteName = FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '');
?>
<!-- [ HEADER ========= -->
<header class="header">
    <div class="header-primary d-sm-flex justify-content-sm-between align-items-sm-center">
        <div class="header-primary__right order-sm-2">
            <div class="d-flex justify-content-between align-items-center">
                <!-- [ COURSE PROGRESS - NOT COMPLETED ========= -->
                <div class="course-progress <?php echo ($progress['crspro_progress'] < 100) ? 'in-progress' : 'is-completed' ?>">
                    <a href="#course-progress" class="course-progress__trigger d-flex align-items-center trigger-js">
                        <div class="course-progress__count me-1">
                            <div class="percent">
                                <svg class="percent__progress" viewBox="0 0 300 300">
                                    <circle cx="150" cy="150" r="100"></circle>
                                    <circle cx="150" cy="150" r="100" style="--percent: <?php echo $progress['crspro_progress'] ?>" id="progressBarJs"></circle>
                                </svg>
                                <svg class="icon icon--trophy percent__media">
                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#trophy">
                                    </use>
                                </svg>
                            </div>
                        </div>
                        <div class="course-progress__content">
                            <h6><?php echo $label = Label::getLabel('LBL_COURSE_PROGRESS'); ?></h6>
                            <small class="progressPercent">
                                <?php
                                $progressLbl = Label::getLabel('LBL_{percent}%_COMPLETED');
                                $progressLbl = str_replace('{percent}', $progress['crspro_progress'], $progressLbl);
                                echo $progressLbl;
                                ?>
                            </small>
                        </div>
                    </a>
                    <div id="course-progress" class="course-progress__target">
                        <div class="course-progress__content align-center d-block">
                            <?php
                            if ($progress['crspro_completed']) { ?>
                                <p class="mb-2">
                                    <?php
                                    $label = Label::getLabel('LBL_{completed-lectures}_OF_{total-lectures}_COMPLETE.');
                                    echo str_replace(
                                        ['{completed-lectures}', '{total-lectures}'],
                                        $course['course_lectures'],
                                        $label
                                    );
                                    ?>
                                </p>
                                <p class="mb-3 bold-600">
                                    <?php echo Label::getLabel('LBL_CONGRATULATIONS!_YOUR_COURSE_HAS_BEEN_SUCCESSFULLY_COMPLETED'); ?>
                                </p>
                                <?php if ($canDownloadCertificate == true) { ?>
                                    <a href="<?php echo MyUtility::makeUrl('Certificates', 'index', [$progressId], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--secondary ms-3">
                                        <svg class="icon icon--small me-2">
                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#download-icon"></use>
                                        </svg>
                                        <?php echo Label::getLabel('LBL_DOWNLOAD_CERTIFICATE'); ?>
                                    </a>
                                <?php } ?>
                            <?php } else { ?>
                                <h6 class="m-0"><?php echo $label; ?></h6>
                                <small class="progressPercent"><?php echo $progressLbl; ?></small>
                            <?php } ?>
                        </div>
                    </div>
                </div>
                <!-- ] -->
                <!-- [ USER ACCOUNT ========= -->
                <div class="account">
                    <a href="#accout-target" class="avtar avtar--small avtar--round account__trigger trigger-js" data-title="S">
                        <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $siteUserId, Afile::SIZE_SMALL], CONF_WEBROOT_FRONTEND) . '?' . time() ?>" alt="">
                    </a>
                    <div id="accout-target" class="account__target">
                        <nav class="menu-vertical">
                            <ul>
                                <?php
                                if ($siteUserType == User::LEARNER) { ?>
                                    <li class="menu__item <?php echo ("Learner" == $controllerName) ? 'is-active' : ''; ?>">
                                        <a href="<?php echo MyUtility::makeUrl('Learner', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#dashboard"></use>
                                            </svg>
                                            <?php echo Label::getLabel('LBL_Dashboard'); ?>
                                        </a>
                                    </li>
                                    <li class="menu__item <?php echo ("Teachers" == $controllerName) ? 'is-active' : ''; ?>">
                                        <a href="<?php echo MyUtility::makeUrl('Teachers', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#students"></use>
                                            </svg>
                                            <?php echo Label::getLabel('LBL_My_Teachers'); ?>
                                        </a>
                                    </li>
                                    <li class="menu__item <?php echo ("Lessons" == $controllerName) ? 'is-active' : ''; ?>">
                                        <a href="<?php echo MyUtility::makeUrl('Lessons', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#lessons"></use>
                                            </svg>
                                            <?php echo Label::getLabel('LBL_Lessons'); ?>
                                        </a>
                                    </li>
                                    <li class="menu__item <?php echo ("Classes" == $controllerName) ? 'is-active' : ''; ?>">
                                        <a href="<?php echo MyUtility::makeUrl('Classes', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#group-classes"></use>
                                            </svg>
                                            <?php echo Label::getLabel('LBL_Classes'); ?>
                                        </a>
                                    </li>
                                    <li class="menu__item <?php echo ("Courses" == $controllerName) ? 'is-active' : ''; ?>">
                                        <a href="<?php echo MyUtility::makeUrl('Courses', '', [], CONF_WEBROOT_DASHBOARD); ?>">
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#all-courses"></use>
                                            </svg>
                                            <?php echo Label::getLabel('LBL_Courses'); ?>
                                        </a>
                                    </li>
                                <?php
                                }
                                ?>
                                <li class="menu__item <?php echo ("Account" == $controllerName && "profileInfo" == $action) ? 'is-active' : ''; ?>">
                                    <a href="<?php echo MyUtility::makeUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD); ?>">
                                        <svg class="icon">
                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#settings"></use>
                                        </svg>
                                        <?php echo Label::getLabel('LBL_Settings'); ?>
                                    </a>
                                </li>
                                <li class="menu__item border-top mt-3">
                                    <a href="<?php echo MyUtility::makeUrl('Account', 'logout', [], CONF_WEBROOT_DASHBOARD); ?>">
                                        <svg class="icon" width="24" height="24" viewBox="0 0 24 24">
                                            <path d="M7.68421 19C7.30633 19 7 18.6866 7 18.3V5.7C7 5.3134 7.30633 5 7.68421 5H17.2632C17.641 5 17.9474 5.3134 17.9474 5.7V7.8H16.5789V6.4H8.36842V17.6H16.5789V16.2H17.9474V18.3C17.9474 18.6866 17.641 19 17.2632 19H7.68421ZM16.5789 14.8V12.7H11.7895V11.3H16.5789V9.2L20 12L16.5789 14.8Z" fill="black" />
                                        </svg>
                                        <?php echo Label::getLabel('LBL_Logout'); ?>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
                <!-- ] -->
            </div>
        </div>
        <div class="header-primary__left order-sm-1">
            <div class="d-sm-flex justify-content-sm-between align-items-sm-center">
                <figure class="header-logo">
                    <a href="<?php echo MyUtility::makeUrl('', '', [], CONF_WEBROOT_FRONT_URL); ?>">
                        <?php if (MyUtility::isDemoUrl()) { ?>
                            <img src="<?php echo CONF_WEBROOT_FRONTEND . 'images/logo.svg'; ?>" alt="" />
                        <?php } else { ?>
                            <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', array(Afile::TYPE_FRONT_LOGO, 0, Afile::SIZE_LARGE), CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $websiteName; ?>">
                        <?php } ?>
                    </a>
                </figure>
                <h1 class="page-title">
                    <a href="javascript:void(0);"><?php echo CommonHelper::renderHtml($course['course_title']); ?></a><br>
                    <?php if (isset($editRequestStatus) && $editRequestStatus != '') { ?>
                        <?php $requestDate = MyDate::showDate($requestDate, true); ?>
                        <?php if ($editRequestStatus == Course::EDIT_UPDATES_INPROGRESS) { ?>
                            <small class="color-red" style="font-size: var(--font-size-sm); font-weight: var(--font-weight-bold)">
                                <?php echo str_replace('{request_date}', $requestDate, Label::getLabel('LBL_COURSE_EDITING_IN_PROGRESS_UNTIL_{request_date}')); ?>
                            </small>
                        <?php } elseif ($editRequestStatus == Course::EDIT_UPDATES_COMPLETE) { ?>
                            <small class="color-info" style="font-size: var(--font-size-sm); font-weight: var(--font-weight-bold)">
                                <?php echo str_replace('{updated_date}', $requestDate, Label::getLabel('LBL_LAST_UPDATED_{updated_date}')); ?>
                            </small>
                        <?php } ?>
                    <?php } ?>
                </h1>
            </div>
        </div>
    </div>
</header>
<!-- ] -->