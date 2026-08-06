<div class="page-layout__sticky">
    <div class="page-steps mb-4 tabs-scrollable-js">
        <ul>
            <li class="<?php echo ($active == 1) ? 'is-active' : ''; ?> general-info-js is-progress">
                <a href="javascript:void(0)" <?php if ($courseId > 0) { ?> onclick="generalForm();" <?php } ?>>
                    <?php echo Label::getLabel('LBL_BASIC_DETAILS'); ?>
                    <span class="step-sign"></span>
                </a>
            </li>
            <li class="intended-learner-js <?php echo ($active == 2) ? 'is-active' : ''; ?> is-progress">
                <a href=" javascript:void(0)" <?php if ($courseId > 0) { ?> onclick="intendedLearnersForm();" <?php } ?>>
                    <?php echo Label::getLabel('LBL_INTENDED_LEARNERS'); ?>
                    <span class="step-sign"></span>
                </a>
            </li>
            <li class="course-price-js <?php echo ($active == 3) ? 'is-active' : ''; ?> is-progress">
                <a href="javascript:void(0)" <?php if ($courseId > 0) { ?> onclick="priceForm();" <?php } ?>>
                    <?php echo Label::getLabel('LBL_PRICE'); ?>
                    <span class="step-sign"></span>
                </a>
            </li>
            <li class="curriculum-js <?php echo ($active == 4) ? 'is-active' : ''; ?> is-progress">
                <a href=" javascript:void(0)" <?php if ($courseId > 0) { ?> onclick="curriculumForm();" <?php } ?>>
                    <?php echo Label::getLabel('LBL_CURRICULUM'); ?>
                    <span class="step-sign"></span>
                </a>
            </li>
            <li class="course-setting-js <?php echo ($active == 5) ? 'is-active' : ''; ?> is-progress">
                <a href="javascript:void(0)" <?php if ($courseId > 0) { ?> onclick="settingsForm();" <?php } ?>>
                    <?php echo Label::getLabel('LBL_SETTINGS'); ?>
                    <span class="step-sign"></span>
                </a>
            </li>
        </ul>
    </div>
    <div class="page-actions">
        <?php
        $class = '';
        if ($fld = $frm->getField('btn_save')) {
            $class = 'mt-2'; ?>
            <div class="page-actions__group">
                <?php
                $fld->setFieldTagAttribute('class', 'btn btn--primary');
                echo $frm->getFieldHtml('btn_save');
                ?>
            </div>
        <?php } ?>
        <div class="page-actions__group <?php echo $class ?>">
            <?php
            if ($fld = $frm->getField('btn_submit')) {
                $fld->setFieldTagAttribute('class', 'btn btn--primary');
                echo $frm->getFieldHtml('btn_submit');
            }

            if ($frm->getField('btn_next')) {
                echo $frm->getFieldHtml('btn_next');
            }

            if ($fld = $frm->getField('btn_approval')) {
                $fld->setFieldTagAttribute('class', 'btn btn--primary -no-border d-none btnApprovalJs');
                echo $frm->getFieldHtml('btn_approval');
            }
            ?>
        </div>
    </div>
</div>