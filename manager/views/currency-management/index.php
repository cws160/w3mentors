<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <?php if ($canEdit) { ?>
                <div class="action-toolbar">
                    <?php $lastSync = str_replace('{datetime}', MyDate::formatDate($fixerConfig['last_synced']), Label::getLabel('LBL_LAST_SYNCED_ON_{datetime}')); ?>
                    <span id="last-sync" class="-color-secondary span-right sync-rates-js <?php echo ($fixerConfig['status'] == AppConstant::YES) ? 'show' : 'hide'; ?>"><?php echo $lastSync; ?></span>
                    <a href="javascript:void(0);" id="sync-rates-btn" onclick="syncRates();" class="btn btn-primary sync-rates-js <?php echo ($fixerConfig['status'] == AppConstant::YES) ? 'show' : 'hide'; ?>"><?php echo Label::getLabel('LBL_SYNC_RATES'); ?></a>
                    <a href="javascript:void(0);" onclick="getConfigurationForm();" class="btn btn-primary"><?php echo Label::getLabel('LBL_CONFIGURATION'); ?></a>
                    <a href="javascript:void(0);" onclick="editCurrencyForm(0);" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
                </div>
            <?php } ?>
        </div>
        <div class="card">
            <div class="card-table">
                <div id="listing" class="table-responsive">
                    <div class="table-processing loaderJs">
                        <div class="spinner spinner--sm spinner--brand"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>