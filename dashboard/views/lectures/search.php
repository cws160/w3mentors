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
                            <span class="btn-label"><?php echo Label::getLabel('LBL_DELETE'); ?></span>
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