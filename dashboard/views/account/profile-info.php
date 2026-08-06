<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php $infoContent = ExtraPage::getBlockContent(ExtraPage::BLOCK_PROFILE_INFO_BAR, $siteLangId); ?>
<script>
    var userIsTeacher = <?php echo FatUtility::int($siteUserType == User::TEACHER); ?>;
</script>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <h1><?php echo Label::getLabel('LBL_ACCOUNT_SETTINGS'); ?></h1>
    </div>
    <div class="page__body">
        <?php if ($siteUserType == User::TEACHER) { ?>
            <!-- [ INFO BAR ========= -->
            <div class="infobar">
                <div class="row justify-content-between align-items-start">
                    <div class="col-lg-8 col-sm-8">
                        <div class="d-flex">
                            <div class="infobar__media me-4">
                                <div class="infobar__media-icon infobar__media-icon--alert is-profile-complete-js">!</div>
                            </div>
                            <div class="infobar__content">
                                <h6 class="mb-1"><?php echo Label::getLabel('LBL_COMPLETE_YOUR_PROFILE'); ?></h6>
                                <p class="m-0"> <?php echo Label::getLabel('LBL_PROFILE_INFO_HEADING'); ?>
                                    <?php if (!empty($infoContent)) { ?>
                                        <a href="javascript:void(0)" class="color-secondary underline pt-3 pb-3 expand-js"><?php echo Label::getLabel('LBL_LEARN_MORE'); ?></a>
                                    <?php } ?>
                                </p>
                                <?php if (!empty($infoContent)) { ?>
                                    <div class="infobar__content-more mt-3 expand-target-js" style="display: none;">
                                        <?php echo ExtraPage::getBlockContent(ExtraPage::BLOCK_PROFILE_INFO_BAR, $siteLangId); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-sm-4">
                        <div class="profile-progress mt-2">
                            <div class="profile-progress__meta mb-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div><span class="small"> <?php echo Label::getLabel('LBL_PROFILE_PROGRESS'); ?></span></div>
                                    <div><span class="small bold-700 progress-count-js"></span></div>
                                </div>
                            </div>
                            <div class="profile-progress__bar">
                                <div class="progress progress--small progress--round">
                                    <div class="progress-bar">
                                        <div class="progress__step"></div>
                                        <div class="progress__step"></div>
                                        <div class="progress__step"></div>
                                        <div class="progress__step"></div>
                                        <div class="progress__step"></div>
                                        <div class="progress__step"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ] -->
        <?php } ?>
        <!-- [ PAGE PANEL ========= -->
        <div class="page-panel page-panel--flex min-height-500">
            <div class="page-panel__small">
                <nav class="menu menu--vertical menu--steps tabs-scrollable-js">
                    <ul>
                        <li class="menu__item <?php echo ($siteUserType == User::TEACHER) ? 'profile--progress--menu' : ''; ?> is-active">
                            <a href="javascript:void(0);" class="profile-Info-js" onClick="profileInfoForm();">
                                <?php echo Label::getLabel('LBL_PERSONAL_INFO'); ?>
                                <span class="menu__icon"></span>
                            </a>
                        </li>
                        <?php if ($siteUserType == User::TEACHER) { ?>
                            <li class="menu__item profile--progress--menu">
                                <a href="javascript:void(0);" class="teacher-lang-form-js" onClick="teacherLanguagesForm();">
                                    <?php echo Label::getLabel('LBL_TEACHER_LANGUAGES'); ?>
                                    <span class="menu__icon"></span>
                                </a>
                            </li>
                            <li class="menu__item profile--progress--menu">
                                <a href="javascript:void(0);" class="teacher-tech-lang-price-js" id="teacher-tech-lang-price-js" onClick="techLangPriceForm();">
                                    <?php echo Label::getLabel('LBL_PRICE'); ?>
                                    <span class="menu__icon"></span>
                                </a>
                            </li>
                            <li class="menu__item profile--progress--menu">
                                <a href="javascript:void(0);" class="teacher-qualification-js" onClick="teacherQualification();">
                                    <?php echo Label::getLabel('LBL_EXPERIENCE'); ?>
                                    <span class="menu__icon"></span>
                                </a>
                            </li>
                            <li class="menu__item profile--progress--menu">
                                <a href="javascript:void(0);" class="teacher-preferences-js" onClick="teacherPreferencesForm();">
                                    <?php echo Label::getLabel('LBL_SKILLS'); ?>
                                    <span class="menu__icon"></span>
                                </a>
                            </li>
                        <?php } ?>
                        <?php if (User::offlineSessionsEnabled() && $siteUserType != User::AFFILIATE) { ?>
                            <li class="menu__item">
                                <a href="javascript:void(0);" class="address-form-js" onClick="addresses();">
                                    <?php echo Label::getLabel('LBL_ADDRESSES'); ?>
                                </a>
                            </li>
                        <?php } ?>
                        <?php if (count($payoutMethods)) { ?>
                            <?php //$paymentFormAction = isset($payoutMethods[BankPayout::KEY]) ? 'bankInfoForm();' : 'paypalEmailAddressForm();'; 
                            ?>
                            <li class="menu__item">
                                <a href="javascript:void(0);" class="teacher-bankinfo-js" onClick="getPayout();">
                                    <?php echo Label::getLabel('LBL_PAYMENTS'); ?>
                                    <span class="menu__icon"></span>
                                </a>
                            </li>
                        <?php } ?>
                        <li class="menu__item">
                            <a href="javascript:void(0);" onClick="changePasswordForm();">
                                <?php echo Label::getLabel('LBL_PASSWORD_/_EMAIL'); ?>
                                <span class="menu__icon"></span>
                            </a>
                        </li>
                        <li class="menu__item">
                            <a href="javascript:void(0);" onClick="cookieConsentForm(false);">
                                <?php echo Label::getLabel('LBL_COOKIE_CONSENT'); ?>
                                <span class="menu__icon"></span>
                            </a>
                        </li>
                        <li class="menu__item">
                            <a href="javascript:void(0)" onclick="DeleteAccountForm();">
                                <?php echo Label::getLabel('LBL_DELETE_MY_ACCOUNT'); ?>
                                <span class="menu__icon"></span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <div class="page-panel__large">
                <div class="content-panel" id="formBlock-js">
                    <?php echo Label::getLabel('LBL_LOADING'); ?>
                </div>
            </div>
        </div>
        <!-- ] -->
    </div>
    <!-- ] -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo FatApp::getConfig('CONF_GOOGLE_API_KEY', FatUtility::VAR_STRING, '') ?>&libraries=places&v=weekly" defer></script>