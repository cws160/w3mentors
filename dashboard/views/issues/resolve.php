<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('id', 'actionForm');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'resolveSetup(this); return(false);');
$actionFld = $frm->getField('reislo_action');
$commentFld = $frm->getField('reislo_comment');
$submitBtn = $frm->getField('btn_submit');
?>
<div class="modal-header gap-2">
    <h5><?php echo Label::getLabel('LBL_ISSUE_DETAIL'); ?></h5>
    <?php if ($order['order_discount_value'] > 0 || $order['order_reward_value'] > 0) { ?>
        <span class="-color-primary color-secondary ms-4"><?php echo Label::getLabel('LBL_NOTE_REFUND_WITH_DISCOUNT_OR_REWARDS'); ?></span>
    <?php } ?>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="detail-group-row">
        <div class="detail-row">
            <div class="detail-row__primary">
                <span class="card-landscape__status badge color-red badge--curve ms-0 me-4">
                    <?php echo Issue::getStatusArr($issue['repiss_status']); ?>
                </span>
                <?php echo Label::getLabel('LBL_ISSUE'); ?> <span class="tag"><?php echo $issue['repiss_title']; ?></span>
                <?php echo Label::getLabel('LBL_WAS_POSTED_BY'); ?> <span class="tag"><?php echo $issue['learner_full_name']; ?></span>
            </div>
            <div class="detail-row__secondary">
                <div class="date">
                    <span><?php echo MyDate::showDate($issue['repiss_reported_on'], true); ?></span>
                </div>
            </div>
        </div>
    </div>
    <div class="detail-group-row background-gray">
        <div class="detail-group-title">
            <h4><?php echo Label::getLabel('LBL_RESOLUTION_FORM'); ?></h4>
        </div>
        <div class="issue-log">
            <div class="issue-log__item">
                <div class="">
                    <?php echo $frm->getFormTag(); ?>
                    <?php echo $frm->getFieldHtml('reislo_repiss_id'); ?>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="field-set">
                                <div class="caption-wraper">
                                    <label class="field_label">
                                        <?php echo $actionFld->getCaption(); ?>
                                        <?php if ($actionFld->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $actionFld->getHtml(); ?>
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
                                        <?php echo $commentFld->getCaption(); ?>
                                        <?php if ($commentFld->requirement->isRequired()) { ?>
                                            <span class="spn_must_field">*</span>
                                        <?php } ?>
                                    </label>
                                </div>
                                <div class="field-wraper">
                                    <div class="field_cover">
                                        <?php echo $commentFld->getHtml(); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="field-set mb-0">
                                <div class="field-wraper form-buttons-group">
                                    <div class="field_cover">
                                        <?php echo $submitBtn->getHtml(); ?>
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
    </div>
</div>