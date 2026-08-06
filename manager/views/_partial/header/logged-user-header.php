<div class="app">
    <?php $this->includeTemplate('_partial/header/left-navigation.php') ?>
    <!--header start here-->

    <!--header end here-->
    <!--body start here-->
    <div class="wrap">
        <header class="main-header mainHeaderJs">
            <div class="container-fluid">
                <div class="main-header-inner">
                    <div class="page-title">
                        <h1>
                            <?php
                            if (!empty($this->variables['pageText']) && array_key_exists('pageTitle', $this->variables['pageText'])) {
                                echo CommonHelper::renderHtml($this->variables['pageText']['pageTitle']);
                            } else {
                                echo Label::getLabel('NAV_DASHBOARD', $siteLangId);
                            }
                            ?>
                        </h1>
                        <?php if (isset($this->variables['pageText']['pageSummary'])) { ?>
                        <span class="page-title-sub">
                            <?php echo CommonHelper::renderHtml($this->variables['pageText']['pageSummary']); ?>
                            <a href="javascript:void(0)" class="openAlertJs"
                                data-pageid="<?php echo CommonHelper::renderHtml($this->variables['pageText']['plangId']); ?>"
                                data-name="<?php echo 'alert_' . CommonHelper::renderHtml($this->variables['pageText']['plangId']); ?>">
                                <?php if (!empty($this->variables['pageText']['pageWarringMsg'])) { ?>
                                <svg class="svg" width="20" height="20">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg#alert">
                                    </use>
                                </svg>
                                <?php } ?>
                            </a>
                        </span>
                        <?php } ?>
                    </div>
                    <div class="main-header-toolbar">
                        <div class="header-action">
                            <div class="header-action__item">
                                <?php if ($controllerName == 'Home' && $actionName == 'index' && $objPrivilege->canViewSalesReport(true) && $objPrivilege->canEditReportStatsRegenerate(true)) { ?>
                                <a class="header-action__trigger"
                                    title="<?php echo Label::getLabel('LBL_REGENERATE_STATS') . ' (' . $regendatedtime . ')'; ?>"
                                    onclick="regenerateStat();" href="javascript:void(0);">
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg#icon-stats">
                                            </use>
                                        </svg>
                                    </span>
                                </a>
                                <?php } ?>
                            </div>
                            <div class="header-action__item">
                                <a class="header-action__trigger"
                                    title="<?php echo Label::getLabel('LBL_View_Portal'); ?>"
                                    href="<?php echo CONF_WEBROOT_FRONT_URL; ?>" target="_blank">
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg#icon-store">
                                            </use>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                            <div class="header-action__item">
                                <a class="header-action__trigger"
                                    title="<?php echo Label::getLabel('LBL_Clear_Cache'); ?>" href="javascript:void(0)"
                                    onclick="clearCache()">
                                    <span class="icon">
                                        <svg class="svg" width="20" height="20">
                                            <use
                                                xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg#icon-cache">
                                            </use>
                                        </svg>
                                    </span>
                                </a>
                            </div>
                            <div class="header-action__item">
                                <div class="dropdown">
                                    <a class="dropdown-toggle header-action__trigger no-after" data-bs-toggle="dropdown"
                                        href="javascript:void(0)">
                                        <span class="icon">
                                            <svg class="svg" width="20" height="20">
                                                <use
                                                    xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/retina/sprite.svg#icon-lang">
                                                </use>
                                            </svg>
                                        </span>
                                    </a>
                                    <div
                                        class="header-action__target dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim dropDownMenuBlockClose">
                                        <div class="pt-3 pb-0 px-4">
                                            <h6 class="mb-0"><?php echo Label::getLabel('LBL_Admin_Select_Language'); ?></h6>
                                        </div>
                                        <nav class="nav nav--header-account">
                                            <?php foreach ($siteLanguages as $langId => $language) { ?>
                                            <div
                                                <?php echo ($siteLangId == $language['language_id']) ? 'class="is--active"' : ''; ?>>
                                                <a href="javascript:void(0);"
                                                    onClick="setSiteDefaultLang(<?php echo $language['language_id']; ?>)"><?php echo CommonHelper::renderHtml($language['language_name']); ?></a>
                                            </div>
                                            <?php } ?>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                            <div class="header-action__item">
                                <div class="dropdown header-account">
                                    <a class="dropdown-toggle header-action__trigger no-before no-after"
                                        data-bs-toggle="dropdown" href="javascript:void(0)">
                                        <span class="header-account__img">
                                            <img id="leftmenuimgtag" alt=""
                                                src="<?php echo MyUtility::makeUrl('image', 'show', [Afile::TYPE_ADMIN_PROFILE_IMAGE, $adminLoggedId, Afile::SIZE_SMALL]); ?>"
                                                alt="">
                                        </span>
                                    </a>
                                    <div
                                        class="header-action__target dropdown-menu dropdown-menu-fit dropdown-menu-right dropdown-menu-anim dropDownMenuBlockClose">
                                        <div class="header-account__avtar">
                                            <div class="profile">
                                                <div class="profile__img">
                                                    <img id="leftmenuimgtag" alt=""
                                                        src="<?php echo MyUtility::makeUrl('image', 'show', [Afile::TYPE_ADMIN_PROFILE_IMAGE, $adminLoggedId, Afile::SIZE_SMALL]); ?>"
                                                        alt="">
                                                </div>
                                                <div class="profile__detail">
                                                    <h6><?php echo Label::getLabel('LBL_HI'); ?>,
                                                        <?php echo $adminName; ?></h6>
                                                    <span><a><?php echo $adminEmail; ?></a></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="separator m-0"></div>
                                        <nav class="nav nav--header-account">
                                            <a
                                                href="<?php echo MyUtility::makeUrl('profile'); ?>"><?php echo Label::getLabel('LBL_View_Profile'); ?></a>
                                            <a
                                                href="<?php echo MyUtility::makeUrl('profile', 'changePassword'); ?>"><?php echo Label::getLabel('LBL_Change_Password'); ?></a>
                                            <a href="javascript:void(0);"
                                                onclick="logout();"><?php echo Label::getLabel('LBL_Logout'); ?></a>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php if (isset($this->variables['pageText']['pageWarringMsg']) && !empty($this->variables['pageText']['pageWarringMsg']) && !Common::isSetCookie('alert_' . $this->variables['pageText']['plangId'])) { ?>
            <div class="alert alert-solid-warning fade alertWarningJs show" role="alert">
                <div class="alert-text">
                    <?php echo nl2br(CommonHelper::renderHtml($this->variables['pageText']['pageWarringMsg'])); ?></div>
                <div class="alert-close">
                    <button type="button"
                        class="btn-close closeAlertJs <?php echo 'alert_' . $this->variables['pageText']['plangId']; ?>"
                        data-bs-dismiss="alert" aria-label="Close"
                        data-name="<?php echo 'alert_' . $this->variables['pageText']['plangId']; ?>">
                    </button>
                </div>
            </div>
            <?php } ?>

            <?php if (isset($this->variables['pageText']['pageRecommendations']) && !empty($this->variables['pageText']['pageRecommendations']) && !Common::isSetCookie('alert_' . $this->variables['pageText']['plangId'])) { ?>
            <div class="alert alert-solid-info fade show" role="alert">
                <div class="alert-text">
                    <?php echo nl2br(CommonHelper::renderHtml($this->variables['pageText']['pageRecommendations'])); ?>
                </div>
            </div>
            <?php } ?>
        </header>