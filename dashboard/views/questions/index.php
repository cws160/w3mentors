<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('onsubmit', 'search(this); return(false);');
$frm->setFormTagAttribute('class', 'form form--small');
$keywordFld = $frm->getField('keyword');
$catFld = $frm->getField('ques_cate_id');
$subCatFld = $frm->getField('ques_subcate_id');
$subCatFld->setFieldTagAttribute('id', 'subCategories');
$typeFld = $frm->getField('ques_type');
$submitBtn = $frm->getField('btn_submit');
$catFld->addFieldTagAttribute('onchange', 'getSubcategories(this.value, "#subCategories")');
$btnReset = $frm->getField('btn_clear');
$btnReset->addFieldTagAttribute('onclick', 'clearSearch()');
?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_QUESTIONS'); ?></h1>
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
                        <a href="javascript:void(0);" onclick="questionForm(0);" class="btn color-secondary btn--bordered">
                            <svg class="icon icon--add icon--small me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M11 11V7h2v4h4v2h-4v4h-2v-4H7v-2h4zm1 11C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"></path>
                            </svg>
                            <?php echo Label::getLabel('LBL_ADD_QUESTION'); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <!-- [ FILTERS ========= -->
        <div class="search-filter slide-target-js">
            <?php echo $frm->getFormTag(); ?>
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
                                <?php echo $catFld->getCaption(); ?>
                                <?php if ($catFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $catFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $subCatFld->getCaption(); ?>
                                <?php if ($subCatFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $subCatFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $typeFld->getCaption(); ?>
                                <?php if ($typeFld->requirement->isRequired()) { ?>
                                    <span class="spn_must_field">*</span>
                                <?php } ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $typeFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-sm-6 form-buttons-group">
                    <div class="field-set">
                        <div class="caption-wraper"><label class="field_label"></label></div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $frm->getFieldHtml('pageno'); ?>
                                <?php echo $frm->getFieldHtml('pagesize'); ?>
                                <?php echo $frm->getFieldHtml('btn_submit'); ?>
                                <?php echo $frm->getFieldHtml('btn_clear'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </form>
            <?php echo $frm->getExternalJS(); ?>
        </div>
        <!-- ] ========= -->
    </div>
    <div class="page__body">
        <!-- [ PAGE PANEL ========= -->
        <div class="page-content" id="listing">
        </div>
        <!-- ] -->
    </div>
    <script>
        var TYPE_SINGLE = <?php echo Question::TYPE_SINGLE; ?>;
        var TYPE_MULTIPLE = <?php echo Question::TYPE_MULTIPLE; ?>;
        var TYPE_TEXT = <?php echo Question::TYPE_TEXT; ?>;
    </script>