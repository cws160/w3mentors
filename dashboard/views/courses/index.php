<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'search(this); return(false);');
$keywordFld = $frm->getField('keyword');
if ($siteUserType == User::LEARNER) {
    $keywordFld->addFieldTagAttribute('placeholder', Label::getLabel('LBL_SEARCH_BY_COURSE_TITLE,_TEACHER,_ORDER_ID'));
} else {
    $keywordFld->addFieldTagAttribute('placeholder', Label::getLabel('LBL_SEARCH_BY_COURSE_TITLE'));
}
$typeFld = $frm->getField('course_type');
$catgFld = $frm->getField('course_cateid');
$catgFld->addFieldTagAttribute('onchange', 'getSubCategories(this.value)');
$subcatgFld = $frm->getField('course_subcateid');
$subcatgFld->setFieldTagAttribute('id', 'subCategories');
$btnReset = $frm->getField('btn_reset');
$btnReset->addFieldTagAttribute('onclick', 'clearSearch()');
?>
<!-- [ PAGE ========= -->
<!-- <main class="page"> -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-7">
                <h1><?php echo Label::getLabel('LBL_MANAGE_COURSES'); ?></h1>
                <p class="m-0">
                    <?php echo Label::getLabel('LBL_MANAGE_COURSE_PAGE_SUB_HEADING'); ?>
                </p>
            </div>
            <div class="col-sm-auto">
                <div class="buttons-group d-flex align-items-center gap-2">
                    <a href="javascript:void(0)" class="btn bg-secondary slide-toggle-js">
                        <svg class="icon icon--clock icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL ?>images/sprite.svg#search"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_SEARCH'); ?>
                    </a>
                    <?php if ($siteUserType == User::TEACHER) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Courses', 'form') ?>" class="btn color-secondary btn--bordered">
                            <svg class="icon icon--uploader me-2">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL ?>images/sprite.svg#uploader"></use>
                            </svg>
                            <?php echo Label::getLabel('LBL_ADD_NEW_COURSE'); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="search-filter slide-target-js" style="display: none;">
            <?php echo $frm->getFormTag(); ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $keywordFld->getCaption(); ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $keywordFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $catgFld->getCaption(); ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $catgFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $subcatgFld->getCaption(); ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $subcatgFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label">
                                <?php echo $typeFld->getCaption(); ?>
                            </label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php echo $typeFld->getHtml(); ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($siteUserType == User::TEACHER) {
                    $status = $frm->getField('course_status');
                ?>
                    <div class="col-md-4">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $status->getCaption(); ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $status->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } else {
                    $status = $frm->getField('crspro_status');
                ?>
                    <div class="col-md-4">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $status->getCaption(); ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $status->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="col-md-4 form-buttons-group">
                    <div class="field-set">
                        <div class="caption-wraper">
                            <label class="field_label"></label>
                        </div>
                        <div class="field-wraper">
                            <div class="field_cover">
                                <?php
                                echo $frm->getFieldHtml('btn_submit');
                                echo $frm->getFieldHtml('btn_reset');
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo $frm->getFieldHtml('page'); ?>
            <?php echo $frm->getFieldHtml('pagesize'); ?>
            </form>
            <?php echo $frm->getExternalJS(); ?>
        </div>
    </div>
    <div class="page__body">
        <div class="page-content" id="listing"></div>
    </div>