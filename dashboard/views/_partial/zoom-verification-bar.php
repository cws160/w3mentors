<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!-- [ INFO BAR ========= -->
<?php if (ZoomMeeting::ACC_SYNCED_AND_VERIFIED != $zoomVerificationStatus) {  ?>
    <div class="infobar infobar--danger">
        <div class="row justify-content-between align-items-center">
            <div class="col-sm-8 col-lg-6 col-xl-8">
                <div class="d-flex">
                    <div class="infobar__content">
                        <h6 class="mb-1"><?php echo Label::getLabel('LBL_DASHBOARD_HEADING_ZOOM'); ?></h6>
                        <p class="m-0"><?php echo Label::getLabel('LBL_DASHBOARD_ZOOM_INFO_TEXT'); ?></p>
                    </div>
                </div>
            </div>
            <?php if (ZoomMeeting::ACC_SYNCED_NOT_VERIFIED == $zoomVerificationStatus) { ?>
                <div class="col-sm-4 col-lg-3  col-xl-4">
                    <div class="-align-right">
                        <a href="javascript:void(0);" onClick="createZoomAccount('verify');" class="btn btn--white"><?php echo Label::getLabel('LBL_DASH_VERIFY_ZOOM_ACCOUNT') ?></a>
                    </div>
                </div>
            <?php } else { ?>
                <div class="col-sm-4 col-lg-3  col-xl-4">
                    <div class="-align-right">
                        <a href="javascript:void(0);" onClick="createZoomAccount();" class="btn btn--white"><?php echo Label::getLabel('LBL_DASH_SYNC_ZOOM_ACCOUNT') ?></a>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
<?php
}
?>
<!-- ] -->