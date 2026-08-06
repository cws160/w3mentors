<?php $adminLoggedId = AdminAuth::getLoggedAdminId(); ?>
<?php $isCourseEnabled = Course::isEnabled(); ?>
<?php $isGroupClassEnabled = GroupClass::isEnabled(); ?>
<sidebar class="sidebar sidebar-hoverable" id="sidebar" data-close-on-click-outside="sidebar">
    <div class="sidebar-logo">
        <button class="sidebar-toggle sidebarOpenerBtnJs active" type="button" title="">
            <span class="sidebar-toggle-icon"><span class="toggle-line"></span></span>
        </button>
        <a class="logo" href="<?php echo MyUtility::makeUrl('home'); ?>"><?php echo MyUtility::getLogo(); ?></a>
    </div>
    <div class="sidebar-menu sidebarMenuJs" id="sidebar-menu">
        <ul class="menu" id="sidebarNavLinks">
            <!--Dashboard-->
            <li class="menu-item">
                <a class="menu-section navLinkJs" href="<?php echo MyUtility::makeUrl(); ?>">
                    <span class="menu-icon">
                        <svg class="svg " width="24" height="24">
                            <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-dashboard">
                            </use>
                        </svg>
                    </span>
                    <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_DASHBOARD'); ?></span>
                </a>
            </li>
            <?php
            if (
                $objPrivilege->canViewUsers(true) || $objPrivilege->canViewTeacherRequests(true) || $objPrivilege->canViewWithdrawRequests(true) ||
                $objPrivilege->canViewTeacherReviews(true) || $objPrivilege->canViewGdprRequests(true) || $objPrivilege->canViewAdminUsers(true)
            ) {
            ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-user" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-users">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_MANAGE_USERS'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="manage-user" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewUsers(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Users'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_USERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewTeacherRequests(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('TeacherRequests'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_TEACHER_REQUESTS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewWithdrawRequests(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('WithdrawRequests'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_WITHDRAW_REQUESTS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewTeacherReviews(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('RatingReviews'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_TEACHER_REVIEWS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewGdprRequests(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('GdprRequests') ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_GDPR_REQUESTS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewAdminUsers(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('AdminUsers') ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Manage_Admins'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php }
            if ($isGroupClassEnabled && ($objPrivilege->canViewGroupClasses(true) || $objPrivilege->canViewPackageClasses(true))) { ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#group-classes" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-group-class"> </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_ADMIN_CLASSES'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="group-classes" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewGroupClasses(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('GroupClasses'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_GROUP_CLASSES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewPackageClasses(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('PackageClasses'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_PACKAGES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php } ?>
            <?php
            if (
                $isCourseEnabled && (
                    $objPrivilege->canViewCourses(true) ||
                    $objPrivilege->canViewCourseCategories(true) ||
                    $objPrivilege->canViewCourseRequests(true) ||
                    $objPrivilege->canViewCourseRefundRequests(true) ||
                    $objPrivilege->canViewCourseEditRequests(true) ||
                    $objPrivilege->canViewCourseLanguage(true) ||
                    $objPrivilege->canViewCourseReviews(true)
                )
            ) { ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-courses" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-courses"> </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_MANAGE_COURSES'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="manage-courses" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewCourseLanguage(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('CourseLanguages'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_COURSE_LANGUAGES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCourseCategories(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Categories'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_CATEGORIES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCourses(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Courses'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_COURSES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCourseRequests(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('CourseRequests'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_APPROVAL_REQUESTS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCourseEditRequests(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('CourseEditRequests'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_EDIT_REQUESTS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCourseReviews(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('RatingReviews', 'index', [AppConstant::COURSE]); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_COURSE_REVIEWS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCourseRefundRequests(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('CourseRefundRequests'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_REFUND_REQUESTS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php } ?>
            <?php
            if (
                    $objPrivilege->canViewQuizCategories(true) ||
                    $objPrivilege->canViewQuestions(true) ||
                    $objPrivilege->canViewQuizzes(true)
            ) { ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-quiz" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                        <svg class="svg" width="24" height="24">
                            <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-quizzes"> </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_MANAGE_QUIZZES'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="manage-quiz" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewQuizCategories(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Categories', 'quiz'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_CATEGORIES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if ($objPrivilege->canViewQuestions(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Questions'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_QUESTIONS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if ($objPrivilege->canViewQuizzes(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Quizzes'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_QUIZZES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php } ?>
            <?php
            if (
                $objPrivilege->canViewOrders(true) ||
                $objPrivilege->canViewLessonsOrders(true) ||
                ($isGroupClassEnabled && $objPrivilege->canViewPackagesOrders(true)) ||
                (GroupClass::isEnabled() && $objPrivilege->canViewClassesOrders(true)) ||
                $objPrivilege->canViewWalletOrders(true) ||
                $objPrivilege->canViewGiftcardOrders(true) ||
                $objPrivilege->canViewSubscriptionOrders(true) ||
                ($isCourseEnabled && $objPrivilege->canViewCoursesOrders(true)) ||
                (SubscriptionPlan::isEnabled() && $objPrivilege->canViewSubscriptionPlanOrders(true))
            ) {
            ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-orders" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-orders">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_MANAGE_ORDERS'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="manage-orders" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewOrders(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Orders'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_ALL_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewLessonsOrders(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Lessons'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_LESSONS_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewSubscriptionOrders(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Subscriptions'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_RECURRING_LESSON_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if (SubscriptionPlan::isEnabled() && $objPrivilege->canViewSubscriptionPlanOrders(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('OrderSubscriptionPlans'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_SUBSCRIPTION_PLAN_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($isGroupClassEnabled && $objPrivilege->canViewClassesOrders(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Classes'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_CLASSES_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($isCourseEnabled && $objPrivilege->canViewCoursesOrders(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('CourseOrders'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_COURSE_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($isGroupClassEnabled && $objPrivilege->canViewPackagesOrders(true)) { ?>
                                <li>
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Packages'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_PACKAGES_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewGiftcardOrders(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Giftcards'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_GIFTCARD_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewWalletOrders(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Wallet'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_WALLET_RECHARGE_ORDERS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php
            }

            if ($objPrivilege->canViewIssuesReported(true)) { ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#issue-reported" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-bug">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_ISSUES_REPORTED'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="issue-reported" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ReportedIssues', 'escalated'); ?>">
                                    <span class="nav_text navTextJs">
                                        <?php echo Label::getLabel('LBL_ESCALATED_ISSUES'); ?>
                                    </span>
                                </a>
                            </li>
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ReportedIssues'); ?>">
                                    <span class="nav_text navTextJs">
                                        <?php echo Label::getLabel('LBL_ALL_REPORTED_ISSUES'); ?>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            <?php
            }
            if (
                $objPrivilege->canViewPreferences(true) || $objPrivilege->canViewSpeakLanguage(true) ||
                $objPrivilege->canViewTeachLanguage(true) || $objPrivilege->canViewIssueReportOptions(true) ||
                $objPrivilege->canViewSpeakLanguageLevels(true)
            ) {
            ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#teacher-preferences" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-teacher">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_Teacher_Preferences'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="teacher-preferences" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewPreferences(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('preferences', 'index', [1]); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_ACCENTS'); ?>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('preferences', 'index', [2]); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_TEACHES_LEVEL'); ?>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('preferences', 'index', [3]); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_LEARNERS_AGES'); ?>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('preferences', 'index', [4]); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_LESSONS_INCLUDE'); ?>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('preferences', 'index', [6]); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_TEST_PREPARATION'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewSpeakLanguage(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('SpeakLanguage'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_SPOKEN_LANGUAGE'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewSpeakLanguageLevels(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('SpeakLanguageLevels'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_SPOKEN_LANGUAGE_LEVELS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewTeachLanguage(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('TeachLanguage'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_TEACHING_LANGUAGE'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewIssueReportOptions(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('issueReportOptions'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_ISSUE_REPORT_OPTIONS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php } ?>
            <!--CMS[-->
            <?php
            if (
                $objPrivilege->canViewSlides(true) || $objPrivilege->canViewContentPages(true) ||
                $objPrivilege->canViewContentBlocks(true) || $objPrivilege->canViewNavigationManagement(true) ||
                $objPrivilege->canViewCountries(true) || $objPrivilege->canViewVideoContent(true) ||
                $objPrivilege->canViewTestimonial(true) || $objPrivilege->canViewLanguageLabel(true) ||
                $objPrivilege->canViewFaqCategory(true) || $objPrivilege->canViewFaq(true) || $objPrivilege->canViewEmailTemplates(true) ||
                $objPrivilege->canViewStates(true) || $objPrivilege->canViewAbusiveWords(true) || $objPrivilege->canViewCertificates(true)
            ) {
            ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-cms" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-CMS">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_MANAGE_CMS'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="manage-cms" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewSlides(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('slides'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_HOMEPAGE_SLIDES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewContentPages(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ContentPages'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_CONTENT_PAGES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewContentBlocks(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ContentBlock'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_CONTENT_BLOCKS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewNavigationManagement(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Navigations'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_NAVIGATION'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCountries(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Countries'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_COUNTRIES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewStates(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('States'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_States'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewVideoContent(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('VideoContent'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_VIDEO_CONTENT'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewTestimonial(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Testimonials'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_TESTIMONIALS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewLanguageLabel(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Label'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_LANGUAGE_LABEL'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewFaqCategory(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('FaqCategories'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_FAQ_CATEGORIES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewFaq(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('faq'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_MANAGE_FAQS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewEmailTemplates(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('EmailTemplates'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_EMAIL_TEMPLATES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewAbusiveWords(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('AbusiveWords'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_ABUSIVE_WORDS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCertificates(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Certificates'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_CERTIFICATES'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php } ?>
            <!-- ] -->
            <!--Settings-->
            <?php
            if (
                $objPrivilege->canViewGeneralSettings(true) || $objPrivilege->canViewPaymentMethods(true) ||
                $objPrivilege->canViewSocialPlatforms(true) || $objPrivilege->canViewDiscountCoupons(true) ||
                $objPrivilege->canViewCurrencyManagement(true) || $objPrivilege->canViewCommissionSettings(true) ||
                $objPrivilege->canViewThemeManagement(true) || $objPrivilege->canViewPageLangData(true) ||
                (User::isAffiliateEnabled() &&  $objPrivilege->canViewAffiliateCommission(true))  ||
                (SubscriptionPlan::isEnabled() && $objPrivilege->canViewSubscriptionPlan(true))
            ) {
            ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-setting" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-system-settings">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_Manage_Settings'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="manage-setting" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewGeneralSettings(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('configurations'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_General_Settings'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewMeetingTool(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('MeetingTools'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Meeting_Tools'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewPaymentMethods(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('PaymentMethods'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Payment_Methods'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewSocialPlatforms(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('SocialPlatform'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_SOCIAL_PLATFORMS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewDiscountCoupons(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Coupons'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Discount_Coupons'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCommissionSettings(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Commission'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Commission_Settings'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewCurrencyManagement(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('CurrencyManagement'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Currency_Management'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewThemeManagement(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Themes') ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Theme_Management'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                            <?php if ($objPrivilege->canViewPageLangData(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs dropdown-toggle-custom" href="<?php echo MyUtility::makeUrl('PageLangData') ?>">
                                        <span class="menu-title"><?php echo Label::getLabel('LBL_Page_Language_Data'); ?></span></a>
                                </li>
                            <?php } ?>
                            <?php if (SubscriptionPlan::isEnabled() && $objPrivilege->canViewSubscriptionPlan(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs dropdown-toggle-custom" href="<?php echo MyUtility::makeUrl('subscriptionPlans') ?>">
                                        <span class="menu-title"><?php echo Label::getLabel('LBL_Manage_Subscription_Plans'); ?></span></a>
                                </li>
                            <?php } ?>
                            <?php if (User::isAffiliateEnabled() &&  $objPrivilege->canViewAffiliateCommission(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs dropdown-toggle-custom" href="<?php echo MyUtility::makeUrl('AffiliateCommission') ?>">
                                        <span class="menu-title"><?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION'); ?></span></a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php
            }
            if (
                $objPrivilege->canViewBlogPostCategories(true) || $objPrivilege->canViewBlogPosts(true) ||
                $objPrivilege->canViewBlogComments(true) || $objPrivilege->canViewBlogContributions(true)
            ) {
            ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-blogs" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-blog">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_Manage_Blogs'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="manage-blogs" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewBlogPostCategories(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('BlogPostCategories'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_BLOG_Categories'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewBlogPosts(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('BlogPosts'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Blog_Posts'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewBlogComments(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('BlogComments'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Blog_Comments'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewBlogContributions(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('BlogContributions'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Blog_Contributions'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php
            }
            if (
                $objPrivilege->canViewMetaTags(true) || $objPrivilege->canViewSeoUrl(true) ||
                $objPrivilege->canViewSiteMap(true) || $objPrivilege->canEditSiteMap(true) ||
                $objPrivilege->canViewRobotsSection(true)
            ) {
            ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#manage-seo" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-SEO">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_MANAGE_SEO'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="manage-seo" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewMetaTags(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('MetaTags'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_META_TAGS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewSeoUrl(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('UrlRewriting'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_SEO_URLS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewRobotsSection(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Bots'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_ROBOTS.TXT'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canEditSiteMap(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="javascript:generateSitemap();">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_UPDATE_SITEMAP'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewSiteMap(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo CONF_WEBROOT_FRONT_URL ?>sitemap.xml" target="_blank">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_XML_SITEMAP'); ?>
                                        </span>
                                    </a>
                                </li>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Sitemap', '', [], CONF_WEBROOT_FRONT_URL) ?>" target="_blank">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_HTML_SITEMAP'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php } ?>
            <!-- Report [ -->
            <?php
            if (
                $objPrivilege->canViewLessonLanguages(true) || ($isGroupClassEnabled && $objPrivilege->canViewClassLanguages(true)) ||
                $objPrivilege->canViewTeacherPerformance(true) || $objPrivilege->canViewLessonStatsReport(true) ||
                $objPrivilege->canViewSalesReport(true) || $objPrivilege->canViewSettlementsReport(true) ||
                $objPrivilege->canViewAdminEarningsReport(true) || $objPrivilege->canViewWalletBalanceReport(true) ||
                $objPrivilege->canViewPayoutsReport(true) || $objPrivilege->canViewHoursTaughtReport(true) ||
                (User::isAffiliateEnabled() && $objPrivilege->canViewAffiliateReport(true))
            ) {
            ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#view-reports" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-reports">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_VIEW_REPORTS'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="view-reports" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewLessonLanguages(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('LessonLanguages'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Lessons_Top_Languages'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if (
                                $isGroupClassEnabled && $objPrivilege->canViewClassLanguages(true)
                            ) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ClassLanguages'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Classes_Top_Languages'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewTeacherPerformance(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('TeacherPerformance'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_Teacher_Performance'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewLessonStatsReport(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('LessonStats'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_LESSON_STATS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewSalesReport(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('SalesReport'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_SALES_REPORT'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewSettlementsReport(true)) { ?>
                                <li>
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Settlements'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_SETTLEMENTS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewAdminEarningsReport(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('admin-earnings'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_ADMIN_EARNINGS'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewAffiliateReport(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('affiliate-report'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_AFFILIATE_REPORT'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewWalletBalanceReport(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('WalletBalanceReport'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_WALLET_BALANCE_REPORT'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewPayoutsReport(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('TeacherPayoutsReport'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_TEACHER_PAYOUTS_REPORT'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewHoursTaughtReport(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('HoursTaughtReport'); ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_HOURS_TAUGHT_REPORT'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php } ?>
            <!--  ] -->
            <!-- Discussion Forum -->
            <?php if ($objPrivilege->canViewDiscussionForum(true)) { ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#discussion-forum" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-forum">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_Discussion_Forum'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="discussion-forum" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('Forum'); ?>">
                                    <span class="nav_text navTextJs">
                                        <?php echo Label::getLabel('LBL_All_Questions'); ?>
                                    </span>
                                </a>
                            </li>
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ForumReportedQuestions'); ?>">
                                    <span class="nav_text navTextJs">
                                        <?php echo Label::getLabel('LBL_Reported_Questions'); ?>
                                    </span>
                                </a>
                            </li>
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ForumTags'); ?>">
                                    <span class="nav_text navTextJs">
                                        <?php echo Label::getLabel('LBL_Forum_Tags'); ?>
                                    </span>
                                </a>
                            </li>
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ForumTagRequests'); ?>">
                                    <span class="nav_text navTextJs">
                                        <?php echo Label::getLabel('LBL_Requested_Tags'); ?>
                                    </span>
                                </a>
                            </li>
                            <li class="nav_item navItemJs">
                                <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('ForumReportIssueReasons'); ?>">
                                    <span class="nav_text navTextJs">
                                        <?php echo Label::getLabel('LBL_Report_Reasons'); ?>
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>
            <?php } ?>
            <!--  ] -->
            <?php /*if ($objPrivilege->canViewAppLabels(true) || $objPrivilege->canViewAppPackages(true)) { ?>
                <li class="menu-item dropdownJs">
                    <button class="menu-section dropdown-toggle-custom menuLinkJs collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#mobile-applications" aria-expanded="true" aria-controls="collapseOne">
                        <span class="menu-icon">
                            <svg class="svg" width="24" height="24">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-aside-menu.svg#icon-mobile-app">
                                </use>
                            </svg>
                        </span>
                        <span class="menu-title menuTitleJs"><?php echo Label::getLabel('LBL_Mobile_Applications'); ?></span>
                        <i class="menu_arrow dropdown-toggle-custom-arrow"></i>
                    </button>
                    <div class="sidebar-dropdown-menu collapse" id="mobile-applications" aria-labelledby="" data-bs-parent="#sidebarNavLinks">
                        <ul class="nav nav-level">
                            <?php if ($objPrivilege->canViewAppLabels(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('AppLabels') ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_App_Labels'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            if ($objPrivilege->canViewAppPackages(true)) { ?>
                                <li class="nav_item navItemJs">
                                    <a class="nav_link navLinkJs" href="<?php echo MyUtility::makeUrl('AppPackages') ?>">
                                        <span class="nav_text navTextJs">
                                            <?php echo Label::getLabel('LBL_App_Packages'); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            <?php }*/ ?>
        </ul>
    </div>
</sidebar>