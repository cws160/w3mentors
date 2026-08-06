<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_REQUEST_INFORMATION'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body pb-4">
    <div class="content-repeated-container">
        <div class="content-repeated mb-4">
            <div class="row g-2">
                <div class="col-xl-4 col-lg-4 col-sm-4">
                    <strong><?php echo Label::getLabel('LBL_REQUESTED_ON'); ?></strong>
                </div>
                <div class="col-xl-8 col-lg-8 col-sm-8">
                    <strong><?php echo MyDate::showDate($requestData['coedre_created'], true); ?></strong>
                </div>
            </div>
        </div>
        <div class="content-repeated mb-4">
            <div class="row g-2">
                <div class="col-xl-4 col-lg-4 col-sm-4">
                    <strong><?php echo Label::getLabel('LBL_STATUS'); ?></strong>
                </div>
                <div class="col-xl-8 col-lg-8 col-sm-8">
                    <?php echo Course::getEditRequestStatuses($requestData['coedre_status']); ?>
                </div>
            </div>
        </div>
        <div class="content-repeated">
            <div class="row g-2">
                <div class="col-xl-4 col-lg-4 col-sm-4">
                    <strong><?php echo Label::getLabel('LBL_REQUEST_REASON'); ?></strong>
                </div>
                <div class="col-xl-8 col-lg-8 col-sm-8">
                    <?php echo nl2br($requestData['coedre_reason']); ?>
                </div>
            </div>
        </div>
    </div>
</div>