<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$form->setFormTagAttribute('id', 'cookieForm');
$form->setFormTagAttribute('class', 'form');
$form->developerTags['colClassPrefix'] = 'col-md-';
$form->developerTags['fld_default_col'] = 7;
$form->setFormTagAttribute('autocomplete', 'off');
$form->setFormTagAttribute('onsubmit', 'cookieConsentSetup(this); return false;');
$necessaryField = $form->getField(CookieConsent::NECESSARY);
$necessaryField->developerTags['cbHtmlBeforeCheckbox'] = '<span class="checkbox disabled">';
$necessaryField->developerTags['cbHtmlAfterCheckbox'] = '<i class="input-helper"></i></span>';
$necessaryField->addFieldTagAttribute('disabled', true);
$preferencesField = $form->getField(CookieConsent::PREFERENCES);
$preferencesField->developerTags['cbHtmlBeforeCheckbox'] = '<span class="checkbox">';
$preferencesField->developerTags['cbHtmlAfterCheckbox'] = '<i class="input-helper"></i></span>';
$statisticsField = $form->getField(CookieConsent::STATISTICS);
$statisticsField->developerTags['cbHtmlBeforeCheckbox'] = '<span class="checkbox">';
$statisticsField->developerTags['cbHtmlAfterCheckbox'] = '<i class="input-helper"></i></span>';
$submitButton = $form->getField('btn_submit');
?>
<div class="modal-header cookie-consent">
    <h5><?php echo Label::getLabel('LBL_COOKIE_CONSENT_HEADING'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="form-edit-head">
        <nav class="tabs tabs--line border-bottom-0">
            <ul>
                <li class="is-active"><a href="javascript:void(0);" class="tab-a" data-id="tab_necessary"><?php echo Label::getLabel('LBL_NECESSARY'); ?></a></li>
                <li><a href="javascript:void(0);" class="tab-a" data-id="tab_preferences"><?php echo Label::getLabel('LBL_PREFERENCES'); ?></a></li>
                <li><a href="javascript:void(0);" class="tab-a" data-id="tab_statistics"><?php echo Label::getLabel('LBL_STATISTICS'); ?></a></li>
            </ul>
        </nav>
    </div>
    <div class="form-edit-body">
        <?php echo $form->getFormTag(); ?>
        <div class="tabs-data">
            <div class="tab-div" data-id="tab_necessary">
                <div class="tabs-data__box">
                    <div class="tab-heading d-flex align-items-center justify-content-between mb-3">
                        <h6><?php echo Label::getLabel('LBL_NECESSARY'); ?></h6>
                        <div class="field_cover">
                            <?php echo $necessaryField->getHTML(CookieConsent::NECESSARY); ?>
                        </div>
                    </div>
                    <p><?php echo Label::getLabel('LBL_NECESSARY_COOKIE_DESCRIPTION_TEXT'); ?></p>
                </div>
            </div>
            <div class="tab-div d-none" data-id="tab_preferences">
                <div class="tabs-data__box">
                    <div class="tab-heading d-flex align-items-center justify-content-between mb-3">
                        <h6><?php echo Label::getLabel('LBL_PREFERENCES'); ?></h6>
                        <div class="field_cover">
                            <?php echo $preferencesField->getHTML(CookieConsent::PREFERENCES); ?>
                        </div>
                    </div>
                    <p><?php echo Label::getLabel('LBL_PREFERENCES_COOKIE_DESCRIPTION_TEXT'); ?></p>
                </div>
            </div>
            <div class="tab-div d-none" data-id="tab_statistics">
                <div class="tabs-data__box">
                    <div class="tab-heading d-flex align-items-center justify-content-between mb-3">
                        <h6><?php echo Label::getLabel('LBL_STATISTICS'); ?></h6>
                        <div class="field_cover">
                            <?php echo $statisticsField->getHTML(CookieConsent::STATISTICS); ?>
                        </div>
                    </div>
                    <p><?php echo Label::getLabel('LBL_STATISTICS_COOKIE_DESCRIPTION_TEXT'); ?></p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                <div class="field-set mb-0">
                    <div class="field-wraper">
                        <div class="field_cover">
                            <?php echo $form->getFieldHtml('btn_submit'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
    <?php echo $form->getExternalJS(); ?>
</div>
<!--end of container-->
<script>
    var necessaryField = '<?php echo CookieConsent::NECESSARY ?>';
    $(document).ready(function() {

        $('.tab-a').click(function() {
            $(".tab-div").addClass('d-none');
            $(".tab-div[data-id='" + $(this).attr('data-id') + "']").removeClass("d-none");
            $(".tab-a").parent().removeClass('is-active');
            $(this).parent().addClass('is-active');
        });
    });
</script>