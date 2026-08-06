<?php $isCourseEnabled = Course::isEnabled();
$isSubPlanEnabled = SubscriptionPlan::isEnabled();
?>
<div class="menu-group">
    <h6 class="heading-6"><?php echo label::getLabel('LBL_PROFILE'); ?></h6>
    <nav class="menu menu--primary">
        <ul>
            <li class="menu__item <?php echo ($controllerName == "Learner" && $action == "index") ? 'is-active' : ''; ?> ">
                <a href="<?php echo MyUtility::makeUrl('Learner'); ?>">
                    <svg class="icon icon--dashboard me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#dashboard'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_DASHBOARD'); ?></span>
                </a>
            </li>
            <li class="menu__item <?php echo ($controllerName == "Account") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Account', 'ProfileInfo'); ?>">
                    <svg class="icon icon--settings me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#settings'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_ACCOUNT_SETTINGS'); ?></span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<div class="menu-group">
    <h6 class="heading-6"><?php echo Label::getLabel('LBL_BOOKING'); ?></h6>
    <nav class="menu menu--primary">
        <ul>
            <li class="menu__item <?php echo ($controllerName == "Lessons" && $action == 'index') ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Lessons'); ?>">
                    <svg class="icon icon--lesson me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#lessons'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_LESSONS'); ?></span>
                </a>
            </li>
            <?php if (GroupClass::isEnabled()) { ?>
                <li class="menu__item <?php echo ($controllerName == "Classes") ? 'is-active' : ''; ?>">
                    <a href="<?php echo MyUtility::makeUrl('Classes'); ?>">
                        <svg class="icon icon--group-classes me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#group-classes'; ?>"></use>
                        </svg>
                        <span><?php echo Label::getLabel('LBL_GROUP_CLASSES'); ?></span>
                    </a>
                </li>
                <li class="menu__item <?php echo ($controllerName == "Packages") ? 'is-active' : ''; ?>">
                    <a href="<?php echo MyUtility::makeUrl('Packages'); ?>">
                        <svg class="icon icon--group-classes me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#class-packages'; ?>"></use>
                        </svg>
                        <span><?php echo Label::getLabel('LBL_CLASS_PACKAGES'); ?></span>
                    </a>
                </li>
            <?php } ?>
            <?php if ($isCourseEnabled) { ?>
                <li class="menu__item <?php echo ($controllerName == "Courses") ? 'is-active' : ''; ?>">
                    <a href="<?php echo MyUtility::makeUrl('Courses'); ?>">
                        <svg class="icon icon--group-classes me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#all-courses'; ?>"></use>
                        </svg>
                        <span><?php echo Label::getLabel('LBL_COURSES'); ?></span>
                    </a>
                </li>
            <?php } ?>
            <li class="menu__item <?php echo ('Subscriptions' == $controllerName && 'index' == $action) ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Subscriptions'); ?>">
                    <svg class="icon icon--lesson me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#recurring'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_RECURRING_LESSONS'); ?></span>
                </a>
            </li>
            <?php if ($isSubPlanEnabled) { ?>
                <li class="menu__item <?php echo ('SubscriptionPlans' == $controllerName && 'index' == $action) ? 'is-active' : ''; ?>">
                    <a href="<?php echo MyUtility::makeUrl('SubscriptionPlans'); ?>">
                        <svg class="icon icon--lesson me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#subscription'; ?>"></use>
                        </svg>
                        <span><?php echo Label::getLabel('LBL_MY_SUBSCRIPTIONS'); ?></span>
                    </a>
                </li>
            <?php } ?>
            <li class="menu__item <?php echo ($controllerName == "Issues") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Issues'); ?>">
                    <svg class="icon icon--group-classes me-2 p-1">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#report-issue'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_REPORTED_ISSUES'); ?></span>
                </a>
            </li>
            <li class="menu__item <?php echo ($controllerName == "Teachers") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Teachers'); ?>">
                    <svg class="icon icon--students me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#students'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_MY_TEACHERS'); ?></span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<div class="menu-group">
    <h6 class="heading-6"><?php echo Label::getLabel('LBL_HISTORY'); ?></h6>
    <nav class="menu menu--primary">
        <ul>
            <li class="menu__item <?php echo ($controllerName == "Orders") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Orders'); ?>">
                    <svg class="icon icon--orders me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#orders'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_ORDERS'); ?></span>
                </a>
            </li>
            <li class="menu__item <?php echo ($controllerName == "Wallet" && $action == "index") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Wallet'); ?>">
                    <svg class="icon icon--wallet me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#wallet'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_WALLET'); ?></span>
                </a>
            </li>
            <li class="menu__item <?php echo ($controllerName == "Wallet" && $action == "withdrawRequests") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Wallet/WithdrawRequests'); ?>">
                    <svg class="icon icon--wallet me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#withdrawal-request'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_WITHDRAWS'); ?></span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<div class="menu-group">
    <h6 class="heading-6"><?php echo Label::getLabel('LBL_OTHERS'); ?></h6>
    <nav class="menu menu--primary">
        <ul>
            <?php if (FatApp::getConfig('CONF_ENABLE_FLASHCARD', FatUtility::VAR_BOOLEAN, false)) { ?>
                <li class="menu__item <?php echo ($controllerName == "Flashcards") ? 'is-active' : ''; ?>">
                    <a href="<?php echo MyUtility::makeUrl('Flashcards'); ?>">
                        <svg class="icon icon--flash-cards me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#flashcards'; ?>"></use>
                        </svg>
                        <span><?php echo Label::getLabel('LBL_Flash_Cards'); ?></span>
                    </a>
                </li>
            <?php } ?>
            <li class="menu__item <?php echo ($controllerName == "Giftcard") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Giftcard'); ?>">
                    <svg class="icon icon--gifts-cards me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#giftcards'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_Gift_Cards'); ?></span>
                </a>
            </li>
            <?php if (!empty(FatApp::getConfig('CONF_ENABLE_REFERRAL_REWARDS'))) { ?>
                <li class="menu__item <?php echo ($controllerName == "Refer") ? 'is-active' : ''; ?>">
                    <a href="<?php echo MyUtility::makeUrl('refer'); ?>">
                        <svg class="icon icon--refer-earn me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#refer-earn'; ?>"></use>
                        </svg>
                        <span><?php echo Label::getLabel('LBL_REFER_AND_EARN'); ?></span>
                    </a>
                </li>
            <?php } ?>
            <li class="menu__item">
                <a href="<?php echo MyUtility::makeUrl('Teachers', '', [], CONF_WEBROOT_FRONT_URL); ?>" target="_blank">
                    <svg class="icon icon--small icon--user-search me-3">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#user-search'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_FIND_A_TEACHER'); ?></span>
                </a>
            </li>
            <?php if ($siteUser['user_is_teacher'] == AppConstant::NO) { ?>
                <li class="menu__item">
                    <a href="<?php echo MyUtility::makeUrl('TeacherRequest', '', [], CONF_WEBROOT_FRONT_URL); ?>" target="_blank">
                        <svg class="icon icon--small icon--user-search me-3">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#apply-to-teach'; ?>"></use>
                        </svg>
                        <span><?php echo Label::getLabel('LBL_APPLY_TO_TEACH'); ?></span>
                    </a>
                </li>
            <?php } ?>
            <?php if ($siteUserType == User::LEARNER && $isCourseEnabled) { ?>
                <li class="menu__item <?php echo ($controllerName == "FavoriteCourses") ? 'is-active' : ''; ?>">
                    <a href="<?php echo MyUtility::makeUrl('FavoriteCourses', ''); ?>">
                        <svg class="icon icon--small icon--favorites icon--user-search me-3">
                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD . 'images/sprite.svg#favorite'; ?>"></use>
                        </svg>
                        <span><?php echo Label::getLabel('LBL_FAVORITE_COURSES'); ?></span>
                    </a>
                </li>
            <?php } ?>
        </ul>
    </nav>
</div>
<?php
$vars = ['controllerName' => $controllerName, 'action' => $action];
$this->includeTemplate('_partial/forum-menu.php', $vars, false);
?>