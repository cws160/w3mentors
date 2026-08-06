<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$subscFormObj->addFormTagAttribute('onsubmit', 'return false;');
$keywordFld = $subscFormObj->getField('keyword');
$keywordFld->addFieldTagAttribute('placeholder', $keywordFld->getCaption());
?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--small">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-12">
                <h1><?php echo Label::getLabel('LBL_Subscribed_Tags'); ?></h1>
                <p class="m-0">
                    <?php echo Label::getLabel('LBL_Use_this_page_to_manage_subscription_for_Forum_tags_and_their_current_status_etc'); ?>
                </p>
            </div>
        </div>
    </div>
    <div class="page__body">
        <!-- [ PAGE PANEL ========= -->
        <div class="page-content">
            <div class="page-panel">
                <div class="page-panel__body">
                    <div class="add-tag-form pb-4">
                        <?php echo $subscFormObj->getFormTag(); ?>
                        <div class="field-icon">
                            <svg data-type="new" class="icon icon--request-tag  color-black">
                                <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL . 'images/forum/sprite.svg#request-tag'; ?>"></use>
                            </svg>
                            <?php echo $keywordFld->getHtml(); ?>
                        </div>
                        </form>
                        <?php echo $subscFormObj->getExternalJS(); ?>
                        <div class="unsubscribe-all mt-3">
                            <a href="javascript:void(0);" id="unsubscribe-all-js" class="btn btn--bordered color-secondary"><?php echo Label::getLabel('LBL_UNSUBSCRIBE_ALL'); ?></a>
                        </div>
                    </div>
                    <div class="tag-group pb-4">
                        <h5><?php echo Label::getLabel('LBL_Subscribed_tags_List'); ?></h5>
                        <div class="mt-2" id="listing"></div>
                    </div>
                    <div class="tag-group">
                        <h5><?php echo Label::getLabel('LBL_Questions_tags_List'); ?></h5>
                        <div class="mt-2" id="system-tags"></div>
                    </div>
                </div>
            </div>
            <!-- ] -->
        </div>
        <script>
            var totalRecords = 0;
        </script>