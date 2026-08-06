<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$frm->setFormTagAttribute('id', 'frmCourses');
$titleFld = $frm->getField('course_title');
$titleFld->setFieldTagAttribute('class', 'field-count__wrap');
$titleFld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_COURSE_TITLE'));
$subTitleFld = $frm->getField('course_subtitle');
$subTitleFld->setFieldTagAttribute('class', 'field-count__wrap');
$subTitleFld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_COURSE_SUBTITLE'));
$catgFld = $frm->getField('course_cate_id');
$catgFld->setFieldTagAttribute('onchange', 'getSubCategories(this.value)');
$subCatFld = $frm->getField('course_subcate_id');
$subCatFld->setFieldTagAttribute('id', 'subCategories');
$langFld = $frm->getField('course_clang_id');
$levelFld = $frm->getField('course_level');
$descFld = $frm->getField('course_details');
$descFld->setFieldTagAttribute('class', 'field-count__wrap');
$courseIdFld = $frm->getField('course_id');
$courseId = $courseIdFld->value;
?>

<?php echo $frm->getFormTag(); ?>
<div class="page-layout">
    <div class="page-layout__small">
        <?php echo $this->includeTemplate('courses/sidebar.php', ['frm' => $frm, 'active' => 1, 'courseId' => $courseId]) ?>
    </div>
    <div class="page-layout__large">
        <div class="box-panel">
            <div class="box-panel__head">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4><?php echo Label::getLabel('LBL_MANAGE_BASIC_DETAILS'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="box-panel__body">
                <nav class="tabs tabs--line px-4">
                    <ul>
                        <li class="is-active">
                            <a href="javascript:void(0)"><?php echo Label::getLabel('LBL_General'); ?></a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" <?php echo ($courseId > 0) ? 'onclick="mediaForm();"' : ''; ?>>
                                <?php echo Label::getLabel('LBL_PHOTOS_&_VIDEOS'); ?>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="tabs-data">
                    <div class="box-panel__container">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label">
                                            <?php echo $titleFld->getCaption(); ?>
                                            <span class="spn_must_field">*</span>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <?php $strLen = 80 - strlen($titleFld->value); ?>
                                        <div class="field_cover field-count" data-length="80" field-count="<?php echo $strLen; ?>">
                                            <?php echo $titleFld->getHtml(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label">
                                            <?php echo $subTitleFld->getCaption(); ?>
                                            <span class="spn_must_field">*</span>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <?php $strLen = 160 - strlen($subTitleFld->value); ?>
                                        <div class="field_cover field-count" data-length="160" field-count="<?php echo $strLen; ?>">
                                            <?php echo $subTitleFld->getHtml(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label">
                                            <?php echo $catgFld->getCaption(); ?>
                                            <span class="spn_must_field">*</span>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $catgFld->getHtml(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label">
                                            <?php echo $subCatFld->getCaption(); ?>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $subCatFld->getHtml(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label">
                                            <?php echo $langFld->getCaption(); ?>
                                            <span class="spn_must_field">*</span>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $langFld->getHtml(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label">
                                            <?php echo $levelFld->getCaption(); ?>
                                            <span class="spn_must_field">*</span>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $levelFld->getHtml(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label">
                                            <?php echo $descFld->getCaption(); ?>
                                            <span class="spn_must_field">*</span>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $descFld->getHtml(); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
echo $frm->getFieldHtml('course_id');
?>
</form>
<?php
echo $frm->getExternalJS();
$catId = ($catgFld->value) ? $catgFld->value : 0;
$subCatId = ($subCatFld->value) ? $subCatFld->value : 0;
?>
<script>
    $(document).ready(function() {
        getSubCategories("<?php echo $catId; ?>", "<?php echo $subCatId; ?>");
    });
</script>