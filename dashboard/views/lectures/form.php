<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$frm->setFormTagAttribute('class', 'form');
$frm->setValidatorJsObjectName('frmLecture' . $lectureDivId . 'Validator');
$frm->setFormTagAttribute('id', 'frmLecture' . $lectureDivId);
$titleFld = $frm->getField('lecture_title');
$titleFld->setFieldTagAttribute('class', 'field-count__wrap');
$titleFld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_ADD_LECTURE_TITLE'));
$descFld = $frm->getField('lecture_details');
$descFld->setFieldTagAttribute('class', 'field-count__wrap');
$trialFld = $frm->getField('lecture_is_trial');
$trialFld->setFieldTagAttribute('class', 'switch__label');
$fld = $frm->getField('btn_cancel');
$sectionId = $frm->getField('lecture_section_id')->value;
if ($lecture['lecture_id'] > 0) {
    $action = 'cancelLecture(\'' . $lecture['lecture_id'] . '\')';
} else {
    $action = 'removeLectureForm(\'' . $sectionId . '\', \'#sectionLectures' . $lectureDivId . '\');';
}
$fld->setFieldTagAttribute('onclick', $action);
$titleLength = 255;
?>
<div class="card-box card-group-js is-active <?php echo ($lecture['lecture_id'] > 0) ? 'lecturePanelJs' : ''; ?>" id="sectionLectures<?php echo $lectureDivId ?>" <?php if ($lecture['lecture_id'] > 0) { ?> data-id="<?php echo $lecture['lecture_id'] ?>" <?php } ?>>
    <!-- [ LECTURE TITLE ========= -->
    <div class="card-box__head">
        <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move">
            <svg class="icon icon--sorting">
                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
            </svg>
        </a>
        <div class="card-title">
            <span class="card-title__label">
                <?php
                echo Label::getLabel('LBL_LECTURE');
                if ($lecture['lecture_order'] > 0) {
                    echo ': ' . $lecture['lecture_order'];
                }
                ?>
            </span>
            <?php if ($lecture['lecture_id'] > 0) { ?>
                <div class="card-title__meta">
                    <div class="card-title__content">
                        <span class="card-title__caption">
                            <?php echo $lecture['lecture_title'] ?>
                        </span>
                        <!-- ] -->
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="card-options card-options--positioned">
            <a href="javascript:void(0);" onclick="<?php echo $action; ?>" class="card-toggle btn btn--equal btn--transparent color-gray-800 card-toggle-js"> </a>
        </div>
    </div>
    <!-- ] -->
    <div class="card-box__body card-target-js">
        <div class="card-controls">
            <?php
            $this->includeTemplate('lectures/navigation.php', [
                'active' => 'description',
                'lectureId' => $lecture['lecture_id'],
                'sectionId' => $lecture['lecture_section_id'],
            ]);
            ?>
        </div>
        <div class="card-controls-content">
            <div class="card-controls-view controls-tabs-view-js">
                <div class="step-small-form">
                    <?php
                    echo $frm->getFormTag();
                    ?>
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
                                    <?php
                                    $strLen = $titleLength - strlen($titleFld->value); ?>
                                    <div class="field_cover field-count" data-length="<?php echo $titleLength ?>" field-count="<?php echo $strLen; ?>">
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
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="field-set">
                                <label class="switch-group d-flex align-items-center">
                                    <span class="switch switch--small">
                                        <?php
                                        $fld = $trialFld->getHtml();
                                        $fld = str_replace("<label >", "", $fld);
                                        $fld = str_replace("</label>", "", $fld);
                                        $fld = str_replace($trialFld->getCaption(), "", $fld);
                                        echo $fld;
                                        ?>
                                        <i class="switch__handle bg-green"></i>
                                    </span>
                                    <span class="switch-group__label free-trial-status-js ms-3"> <?php echo $trialFld->getCaption(); ?></span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="row align-items-center">
                        <div class="col-md-12">
                            <div class="step-actions">
                                <?php
                                echo $frm->getFieldHtml('btn_submit');
                                echo $frm->getFieldHtml('btn_cancel');
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php
                    echo $frm->getFieldHtml('lecture_section_id');
                    echo $frm->getFieldHtml('lecture_course_id');
                    echo $frm->getFieldHtml('lecture_id');
                    ?>
                    </form>
                    <?php echo $frm->getExternalJs(); ?>
                </div>
            </div>
        </div>
    </div>
</div>