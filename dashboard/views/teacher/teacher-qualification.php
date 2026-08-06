<div class="content-panel__head">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5><?php echo Label::getLabel('LBL_MANAGE_EXPERINCE'); ?></h5>
        </div>
        <div><a href="javascript:void(0);" onclick="teacherQualificationForm(0);" class="btn btn--small btn--bordered color-secondary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a></div>
    </div>
</div>
<div class="content-panel__body">
    <div class="form">
        <div class="form__body p-0">
            <div class="table-scroll">
                <table class="table table--bordered table--responsive">
                    <tr class="title-row">
                        <th><?php echo $lblRI = Label::getLabel('LBL_RESUME_INFORMATION'); ?></th>
                        <th><?php echo $lbllSE = Label::getLabel('LBL_START/END'); ?></th>
                        <th><?php echo $lblAttach = Label::getLabel('LBL_ATTACHMENT'); ?></th>
                        <th><?php echo $lblAction = Label::getLabel('LBL_ACTIONS'); ?></th>
                    </tr>
                    <?php foreach ($qualificationData as $qualificationData) { ?>
                        <tr id="qualification-<?php echo $qualificationData['uqualification_id']; ?>">
                            <td>
                                <div class="flex-cell">
                                    <div class="flex-cell__label"><?php echo $lblRI; ?></div>
                                    <div class="flex-cell__content">
                                        <div class="data-group">
                                            <span class="bold-600"><?php echo $qualificationData['uqualification_title']; ?></span><br>
                                            <span><?php echo Label::getLabel('LBL_LOCATION') . ' - ' . $qualificationData['uqualification_institute_address']; ?></span><br>
                                            <span><?php echo Label::getLabel('LBL_INSTITUTION') . ' - ' . $qualificationData['uqualification_institute_name']; ?></span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex-cell">
                                    <div class="flex-cell__label"><?php echo $lbllSE; ?></div>
                                    <div class="flex-cell__content"><?php echo $qualificationData['uqualification_start_year']; ?> - <?php echo $qualificationData['uqualification_end_year']; ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="flex-cell">
                                    <div class="flex-cell__label"><?php echo $lblAttach; ?></div>
                                    <div class="flex-cell__content">
                                        <?php
                                        if (empty($qualificationData['file_name'])) {
                                            echo Label::getLabel('LBL_NA');
                                        } else {
                                        ?>
                                            <a href="<?php echo MyUtility::makeFullUrl('Teacher', 'downloadQualification', [$qualificationData['uqualification_id']]); ?>" target="_blank" class="attachment-file">
                                                <svg class="icon icon--issue icon--attachement icon--small color-primary">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#attach' ?>"></use>
                                                </svg>
                                                <?php echo $qualificationData['file_name']; ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex-cell">
                                    <div class="flex-cell__label"><?php echo $lblAction; ?></div>
                                    <div class="flex-cell__content">
                                        <div class="actions-group">
                                            <a href="javascript:void(0);" onclick="teacherQualificationForm('<?php echo $qualificationData['uqualification_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                                <svg class="icon icon--issue icon--small">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#edit'; ?>"></use>
                                                </svg>
                                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_EDIT'); ?></div>
                                            </a>
                                            <a href="javascript:void(0);" onclick="deleteTeacherQualification('<?php echo $qualificationData['uqualification_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                                <svg class="icon icon--issue icon--small">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#trash'; ?>"></use>
                                                </svg>
                                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_DELETE'); ?></div>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php } ?>
                </table>
            </div>
        </div>
        <div class="form__actions">
            <div class="d-flex align-items-center gap-1">
                <input type="button" value="<?php echo label::getLabel('LBL_next'); ?>" onclick="$('.teacher-preferences-js').trigger('click');">
            </div>
        </div>
    </div>
</div>