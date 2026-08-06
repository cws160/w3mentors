<div class="page-layout__sticky">
    <div class="page-steps mb-4 tabs-scrollable-js">
        <ul>
            <li class="generalTabJs is-error <?php echo ($active == 1) ? 'is-active' : ''; ?>" <?php echo ($quizId > 0 && $active != 1) ? 'onclick="form(\'' . $quizId . '\');"' : ''; ?>>
                <a href="javascript:void(0)"><?php echo Label::getLabel('LBL_General'); ?>
                    <span class="step-sign"></span>
                </a>
            </li>
            <li class="questionsTabJs is-error <?php echo ($active == 2) ? 'is-active' : '';  ?>">
                <a href="javascript:void(0)" <?php echo ($quizId > 0 && $active != 2) ? 'onclick="questions(\'' . $quizId . '\');"' : ''; ?>>
                    <?php echo Label::getLabel('LBL_QUESTION_BANK'); ?><span class="step-sign"></span>
                </a>
            </li>
            <li class="settingsTabJs is-error <?php echo ($active == 3) ? 'is-active' : ''; ?>">
                <a href="javascript:void(0)" <?php echo ($quizId > 0 && $active != 3) ? 'onclick="settings(\'' . $quizId . '\');"' : ''; ?>>
                    <?php echo Label::getLabel('LBL_SETTINGS'); ?><span class="step-sign"></span>
                </a>
            </li>
        </ul>
    </div>
    <div class="page-actions">
        <?php if (isset($frm)) { ?>
            <div class="page-actions__group">
                <?php
                $fld = $frm->getField('btn_submit');
                $fld->setFieldTagAttribute('class', 'btn btn--primary');
                echo $frm->getFieldHtml('btn_submit');
                ?>
            </div>
        <?php } ?>
        <?php if (isset($next)) { ?>
            <div class="page-actions__group">
                <a class="btn btn--primary" href=" javascript:void(0);" onclick="settingsForm('<?php echo $count ?>', '<?php echo $quizId ?>');">
                    <?php echo Label::getLabel('LBL_SAVE_&_NEXT') ?>
                </a>
            </div>
        <?php } ?>
    </div>
</div>