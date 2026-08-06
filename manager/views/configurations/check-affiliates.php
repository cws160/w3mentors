<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card">
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title"><?php echo Label::getLabel('LBL_CONFIRMATION'); ?></h3>
        </div>
    </div>
    <div class="card-body">
        <?php if (isset($stats['affiliates']) || $stats['revenue'] > 0) { ?>
            <h6><?php echo Label::getLabel('LBL_CONFIRM_AFFILIATE_DEACTIVATION'); ?></h6>

            <div class="row mt-4">
                <?php if (isset($stats['affiliates'])) { ?>
                    <div class="col-md-6">
                        <div class="stats">
                            <div class="stats__content">
                                <h6 class="text-uppercase"> <?php echo Label::getLabel('LBL_AFFILIATES'); ?></h6>
                                <h3 class="counter"><?php echo $stats['affiliates']; ?></h3>
                                <a href="<?php echo MyUtility::makeUrl('Users') . '?type=' . User::AFFILIATE; ?>" class="stats__link"></a>
                            </div>
                            <span class="stats__icon">
                                <img src="<?php echo CONF_WEBROOT_URL ?>images/total-users.svg" alt="">
                            </span>
                        </div>
                    </div>
                <?php } ?>
                <?php if ($stats['revenue'] > 0) { ?>
                    <div class="col-md-6">
                        <div class="stats">
                            <div class="stats__content">
                                <h6 class="text-uppercase"><?php echo Label::getLabel('LBL_TOTAL_REVENUE'); ?></h6>
                                <h3 class="counter"><?php echo $stats['revenue']; ?></h3>
                                <a class="stats__link" href="<?php echo MyUtility::makeUrl('AffiliateReport') ?>" target="_blank"></a>
                            </div>
                            <span class="stats__icon">
                                <img src="<?php echo CONF_WEBROOT_URL ?>images/total-lessons.svg" alt="">
                            </span>
                        </div>
                    </div>
                <?php } ?>
            </div>
            
            

            <div class="confirm-action pt-3">
                <a class="btn btn-primary" onclick="disableAffiliates();">
                    <?php echo Label::getLabel('LBL_PROCEED_WITH_DEACTIVATION'); ?>
                </a>
            </div>


        <?php } else { ?>
            <h3><?php echo Label::getLabel('LBL_ARE_YOU_SURE_YOU_WANT_TO_DEACTIVATE_AFFILIATES?'); ?></h3>
            <div class="row">
                <div class="col-md-12">&nbsp;</div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <a class="btn btn-primary" onclick="disableAffiliates();">
                        <?php echo Label::getLabel('LBL_PROCEED_WITH_DEACTIVATION'); ?>
                    </a>
                </div>
            </div>
        <?php } ?>
    </div>
</div>