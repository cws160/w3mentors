<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('id', 'frmCourses');
$frm->setFormTagAttribute('onsubmit', 'setupIntendedLearners(this); return false;');
$typeLearning = IntendedLearner::TYPE_LEARNING;
$typeRequirements = IntendedLearner::TYPE_REQUIREMENTS;
$typeLearners = IntendedLearner::TYPE_LEARNERS;
$intendedLearnertypes = IntendedLearner::getTypes();
$typesSubTitles = IntendedLearner::getTypesSubTitles();
($frm->getField('type_learnings[]'))->setFieldTagAttribute('placeholder', $intendedLearnertypes[$typeLearning]);
($frm->getField('type_requirements[]'))->setFieldTagAttribute('placeholder', $intendedLearnertypes[$typeRequirements]);
($frm->getField('type_learners[]'))->setFieldTagAttribute('placeholder', $intendedLearnertypes[$typeLearners]);
$textLength = 155;
?>
<?php echo $frm->getFormTag(); ?>
<div class="page-layout">
    <div class="page-layout__small">
        <?php echo $this->includeTemplate('courses/sidebar.php', ['frm' => $frm, 'active' => 2, 'courseId' => $courseId]) ?>
    </div>
    <div class="page-layout__large">
        <div class="box-panel">
            <div class="box-panel__head border-bottom">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4><?php echo Label::getLabel('LBL_INTENDED_LEARNERS'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="box-panel__body">
                <div class="box-panel__container">
                    <p><?php echo Label::getLabel('LBL_INTENDED_LEARNERS_SHORT_INFO'); ?></p>
                    <div class="fields-collection mt-5 typesAreaJs<?php echo $typeLearning ?>">
                        <div class="fields-collection__head">
                            <h6 class="mb-2"><?php echo $intendedLearnertypes[$typeLearning]; ?></h6>
                            <p class="style-italic">
                                <?php echo $typesSubTitles[$typeLearning]; ?>
                            </p>
                        </div>
                        <div class="fields-collection__body mt-5 typesListJs sortableLearningJs">
                            <?php
                            $i = 1;
                            if (isset($responses[$typeLearning]) && count($responses[$typeLearning]) > 0) {
                                $learningField = $frm->getField('type_learnings[]');
                                $idsFld = $frm->getField('type_learnings_ids[]');
                                $idsFld->setFieldTagAttribute('class', 'sortable_ids');
                                foreach ($responses[$typeLearning] as $response) {
                                    $learningField->value = CommonHelper::renderHtml($response['coinle_response']);
                                    $idsFld->value = $response['coinle_id']; ?>
                                    <div class="sort-row typeFieldsJs">
                                        <div class="sort-row__item">
                                            <div class="sort-row__field">
                                                <?php $strLen = $textLength - strlen($learningField->value); ?>
                                                <div class="field-count" data-length="<?php echo $textLength ?>" field-count="<?php echo $strLen; ?>">
                                                    <?php echo $frm->getFieldHtml('type_learnings[]') ?>
                                                </div>
                                            </div>
                                            <div class="sort-row__actions">
                                                <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move sortHandlerJs">
                                                    <svg class="icon icon--sorting">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
                                                    </svg>
                                                </a>
                                                <a href="javascript:void(0);" class="btn btn--equal btn--transparent color-gray-1000 removeRespJs" <?php echo ($i > 1) ? 'onclick="removeIntendedLearner(this, \'' . $response['coinle_id'] . '\');"' : 'style="display:none;"'; ?>>
                                                    <svg class="icon icon--sorting">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#bin-icon"></use>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                        <?php echo $frm->getFieldHtml('type_learnings_ids[]'); ?>
                                    </div>
                                <?php
                                    $i++;
                                }
                            }
                            if ($i == 1) { ?>
                                <div class="sort-row typeFieldsJs">
                                    <div class="sort-row__item">
                                        <div class="sort-row__field">
                                            <div class="field-count" data-length="<?php echo $textLength ?>" field-count="<?php echo $textLength ?>">
                                                <?php
                                                $learningField = $frm->getField('type_learnings[]');
                                                $learningField->setFieldTagAttribute('class', 'field-count__wrap');
                                                $learningField->value = '';
                                                echo $frm->getFieldHtml('type_learnings[]');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="sort-row__actions">
                                            <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move">
                                                <svg class="icon icon--sorting">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
                                                </svg>
                                            </a>
                                            <a href="javascript:void(0);" style="display:none;" class="btn btn--equal btn--transparent color-gray-1000 removeRespJs">
                                                <svg class="icon icon--sorting">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#bin-icon"></use>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="fields-collection__footer mt-3">
                            <a href="javascript:void(0)" onclick="addFld('<?php echo $typeLearning ?>')" class="icon-link">
                                <svg class="icon icon--more me-2">
                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#more-icon"></use>
                                </svg>
                                <?php echo Label::getLabel('LBL_ADD_MORE_TO_YOUR_RESPONSE'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="fields-collection mt-5 typesAreaJs<?php echo $typeRequirements ?>">
                        <div class="fields-collection__head">
                            <h6 class="mb-2">
                                <?php echo $intendedLearnertypes[$typeRequirements]; ?>
                            </h6>
                            <p class="style-italic">
                                <?php echo $typesSubTitles[$typeRequirements]; ?>
                            </p>
                        </div>
                        <div class="fields-collection__body mt-5 typesListJs sortableRequirementJs">
                            <?php
                            $i = 1;
                            if (isset($responses[$typeRequirements]) && count($responses[$typeRequirements]) > 0) {
                                $reqField = $frm->getField('type_requirements[]');
                                $idsFld = $frm->getField('type_requirements_ids[]');
                                $idsFld->setFieldTagAttribute('class', 'sortable_ids');
                                foreach ($responses[$typeRequirements] as $response) {
                                    $reqField->value = CommonHelper::renderHtml($response['coinle_response']);
                                    $idsFld->value = $response['coinle_id']; ?>
                                    <div class="sort-row typeFieldsJs">
                                        <div class="sort-row__item">
                                            <div class="sort-row__field">
                                                <?php $strLen = $textLength - strlen($reqField->value); ?>
                                                <div class="field-count" data-length="<?php echo $textLength ?>" field-count="<?php echo $strLen; ?>">
                                                    <?php echo $frm->getFieldHtml('type_requirements[]'); ?>
                                                </div>
                                            </div>
                                            <div class="sort-row__actions">
                                                <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move sortHandlerJs">
                                                    <svg class="icon icon--sorting">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
                                                    </svg>
                                                </a>
                                                <a href="javascript:void(0);" class="btn btn--equal btn--transparent color-gray-1000 removeRespJs" <?php echo ($i > 1) ? 'onclick="removeIntendedLearner(this, \'' . $response['coinle_id'] . '\');"' : 'style="display:none;"' ?>>
                                                    <svg class="icon icon--sorting">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#bin-icon"></use>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                        <?php echo $frm->getFieldHtml('type_requirements_ids[]'); ?>
                                    </div><?php
                                            $i++;
                                        }
                                    }
                                    if ($i == 1) { ?>
                                <div class="sort-row typeFieldsJs">
                                    <div class="sort-row__item">
                                        <div class="sort-row__field">
                                            <div class="field-count" data-length="<?php echo $textLength ?>" field-count="<?php echo $textLength ?>">
                                                <?php
                                                $reqField = $frm->getField('type_requirements[]');
                                                $reqField->setFieldTagAttribute('class', 'field-count__wrap');
                                                $reqField->value = '';
                                                echo $frm->getFieldHtml('type_requirements[]');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="sort-row__actions">
                                            <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move">
                                                <svg class="icon icon--sorting">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
                                                </svg>
                                            </a>
                                            <a href="javascript:void(0);" style="display:none;" class="btn btn--equal btn--transparent color-gray-1000 removeRespJs">
                                                <svg class="icon icon--sorting">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#bin-icon"></use>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div><?php
                                    }
                                        ?>
                        </div>
                        <div class="fields-collection__footer mt-3">
                            <a href="javascript:void(0)" onclick="addFld('<?php echo $typeRequirements ?>')" class="icon-link">
                                <svg class="icon icon--more me-2">
                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#more-icon"></use>
                                </svg>
                                <?php echo Label::getLabel('LBL_ADD_MORE_TO_YOUR_RESPONSE'); ?>
                            </a>
                        </div>
                    </div>
                    <div class="fields-collection mt-5 typesAreaJs<?php echo $typeLearners ?>">
                        <div class="fields-collection__head">
                            <h6 class="mb-2"><?php echo $intendedLearnertypes[$typeLearners]; ?></h6>
                            <p class="style-italic">
                                <?php echo $typesSubTitles[$typeLearners]; ?>
                            </p>
                        </div>
                        <div class="fields-collection__body mt-5 typesListJs sortableLearnerJs">
                            <?php
                            $i = 1;
                            if (isset($responses[$typeLearners]) && count($responses[$typeLearners]) > 0) {
                                $learnerField = $frm->getField('type_learners[]');
                                $idsFld = $frm->getField('type_learners_ids[]');
                                $idsFld->setFieldTagAttribute('class', 'sortable_ids');
                                foreach ($responses[$typeLearners] as $response) {
                                    $learnerField->value = CommonHelper::renderHtml($response['coinle_response']);
                                    $idsFld->value = $response['coinle_id']; ?>
                                    <div class="sort-row typeFieldsJs">
                                        <div class="sort-row__item">
                                            <div class="sort-row__field">
                                                <?php $strLen = $textLength - strlen($learnerField->value); ?>
                                                <div class="field-count" data-length="<?php echo $textLength; ?>" field-count="<?php echo $strLen; ?>">
                                                    <?php echo $frm->getFieldHtml('type_learners[]'); ?>
                                                </div>
                                            </div>
                                            <div class="sort-row__actions">
                                                <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move sortHandlerJs">
                                                    <svg class="icon icon--sorting">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
                                                    </svg>
                                                </a>
                                                <a href="javascript:void(0);" class="btn btn--equal btn--transparent color-gray-1000 removeRespJs" <?php echo ($i > 1) ? 'onclick="removeIntendedLearner(this, \'' . $response['coinle_id'] . '\');"' : 'style="display:none;"'; ?>>
                                                    <svg class="icon icon--sorting">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#bin-icon"></use>
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                        <?php echo $idsFld->getHtml(); ?>
                                    </div><?php
                                            $i++;
                                        }
                                    }
                                    if ($i == 1) { ?>
                                <div class="sort-row typeFieldsJs">
                                    <div class="sort-row__item">
                                        <div class="sort-row__field">
                                            <div class="field-count" data-length="<?php echo $textLength; ?>" field-count="<?php echo $textLength; ?>">
                                                <?php
                                                $learnerField = $frm->getField('type_learners[]');
                                                $learnerField->setFieldTagAttribute('class', 'field-count__wrap');
                                                $learnerField->value = '';
                                                echo $frm->getFieldHtml('type_learners[]');
                                                ?>
                                            </div>
                                        </div>
                                        <div class="sort-row__actions">
                                            <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-1000 cursor-move">
                                                <svg class="icon icon--sorting">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#sorting-icon"></use>
                                                </svg>
                                            </a>
                                            <a href="javascript:void(0);" style="display:none;" class="btn btn--equal btn--transparent color-gray-1000 removeRespJs">
                                                <svg class="icon icon--sorting">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#bin-icon"></use>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div><?php
                                    }
                                        ?>
                        </div>
                        <div class="fields-collection__footer mt-3">
                            <a href="javascript:void(0)" onclick="addFld('<?php echo $typeLearners ?>')" class="icon-link">
                                <svg class="icon icon--more me-2">
                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#more-icon"></use>
                                </svg>
                                <?php echo Label::getLabel('LBL_ADD_MORE_TO_YOUR_RESPONSE'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php echo $frm->getFieldHtml('course_id'); ?>
</form>
<?php echo $frm->getExternalJS(); ?>
<script type="text/javascript">
    $(function() {
        $(".sortableLearningJs, .sortableRequirementJs, .sortableLearnerJs").sortable({
            handle: ".sortHandlerJs",
            update: function(event, ui) {
                updateIntendedOrder();
            }
        });
    });
</script>