<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'search(this);return false;');
$keywordFld = $frm->getField('keyword');
$keywordFld->addFieldTagAttribute('placeholder', Label::getLabel('LBL_KEYWORD'));
$serviceFld = $frm->getField('service_type');
$statusFld = $frm->getField('grpcls_status');
$durationFld = $frm->getField('grpcls_duration');
$frm->getField('btn_clear')->addFieldTagAttribute('onClick', 'clearSearch();');
$labels = [
    'START_TIME' => Label::getLabel('LBL_START_TIME'),
    'CLASS_TITLE' => Label::getLabel('LBL_CLASS_TITLE'),
    'REMOVE_CLASS' => Label::getLabel('LBL_REMOVE_CLASS')
];
?>
<script>
    labels = <?php echo json_encode($labels); ?>
</script>
<!-- [ PAGE ========= -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_MANAGE_CLASS_PACKAGES'); ?></h1>
            </div>
            <div class="col-sm-auto">
                <div class="buttons-group d-flex align-items-center gap-2">
                    <a href="javascript:void(0)" class="btn btn--secondary slide-toggle-js">
                        <svg class="icon icon--search icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_SEARCH'); ?>
                    </a>
                    <?php if ($siteUserType == User::TEACHER) { ?>
                        <a href="javascript:void(0);" onclick="form(0);" class="btn color-secondary btn--bordered">
                            <svg class="icon icon--add icon--small me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M11 11V7h2v4h4v2h-4v4h-2v-4H7v-2h4zm1 11C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"></path>
                            </svg>
                            <?php echo Label::getLabel('LBL_ADD_PACKAGE'); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <div class="page__body">
        <!-- [ FILTERS ========= -->
        <div class="page-filter">
            <?php echo $frm->getFormTag(); ?>
            <div class="search-filter slide-target-js" style="display: none;">
                <div class="row">
                    <div class="col-lg-4 col-sm-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $keywordFld->getCaption(); ?>
                                    <?php if ($keywordFld->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $keywordFld->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $serviceFld->getCaption(); ?>
                                    <?php if ($serviceFld->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $serviceFld->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"></label></div>
                            <div class="field-wraper form-buttons-group">
                                <div class="field_cover">
                                    <?php echo $frm->getFieldHtml('pageno'); ?>
                                    <?php echo $frm->getFieldHtml('pagesize'); ?>
                                    <?php echo $frm->getFieldHtml('grpcls_id'); ?>
                                    <?php echo $frm->getFieldHtml('order_id'); ?>
                                    <?php echo $frm->getFieldHtml('btn_submit'); ?>
                                    <?php echo $frm->getFieldHtml('btn_clear'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </form>
            <?php echo $frm->getExternalJS(); ?>
        </div>
        <!-- ] ========= -->
        <!-- [ PAGE PANEL ========= -->
        <div class="page-content" id="listing"></div>
        <!-- ] -->
    </div>
    <script>
        $(document).ready(function() {
            search(document.frmClassSearch);
        });
    </script>