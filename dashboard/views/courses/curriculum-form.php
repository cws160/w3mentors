<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$fld = $frm->getField('btn_next');
$fld->setFieldTagAttribute('onclick', 'settingsForm()');
$fld->setFieldTagAttribute('class', 'btn btn--primary -no-border');
?>
<div class="page__body">
    <!-- [ PAGE PANEL ========= -->
    <div class="page-layout">
        <div class="page-layout__small">
            <?php echo $this->includeTemplate('courses/sidebar.php', ['frm' => $frm, 'active' => 4, 'courseId' => $courseId]) ?>
        </div>
        <div class="page-layout__large">
            <div class="box-panel mb-4">
                <div class="box-panel__head border-bottom">
                    <div class="d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4><?php echo Label::getLabel('LBL_MANAGE_CURRICULUM'); ?></h4>
                        </div>
                        <div>
                            <div class="buttons-group d-flex align-items-center gap-2">
                                <a href="javascript:void(0);" onclick="sectionForm()" class="btn btn--secondary btn--block-mobile">
                                    <svg class="icon">
                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#plus-more"></use>
                                    </svg>
                                    <?php echo Label::getLabel('LBL_ADD_SECTION'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="box-panel__body">
                    <div class="box-panel__container">
                        <p><?php echo Label::getLabel('LBL_CURRICULUM_SHORT_INFO_1'); ?></p>
                        <p><?php echo Label::getLabel('LBL_CURRICULUM_SHORT_INFO_2'); ?></p>
                    </div>
                </div>
            </div>
            <div id="sectionAreaJs">
            </div>
            <div id="sectionFormAreaJs">
            </div>
        </div>
    </div>
</div>