<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('onsubmit', 'setupOfferPrice(this); return(false);');
?>


<div class="modal-header">
    <h5 class="page-heading">
        <?php echo sprintf(Label::getLabel("LBL_OFFER_PERCENTAGE_FOR_%s"), ucfirst($offers['user_first_name'] . ' ' . $offers['user_last_name'])) ?>
    </h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>

<div class="modal-body">
    <?php
    echo $frm->getFormTag();
    echo $frm->getFieldHtml('offpri_learner_id');
    echo $frm->getFieldHtml('offpri_id');
    ?>
    <fieldset class="fieldset-box">
        <legend><?php echo Label::getLabel("LBL_LESSON_OFFER") ?></legend>
        <table class="table-pricing">
            <tbody>
                <?php foreach ($userSlots as $userSlot) { ?>
                    <tr>
                        <td width="70%"><?php echo $frm->getField('offpri_lesson_price[' . $userSlot . ']')->getCaption(); ?></td>
                        <td>
                            <?php echo $frm->getFieldHtml('offpri_lesson_price[' . $userSlot . ']') ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </fieldset>
    <fieldset class="fieldset-box">
        <legend><?php echo Label::getLabel("LBL_CLASS_OFFER") ?></legend>
        <table class="table-pricing">
            <tbody>
                <?php foreach ($classSlots as $classSlot) { ?>
                    <tr>
                        <td width="70%"><?php echo $frm->getField('offpri_class_price[' . $classSlot . ']')->getCaption(); ?></td>
                        <td>
                            <?php echo $frm->getFieldHtml('offpri_class_price[' . $classSlot . ']') ?>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </fieldset>
    <fieldset class="fieldset-box">
        <legend><?php echo Label::getLabel("LBL_CLASS_PACKAGE_OFFER") ?></legend>
        <table class="table-pricing">
            <tbody>
                <tr>
                    <td width="70%"><?php echo $frm->getField('offpri_package_price')->getCaption(); ?></td>
                    <td>
                        <?php echo $frm->getFieldHtml('offpri_package_price'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>
    <div class="row">
        <div class="fld_wrapper-js col-md-12">
            <div class="field-set mb-0">
                <div class="field-wraper form-buttons-group">
                    <div class="field_cover">
                        <?php echo $frm->getFieldHtml('btn_submit'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </form>
</div>
<?php echo $frm->getExternalJs(); ?>