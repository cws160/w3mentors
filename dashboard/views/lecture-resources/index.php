<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$time = time();
?>
<div class="card-box card-group-js is-active" id="lectureResrcForm<?php echo $time; ?>">
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
            <a href="javascript:void(0);" onclick="cancelLecture('<?php echo $lectureId ?>');" class="card-toggle btn btn--equal btn--transparent color-gray-800 card-toggle-js"> </a>
        </div>
    </div>
    <div class="card-box__body card-target-js">
        <div class="card-controls">
            <?php
            $this->includeTemplate('lectures/navigation.php', [
                'active' => 'resrc',
                'lectureId' => $lectureId,
                'sectionId' => $lecture['lecture_section_id'],
            ]);
            ?>
        </div>
        <div class="card-controls-content">
            <div class="card-controls-view controls-tabs-view-js">
                <div class="page">
                    <div class="page__head pt-0">
                        <div class="step-view">
                            <div class="step-view__large">
                                <?php
                                $frm->setFormTagAttribute('class', 'form');
                                $frm->setFormTagAttribute('id', 'frmLectureResrc' . $time);
                                $fld = $frm->getField('resource_files[]');
                                $fld->setFieldTagAttribute('onchange', 'uploadResource(\'frmLectureResrc' . $time . '\');');
                                $label = Label::getLabel('LBL_ALLOWED_SIZE_{filesize}_MB._SUPPORTED_FILE_FORMATS_{extensions}');
                                $label = str_replace(
                                    ['{filesize}', '{extensions}'],
                                    [MyUtility::convertBitesToMb($filesize), implode(', ', Resource::ALLOWED_EXTENSIONS)],
                                    $label
                                );
                                $fld->htmlAfterField = '<small>' . $label . '</small>';
                                echo $frm->getFormTag();
                                ?>
                                <div class="field-set">
                                    <div class="caption-wraper">
                                        <label class="field_label">
                                            <?php echo $fld->getCaption(); ?>
                                        </label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <?php echo $fld->getHtml(); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                echo $frm->getFieldHtml('lecsrc_lecture_id');
                                echo $frm->getFieldHtml('lecsrc_course_id');
                                echo $frm->getFieldHtml('lecsrc_type');
                                echo $frm->getFieldHtml('lecsrc_id');
                                ?>
                                </form>
                                <?php echo $frm->getExternalJs(); ?>
                            </div>
                            <div class="step-view__small">
                                <div class="field-set">
                                    <div class="caption-wraper  d-none d-sm-block">
                                        <label class="field_label"></label>
                                    </div>
                                    <div class="field-wraper">
                                        <div class="field_cover">
                                            <a href="javascript:void(0);" onclick="getResources('<?php echo $lectureId; ?>')" class="btn color-secondary btn--bordered d-flex">
                                                <svg class="icon icon--uploader me-2">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#uploader"></use>
                                                </svg>
                                                <?php echo Label::getLabel('LBL_ADD_FROM_LIBRARY') ?>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="page__body">
                        <div class="table-scroll">
                            <table class="table table--styled table--responsive">
                                <tr class="title-row">
                                    <th><?php echo Label::getLabel('LBL_FILE_NAME'); ?></th>
                                    <th><?php echo Label::getLabel('LBL_TYPE'); ?></th>
                                    <th><?php echo Label::getLabel('LBL_ACTION'); ?></th>
                                </tr>
                                <?php
                                if (count($resources) > 0) {
                                    foreach ($resources as $resrc) {
                                        $icon = Resource::getFileIcon($resrc['resrc_type']); ?>
                                        <tr>
                                            <td>
                                                <div class="flex-cell">
                                                    <div class="flex-cell__label">
                                                        <?php echo Label::getLabel('LBL_FILE_NAME:'); ?>
                                                    </div>
                                                    <div class="flex-cell__content">
                                                        <div class="file-attachment">
                                                            <div class="d-flex">
                                                                <div class="file-attachment__media d-none d-sm-flex">
                                                                    <svg class="attached-media">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#<?php echo $icon ?>"></use>
                                                                    </svg>
                                                                    <?php //echo Common::getFileTypeIcon($resrc['resrc_name']); 
                                                                    ?>
                                                                </div>
                                                                <div class="file-attachment__content">
                                                                    <p class="mb-1 bold-600 color-black">
                                                                        <?php echo $resrc['resrc_name']; ?>
                                                                    </p>
                                                                    <span class="m-0 style-italic me-3 color-gray-900">
                                                                        <?php echo $resrc['resrc_size']; ?>
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex-cell">
                                                    <div class="flex-cell__label">
                                                        <?php echo Label::getLabel('LBL_TYPE:'); ?>
                                                    </div>
                                                    <div class="flex-cell__content">
                                                        <div style="max-width: 250px;">
                                                            <?php echo strtoupper($resrc['resrc_type']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="flex-cell">
                                                    <div class="flex-cell__label">
                                                        <?php echo Label::getLabel('LBL_ACTION:'); ?>
                                                    </div>
                                                    <div class="flex-cell__content">
                                                        <div class="actions-group">
                                                            <a href="javascript:void(0);" onclick="removeLectureResrc('<?php echo $resrc['lecsrc_id']; ?>', '<?php echo $resrc['lecsrc_lecture_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                                                <svg class="icon icon--issue icon--small">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#delete-icon"></use>
                                                                </svg>
                                                                <div class="tooltip tooltip--top bg-black">
                                                                    <?php echo Label::getLabel('LBL_DELETE'); ?>
                                                                </div>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                    }
                                } else { ?>
                                    <tr>
                                        <td colspan="4">
                                            <div class="flex-cell">
                                                <div class="flex-cell__content">
                                                    <?php echo Label::getLabel('LBL_NO_RESOURCE_UPLOADED'); ?>
                                                </div>
                                            </div>
                                        </td>
                                    </tr><?php
                                        }
                                            ?>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>