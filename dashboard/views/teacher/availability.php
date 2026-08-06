<?php $infoContent = ExtraPage::getBlockContent(ExtraPage::BLOCK_PROFILE_INFO_BAR, $siteLangId); ?>
<div class="container container--fixed">
    <div class="page__head">
        <h1><?php echo Label::getLabel('LBL_MANAGE_CALENDAR'); ?></h1>
    </div>
    <div class="page__body">
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
        <!-- [ PAGE PANEL ========= -->
        <div class="page-panel" style="min-height: 400px;" id="availability-calendar-js">
        </div>
        <!-- ] -->
    </div>
    <script>
        $(document).ready(function() {
            generalAvailability();
        });
    </script>