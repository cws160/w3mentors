<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('id', 'addquestion');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 5;
$fld = $frm->getField('fque_tags');
$fld->htmlAfterField = '<a class="rm_btn rm_btn--small btn btn--bordered color-secondary btn--block-mobile ms-3" title="' . Label::getLabel('LBL_Forum_Request_new_tag') . '" href="javascript:void(0);" onclick="getApprovalRequestForm(); return false;">
<svg data-type="new" class="icon icon--request-tag icon--small me-2">
    <use xlink:href="' . CONF_WEBROOT_FRONT_URL . 'images/forum/sprite.svg#request-tag"></use>
</svg>' . Label::getLabel('LBL_Request_new_tag') . '
</a>';
$fld->addFieldTagAttribute('id', 'fque_tags');
$fld->addFieldTagAttribute('data-ids', '');
$selectedTags = '';

$slugFld = $frm->getField('fque_slug');
$slugFld->setFieldTagAttribute('onchange', 'formatSlug(this);');
?>
<div class="container container--small">
    <div class="page__head">
        <a href="<?php echo MyUtility::makeUrl('Forum'); ?>" class="page-back">
            <svg class="icon icon--back me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M7.828,11H20v2H7.828l5.364,5.364-1.414,1.414L4,12l7.778-7.778,1.414,1.414Z" />
            </svg>
            <?php echo Label::getLabel('LBL_Back_to_questions'); ?>
        </a>
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_WHAT_IS_ON_YOUR_MIND?'); ?></h1>
            </div>
        </div>
    </div>
    <div class="page__body">
        <div class="page-panel">
            <div class="page-panel__body">
                <?php
                $cls = 'alert--success';
                $statusMsg = '';
                if (ForumQuestion::FORUM_QUE_RESOLVED == $data['fque_status']) {
                    $statusMsg = 'Resolved Marked';
                }
                if (ForumQuestion::FORUM_QUE_SPAMMED == $data['fque_status']) {
                    $cls = 'alert--danger';
                    $statusMsg = 'Spammed Marked';
                }
                if ('' != $statusMsg) {
                ?>
                    <div class="alert <?php echo $cls; ?>">
                        <?php echo $statusMsg; ?>
                    </div>
                <?php
                }
                echo $frm->getFormTag();
                ?>
                <div class="row">
                    <div class="col-md-12">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php
                                    $fld = $frm->getField('fque_title');
                                    $fld->setFieldTagAttribute('class', 'field-count__wrap');
                                    $fld->setFieldTagAttribute('id', 'que_title');
                                    $txt = Label::getLabel('LBL_Title_Must_Be_Between_{min-length}_And_{max-length}');
                                    $txt = CommonHelper::replaceStringData($txt, [
                                        '{min-length}' => ForumQuestion::QUEST_TITLE_MIN_LENGTH,
                                        '{max-length}' => ForumQuestion::QUEST_TITLE_MAX_LENGTH
                                    ]);
                                    $fld->htmlAfterField = '<br /><small>' . $txt . '</small>';
                                    echo $fld->getCaption();
                                    if ($fld->requirement->isRequired()) {
                                    ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover field-count" field-count="<?php echo (array_key_exists('fque_title', $data) ? ForumQuestion::QUEST_TITLE_MAX_LENGTH - strlen($data['fque_title']) : ForumQuestion::QUEST_TITLE_MAX_LENGTH); ?>" data-length=<?php echo ForumQuestion::QUEST_TITLE_MAX_LENGTH; ?>>
                                    <?php echo $fld->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php
                                    $fld = $frm->getField('fque_slug');
                                    $fld->setFieldTagAttribute('class', 'field-count__wrap');
                                    $fld->setFieldTagAttribute('id', 'fque_slug');
                                    $txt = Label::getLabel('LBL_Slug_Must_Be_Between_{min-length}_And_{max-length}');
                                    $txt = CommonHelper::replaceStringData($txt, [
                                        '{min-length}' => ForumQuestion::QUEST_TITLE_MIN_LENGTH,
                                        '{max-length}' => ForumQuestion::QUEST_TITLE_MAX_LENGTH
                                    ]);
                                    $fld->htmlAfterField = '<br /><small>' . $txt . '</small>';
                                    echo $fld->getCaption();
                                    if ($fld->requirement->isRequired()) {
                                    ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover field-count" field-count="<?php echo (array_key_exists('fque_slug', $data) ? ForumQuestion::QUEST_TITLE_MAX_LENGTH - strlen($data['fque_slug']) : ForumQuestion::QUEST_TITLE_MAX_LENGTH); ?>" data-length=<?php echo ForumQuestion::QUEST_TITLE_MAX_LENGTH; ?>>
                                    <?php echo $fld->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php
                                    $fld = $frm->getField('fque_lang_id');
                                    echo $fld->getCaption();
                                    ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $fld->getHtml(); ?>
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
                                    <?php
                                    $fld = $frm->getField('fque_description');
                                    echo $fld->getCaption();
                                    if ($fld->requirement->isRequired()) {
                                    ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $fld->getHtml(); ?>
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
                                    <?php
                                    $fld = $frm->getField('fque_tags');
                                    echo $fld->getCaption();
                                    ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover d-sm-flex align-items-center">
                                    <?php echo $fld->getHtml(); ?>
                                </div>
                                <div id="question-tags" class="tags pt-2 pb-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php
                                    $fld = $frm->getField('fque_status');
                                    $canChangeStatus = true;
                                    if (
                                        $data['fque_status'] == ForumQuestion::FORUM_QUE_RESOLVED || $data['fque_status'] == ForumQuestion::FORUM_QUE_SPAMMED
                                    ) {
                                        $fld->setFieldTagAttribute('disabled', 'disabled');
                                        $canChangeStatus = false;
                                    }
                                    ?>
                                    <label class="statustab switch-group d-flex align-items-center justify-content-between <?php echo (false == $canChangeStatus ? 'disabled-switch' : ''); ?>" data-bind="click:toggleDisable">
                                        <span class="switch-group__label question-status-js"><?php echo ($fld->checked) ? Label::getLabel('LBL_Question_published') : Label::getLabel('LBL_Question_unpublished'); ?></span>
                                        <span class="switch switch--small">
                                            <input class="switch__label" type="<?php echo $fld->fldType; ?>" name="<?php echo $fld->getName(); ?>" value="<?php echo $fld->value; ?>" <?php echo ($fld->checked) ? 'checked' : ''; ?> <?php echo (false == $canChangeStatus ? 'disabled' : ''); ?>>
                                            <i class="switch__handle bg-green"></i>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="field-set">
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php $fld = $frm->getField('fque_comments_allowed'); ?>
                                    <label class="statustab switch-group d-flex align-items-center justify-content-between <?php echo (false == $canChangeStatus ? 'disabled-switch' : ''); ?>">
                                        <span class="switch-group__label comments-allowed-status-js"><?php echo ($fld->checked) ? Label::getLabel('LBL_Comments_allowed') : Label::getLabel('LBL_Comments_not_allowed'); ?></span>
                                        <span class="switch switch--small">
                                            <input class="switch__label" type="<?php echo $fld->fldType; ?>" name="<?php echo $fld->getName(); ?>" value="<?php echo $fld->value; ?>" <?php echo ($fld->checked) ? 'checked' : ''; ?> <?php echo (false == $canChangeStatus ? 'disabled' : ''); ?>>
                                            <i class="switch__handle bg-green"></i>
                                        </span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-auto">
                        <div class="field-set">
                            <div class="field-wraper form-buttons-group">
                                <div class="field_cover">
                                    <?php
                                    echo $frm->getFieldHtml('fque_id');
                                    echo $frm->getFieldHtml('fque_sel_tags');
                                    echo $frm->getFieldHtml('btn_submit');
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </form>
                <?php echo $frm->getExternalJS(); ?>
            </div>
        </div>
    </div>
    <script>
        var commentsAallowed = '<?php echo Label::getLabel('LBL_Comments_allowed'); ?>';
        var commentsNotAllowed = '<?php echo Label::getLabel('LBL_Comments_not_allowed'); ?>';
        var quePublished = '<?php echo Label::getLabel('LBL_Question_published'); ?>';
        var queUnpublished = '<?php echo Label::getLabel('LBL_Question_unpublished'); ?>';
        $(document).ready(function() {
            var addedTags = <?php echo json_encode($tags); ?>;
            processOldTags(addedTags);
            $('input[name="fque_comments_allowed"]').on('change', function() {
                let status = ($(this).is(':checked')) ? commentsAallowed : commentsNotAllowed;
                $('.comments-allowed-status-js').text(status);
            });
            $('input[name="fque_status"]').on('change', function() {
                let status = ($(this).is(':checked')) ? quePublished : queUnpublished;
                $('.question-status-js').text(status);
            });
            fcom.setEditorLayout(<?php echo $siteLangId; ?>);
        });
    </script>
    <style>
        .statustab.disabled-switch {
            cursor: no-drop;
            opacity: 0.5;
        }
    </style>