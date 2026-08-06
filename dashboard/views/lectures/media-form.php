<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$time = time(); ?>
<?php
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('id', 'frmLectureMedia' . $time);
$frm->setFormTagAttribute('onsubmit', 'setupLectureMedia("frmLectureMedia' . $time . '"); return false;');
$videoFld = $frm->getField('lecsrc_link');
$fld = $frm->getField('btn_cancel');
$fld->setFieldTagAttribute('onclick', 'cancelLecture("' . $lectureId . '");');
$lecsrcId = $frm->getField('lecsrc_id');
?>
<div class="card-box card-group-js is-active" id="lectureMediaForm<?php echo $time; ?>">
    <!-- [ LECTURE TITLE ========= -->
    <div class="card-box__head">
        <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move">
            <svg class="icon icon--sorting">
                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
            </svg>
        </a>
        <div class="card-title">
            <span class="card-title__label">
                <?php echo Label::getLabel('LBL_LECTURE') . ': ' . $lecture['lecture_order']; ?>
            </span>
            <?php if ($lectureId > 0) { ?>
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
            <a href="javascript:void(0);" onclick="cancelLecture('<?php echo $lectureId ?>')" class="card-toggle btn btn--equal btn--transparent color-gray-800 card-toggle-js"> </a>
        </div>
    </div>
    <!-- ] -->
    <div class="card-box__body card-target-js">
        <div class="card-controls">
            <?php
            $this->includeTemplate('lectures/navigation.php', [
                'active' => 'media',
                'lectureId' => $lectureId,
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
                            <div class="field-set mb-3">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $videoFld->getCaption(); ?>
                                        <span class="spn_must_field">*</span>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover m-0">
                                        <?php echo $videoFld->getHtml(); ?>
                                        <small class="style-italic">
                                            <?php
                                            $lblVideo = Label::getLabel('LBL_COURSE_LECTURE_VIDEO_INFO');
                                            echo str_replace(
                                                ['{extensionsVideo}', '{filesizeVideo}'],
                                                [implode(', ', $videoExtensions), $filesize],
                                                $lblVideo
                                            );
                                            ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="progress-div d-none">
                        <progress class='progress progress-bar' value='0' max='100.0'></progress>
                        <span class="progress-bar-info" id='info'>0%</span>
                    </div> -->
                    <div class="attachmentFile  mb-3">
                        <?php if ($lectureRes && !empty($lectureRes['lecsrc_link_name'])) { ?>
                            <?php
                            $class = "is-progress";
                            $tooltip = Label::getLabel('LBL_VIDEO_IS_NOT_READY_YET');
                            if (!empty($error)) {
                                $class = 'is-deleted';
                                $tooltip = $error;
                            } elseif ($lectureRes['lecsrc_duration'] > 0) {
                                $class = 'is-completed';
                                $tooltip = $lectureRes['lecsrc_link_name'];
                            }
                            ?>
                            <div title="<?php echo $tooltip; ?>" class="attachmentFile-status <?php echo $class; ?>">
                            </div>
                        <?php
                        }
                        ?>
                        <span>
                            <?php if (!empty($videoFld->value)) {
                                echo $lectureRes['lecsrc_link_name'];
                            ?>
                        </span>
                        <?php if ($courseStatus != Course::PUBLISHED) { ?>
                            <a href="javascript:void(0);" onclick="removeLectureVideo('<?php echo $lecsrcId->value; ?>');" class="btn btn--equal btn--transparent is-hover color-gray-1000">
                                <svg class="icon icon--edit icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#trash"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black">
                                    <?php echo Label::getLabel('LBL_DELETE'); ?>
                                </div>
                            </a>
                        <?php } ?>
                    <?php } ?>


                    </div>
                    <div class="row">
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
                    echo $frm->getFieldHtml('lecsrc_lecture_id');
                    echo $frm->getFieldHtml('lecsrc_course_id');
                    echo $lecsrcId->getHtml();
                    ?>
                    </form>
                    <?php echo $frm->getExternalJs(); ?>
                </div>
            </div>
        </div>
    </div>
</div>