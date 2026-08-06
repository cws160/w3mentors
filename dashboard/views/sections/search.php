<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!-- [ SECTION TITLE ========= -->

<?php
$sectionOrder = $lectureOrder = 1;
if (count($sectionsList) > 0) {
    foreach ($sectionsList as $section) { ?>
        <div class="card-panel" id="sectionId<?php echo $section['section_id']; ?>" data-id="<?php echo $section['section_id']; ?>">
            <div class="card-panel__head">
                <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move sortHandlerJs">
                    <svg class="icon icon--sorting">
                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#sorting-icon"></use>
                    </svg>
                </a>
                <div class="card-title">
                    <span class="card-title__label"><?php echo Label::getLabel('LBL_SECTION:') . ' ' . $section['section_order']; ?> </span>
                    <div class="card-title__meta">
                        <div class="card-title__content edit-title-js sectionCardJs">
                            <span class="card-title__caption"><?php echo $section['section_title'] ?></span>
                            <!-- [ SECTION ACTIONS ========= -->
                            <div class="card-title__actions">
                                <a href="javascript:void(0);" onclick="sectionForm('<?php echo $section['section_id'] ?>')" class="btn btn--equal btn--transparent is-hover color-gray-1000 edit-js">
                                    <svg class="icon icon--edit icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#edit"></use>
                                    </svg>
                                    <span class="btn-label"><?php echo Label::getLabel('LBL_EDIT'); ?></span>

                                    <div class="tooltip tooltip--top bg-black">
                                        <?php echo Label::getLabel('LBL_EDIT'); ?>
                                    </div>
                                </a>
                                <?php if ($courseStatus != Course::PUBLISHED) { ?>
                                    <a href="javascript:void(0);" onclick="removeSection('<?php echo $section['section_id']; ?>')" class="btn btn--equal btn--transparent is-hover color-gray-1000">
                                        <svg class="icon icon--edit icon--small">
                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#trash"></use>
                                        </svg>
                                        <span class="btn-label">
                                            <?php echo Label::getLabel('LBL_DELETE'); ?>
                                        </span>
                                        <div class="tooltip tooltip--top bg-black">
                                            <?php echo Label::getLabel('LBL_DELETE'); ?>
                                        </div>
                                    </a>
                                <?php } ?>
                            </div>
                            <!-- ] -->
                        </div>
                        <div class="card-title__form edit-form-js sectionEditCardJs">
                        </div>
                    </div>
                    <div class="card-options card-options--positioned sectionCardJs">
                        <a href="javascript:void(0);" onclick="lectureForm('<?php echo $section['section_id']; ?>');" class="card-options__trigger btn btn--equal btn--transparent color-gray-800 is-hover">
                            <svg class="icon icon--plus">
                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#add-more"></use>
                            </svg>
                            <div class="tooltip tooltip--top bg-black">
                                <?php echo Label::getLabel('LBL_ADD_LECTURE'); ?>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            <?php $lectureCount = (isset($section['lectures'])) ? count($section['lectures']) : 0; ?>
            <div class="card-panel__body lecturesListJs" style="display: <?php echo ($lectureCount > 0) ? 'block' : 'none'; ?>">
                <?php
                if ($lectureCount > 0) {
                    foreach ($section['lectures'] as $lecture) { ?>
                        <div class="card-box card-group-js lecturePanelJs" id="sectionLectures<?php echo $lecture['lecture_id'] ?>" data-id="<?php echo $lecture['lecture_id'] ?>">
                            <div class="card-box__head">
                                <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move sortHandlerJs">
                                    <svg class="icon icon--sorting">
                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
                                    </svg>
                                </a>
                                <div class="card-title">
                                    <span class="card-title__label">
                                        <?php echo Label::getLabel('LBL_LECTURE') . ': ' . $lecture['lecture_order']; ?>
                                    </span>
                                    <div class="card-title__meta">
                                        <div class="card-title__content">
                                            <span class="card-title__caption">
                                                <?php echo $lecture['lecture_title'] ?>
                                            </span>
                                            <!-- [ LECTURE ACTIONS ========= -->
                                            <div class="card-title__actions">
                                                <a href="javascript:void(0);" onclick="lectureForm('<?php echo $lecture['lecture_section_id'] ?>', '<?php echo $lecture['lecture_id'] ?>')" class="btn btn--equal btn--transparent is-hover color-gray-1000 card-toggle-js">
                                                    <svg class="icon icon--edit icon--small">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#edit"></use>
                                                    </svg>
                                                    <span class="btn-label"><?php echo Label::getLabel('LBL_EDIT'); ?></span>

                                                    <div class="tooltip tooltip--top bg-black">
                                                        <?php echo Label::getLabel('LBL_EDIT'); ?>
                                                    </div>
                                                </a>
                                                <a href="javascript:void(0);" onclick="removeLecture('<?php echo $lecture['lecture_section_id'] ?>', '<?php echo $lecture['lecture_id'] ?>')" class="btn btn--equal btn--transparent is-hover color-gray-1000">
                                                    <svg class="icon icon--edit icon--small">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#trash"></use>
                                                    </svg>
                                                    <span class="btn-label"> <?php echo Label::getLabel('LBL_DELETE'); ?></span>
                                                    <div class="tooltip tooltip--top bg-black">
                                                        <?php echo Label::getLabel('LBL_DELETE'); ?>
                                                    </div>
                                                </a>
                                            </div>
                                            <!-- ] -->
                                        </div>
                                    </div>
                                </div>
                                <div class="card-options card-options--positioned">
                                    <a href="javascript:void(0);" onclick="lectureForm('<?php echo $lecture['lecture_section_id'] ?>', '<?php echo $lecture['lecture_id'] ?>')" class="card-toggle btn btn--equal btn--transparent color-gray-800 card-toggle-js"> </a>
                                </div>
                            </div>
                        </div>
                <?php
                        if ($lectureOrder < $lecture['lecture_order']) {
                            $lectureOrder = $lecture['lecture_order'];
                        }
                    }
                }
                ?>
            </div>
        </div><?php
                $sectionOrder = $section['section_order'] + 1;
            }
        } else {
            $variables = [
                'msgHeading' => '<a href="javascript:void(0);" onclick="sectionForm()">' . Label::getLabel('LBL_CLICK_TO_ADD_SECTIONS') . '</a>',
            ];
            $this->includeTemplate('_partial/no-record-found.php', $variables, false);
        } ?>
<input type="hidden" id="courseSectionOrderJs" value="<?php echo $sectionOrder; ?>">
<input type="hidden" id="lectureOrderJs" value="<?php echo $lectureOrder + 1; ?>">
<!-- ] -->

<script type="text/javascript">
    $(function() {
        $("#sectionAreaJs").sortable({
            handle: '.sortHandlerJs',
            update: function(event, ui) {
                updateSectionOrder();
            }
        });
        $(".lecturesListJs").sortable({
            handle: '.sortHandlerJs',
            update: function(event, ui) {
                updateLectureOrder();
            }
        });
    });
</script>