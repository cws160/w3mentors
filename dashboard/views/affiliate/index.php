<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
?>
<div class="container container--fixed">
    <div class="page__head">
        <h1><?php echo Label::getLabel('LBL_DASHBOARD'); ?></h1>
    </div>
    <div class="page__body">
        <div class="dashboard">
            <div class="dashboard__primary">
                <div class="stats-row">
                    <div class="row align-items-center g-4">
                        <div class="col-md-6 col-sm-6">
                            <div class="stat">
                                <div class="stat__amount">
                                    <span><?php echo Label::getLabel('LBL_TOTAL_REFEREES'); ?></span>
                                    <h5><?php echo $referralsCount; ?></h5>
                                </div>
                                <div class="stat__media bg-secondary">
                                    <svg class="icon icon--money icon--40 color-white">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#user'; ?>"></use>
                                    </svg>
                                </div>
                                <a href="<?php echo MyUtility::makeUrl('Refer'); ?>" class="stat__action"></a>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <div class="stat">
                                <div class="stat__amount">
                                    <span><?php echo Label::getLabel('LBL_TOTAL_SIGNUP_REVENUE'); ?></span>
                                    <h5><?php echo MyUtility::formatMoney($totalSignupCommission); ?></h5>
                                </div>
                                <div class="stat__media bg-secondary">
                                    <svg class="icon icon--money icon--40 color-white">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#stats'; ?>"></use>
                                    </svg>
                                </div>
                                <a href="<?php echo MyUtility::makeUrl('Wallet'); ?>" class="stat__action"></a>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <div class="stat">
                                <div class="stat__amount">
                                    <span><?php echo Label::getLabel('LBL_TOTAL_ORDER_REVENUE'); ?></span>
                                    <h5><?php echo MyUtility::formatMoney($totalOrderCommission); ?></h5>
                                </div>
                                <div class="stat__media bg-secondary">
                                    <svg class="icon icon--money icon--40 color-white">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#stats'; ?>"></use>
                                    </svg>
                                </div>
                                <a href="<?php echo MyUtility::makeUrl('Wallet'); ?>" class="stat__action"></a>
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-6">
                            <div class="stat">
                                <div class="stat__amount">
                                    <span><?php echo Label::getLabel('LBL_WALLET_BALANCE'); ?></span>
                                    <h5><?php echo MyUtility::formatMoney($walletBalance); ?></h5>
                                </div>
                                <div class="stat__media bg-secondary">
                                    <svg class="icon icon--money icon--40 color-white">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#stats_2'; ?>"></use>
                                    </svg>
                                </div>
                                <a href="<?php echo MyUtility::makeUrl('Wallet'); ?>" class="stat__action"></a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-content">
                    <div class="results" id="listItemsLessons">
                    </div>
                </div>
            </div>
        </div>
    </div>