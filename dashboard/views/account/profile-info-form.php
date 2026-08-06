<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$profileFrm->setFormTagAttribute('id', 'profileInfoFrm');
$profileFrm->setFormTagAttribute('class', 'form form--horizontal');
$profileFrm->setFormTagAttribute('onsubmit', 'setupProfileInfo(this, false); return(false);');
$userIdFld = $profileFrm->getField('user_id');
$userIdFld->addFieldTagAttribute('id', 'user_id');
if ($profileFrm->getField('user_username')) {
    $userUsername = $profileFrm->getField('user_username');
    $userUsername->addFieldTagAttribute('onchange', 'formatUrl(this);');
    $userUsername->developerTags['col'] = 12;
    $protocol = (FatApp::getConfig('CONF_USE_SSL')) ? 'https://' : 'http://';
    $link = $protocol . $_SERVER['SERVER_NAME'] . MyUtility::makeUrl('teachers', 'view', [$userUsername->value], CONF_WEBROOT_FRONTEND);
    $userUsername->htmlAfterField = '<small class="user_url_string mb-0"><a href="' . $link . '" target="_blank">' . $link . '</a></small>';
}
if ($profileFrm->getField('user_book_before')) {
    $profileFrm->getField('user_book_before')->htmlAfterField = "<br><small>" . Label::getLabel("htmlAfterField_booking_before_text") . ".</small>";
}
$profileFrm->developerTags['colClassPrefix'] = 'col-md-';
$profileFrm->developerTags['fld_default_col'] = 6;
$firstNameField = $profileFrm->getField('user_first_name');
$firstNameField->addFieldTagAttribute('placeholder', $firstNameField->getCaption());
$lastNameField = $profileFrm->getField('user_last_name');
$lastNameField->addFieldTagAttribute('placeholder', $lastNameField->getCaption());
$genderField = $profileFrm->getField('user_gender');
$phoneField = $profileFrm->getField('user_phone_number');
$countryField = $profileFrm->getField('user_country_id');
$timeZoneField = $profileFrm->getField('user_timezone');
$bookingBeforeField = $profileFrm->getField('user_book_before');
$freeTrialField = $profileFrm->getField('user_trial_enabled');
$siteLangField = $profileFrm->getField('user_lang_id');
$nextButton = $profileFrm->getField('btn_next');
$nextButton->addFieldTagAttribute('onClick', 'setupProfileInfo(this.form, true); return(false);');
$phoneCode = $profileFrm->getField('user_phone_code');
$phoneCode->addFieldTagAttribute('id', 'user_phone_code');


if ($userRow['user_is_teacher'] == AppConstant::YES) {
    $timeZoneField->htmlAfterField = "<br><small class='color-secondary'>" . Label::getLabel("htmlAfterField_TIMEZONE_TEXT") . ".</small>";
}
if (MyUtility::getLayoutDirection() == 'rtl') {
    $phoneField->addFieldTagAttribute('style', 'direction: ltr;text-align:right;');
}
if ($userRow['user_is_teacher'] == AppConstant::YES) {
    $offlineSession = $profileFrm->getField('user_offline_sessions');
}
?>
<div class="content-panel__head">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h5><?php echo Label::getLabel('LBL_Manage_Profile'); ?></h5>
        </div>
    </div>
</div>
<div class="content-panel__body">
    <div class="form" id="langForm">
        <div class="form__body p-0">
            <nav class="tabs tabs--line ps-4 pe-4">
                <ul class="tab-ul-js">
                    <li class="is-active"><a href="javascript:void(0)" onclick="profileInfoForm();"><?php echo Label::getLabel('LBL_General'); ?></a></li>
                    <li><a href="javascript:void(0)" onclick="profileImageForm();" class="profile-imag-li"><?php echo Label::getLabel('LBL_Photos_&_Videos'); ?></a></li>

                    <?php
                    if ($siteUserType == User::TEACHER) {
                        foreach ($languages as $langId => $language) {
                    ?>
                            <li class="profile-lang-tab"><a href="javascript:void(0);" class="profile-lang-li" onclick="getLangProfileInfoForm(<?php echo $langId; ?>);"><?php echo $language['language_name']; ?></a></li>
                    <?php
                        }
                    }
                    ?>
                </ul>
            </nav>
            <div class="tabs-data">
                <div id="profileInfoFrmBlock">
                    <?php if (!empty(FatApp::getConfig('CONF_GOOGLE_CLIENT_JSON')) && $siteUserType != User::AFFILIATE) { ?>
                        <div class="action-bar border-top-0 <?php echo (!$isGoogleAuthSet) ? 'selection-disabled' : ''; ?>">
                            <div class="row justify-content-between align-items-center">
                                <div class="col-sm-6">
                                    <div class="d-flex align-items-center">
                                        <div class="action-bar__media me-4">
                                            <div class="g-circle">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="31" height="31" viewBox="0 0 31 31">
                                                    <path fill="#fbbb00" d="M6.87,148.63l-1.079,4.028-3.944.083a15.527,15.527,0,0,1-.114-14.474h0l3.511.644,1.538,3.49a9.25,9.25,0,0,0,.087,6.228Z" transform="translate(0 -129.896)" />
                                                    <path fill="#518ef8" d="M276.516,208.176a15.494,15.494,0,0,1-5.525,14.983h0l-4.423-.226-.626-3.907a9.238,9.238,0,0,0,3.975-4.717h-8.288v-6.132h14.888Z" transform="translate(-245.787 -195.572)" />
                                                    <path fill="#28b446" d="M53.865,318.262h0a15.5,15.5,0,0,1-23.356-4.742l5.023-4.112a9.219,9.219,0,0,0,13.284,4.72Z" transform="translate(-28.662 -290.675)" />
                                                    <path fill="#f14336" d="M52.285,3.568,47.263,7.679a9.217,9.217,0,0,0-13.589,4.826L28.625,8.372h0a15.5,15.5,0,0,1,23.661-4.8Z" transform="translate(-26.891)" />
                                                </svg>
                                            </div>
                                        </div>
                                        <div class="action-bar__content">
                                            <p class="mb-0"><?php echo Label::getLabel('LBL_TO_SYNC_WITH_GOOGLE_CALENDAR'); ?></p>
                                            <?php if (!$isGoogleAuthSet) { ?>
                                                <p class="mb-0 color-secondary"><?php echo Label::getLabel('LBL_GOOGLE_CALENDAR_NOT_ACTIVE_YET'); ?></p>
                                            <?php } else { ?>
                                                <p class="mb-0 color-secondary"><?php echo (empty($accessToken)) ? Label::getLabel('LBL_YOUR_GOOGLE_CALENDAR_NOT_SYNC') : Label::getLabel('LBL_YOUR_GOOGLE_CALENDAR_ALREADY_SYNCED'); ?></p>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-auto">
                                    <a onclick="googleCalendarAuthorize();" href="javascript:void(0);" class="social-button social-button--google">
                                        <span class="social-button__media">
                                            <svg xmlns="http://www.w3.org/2000/svg" x="0px" y="0px" width="100" height="100" viewBox="0 0 48 48">
                                                <rect width="22" height="22" x="13" y="13" fill="#fff"></rect>
                                                <polygon fill="#1e88e5" points="25.68,20.92 26.688,22.36 28.272,21.208 28.272,29.56 30,29.56 30,18.616 28.56,18.616"></polygon>
                                                <path fill="#1e88e5" d="M22.943,23.745c0.625-0.574,1.013-1.37,1.013-2.249c0-1.747-1.533-3.168-3.417-3.168 c-1.602,0-2.972,1.009-3.33,2.453l1.657,0.421c0.165-0.664,0.868-1.146,1.673-1.146c0.942,0,1.709,0.646,1.709,1.44 c0,0.794-0.767,1.44-1.709,1.44h-0.997v1.728h0.997c1.081,0,1.993,0.751,1.993,1.64c0,0.904-0.866,1.64-1.931,1.64 c-0.962,0-1.784-0.61-1.914-1.418L17,26.802c0.262,1.636,1.81,2.87,3.6,2.87c2.007,0,3.64-1.511,3.64-3.368 C24.24,25.281,23.736,24.363,22.943,23.745z"></path>
                                                <polygon fill="#fbc02d" points="34,42 14,42 13,38 14,34 34,34 35,38"></polygon>
                                                <polygon fill="#4caf50" points="38,35 42,34 42,14 38,13 34,14 34,34"></polygon>
                                                <path fill="#1e88e5" d="M34,14l1-4l-1-4H9C7.343,6,6,7.343,6,9v25l4,1l4-1V14H34z"></path>
                                                <polygon fill="#e53935" points="34,34 34,42 42,34"></polygon>
                                                <path fill="#1565c0" d="M39,6h-5v8h8V9C42,7.343,40.657,6,39,6z"></path>
                                                <path fill="#1565c0" d="M9,42h5v-8H6v5C6,40.657,7.343,42,9,42z"></path>
                                            </svg>
                                        </span>
                                        <span class="social-button__label"><?php echo Label::getLabel('LBL_CONNECT_GOOGLE_CALENDAR'); ?></span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="padding-6">
                        <div class="max-width-80">
                            <?php
                            echo $profileFrm->getFormTag();
                            if ($profileFrm->getField('user_id')) {
                                echo $profileFrm->getFieldHtml('user_id');
                            }
                            ?>
                            <?php if (isset($userUsername)) { ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label">
                                                    <?php echo $userUsername->getCaption(); ?>
                                                    <?php if ($userUsername->requirement->isRequired()) { ?>
                                                        <span class="spn_must_field">*</span>
                                                    <?php } ?>
                                                </label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <?php echo $userUsername->getHTML(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="field-set">
                                        <div class="caption-wraper">
                                            <label class="field_label"><?php echo Label::getLabel('LBL_Name'); ?>
                                                <?php if ($firstNameField->requirement->isRequired()) { ?>
                                                    <span class="spn_must_field">*</span>
                                                <?php } ?>
                                            </label>
                                        </div>
                                        <div class="field-wraper">
                                            <div class="field_cover">
                                                <div class="custom-cols custom-cols--onehal">
                                                    <ul>
                                                        <li><?php echo $firstNameField->getHTML('user_first_name'); ?></li>
                                                        <li><?php echo $lastNameField->getHTML('user_last_name'); ?></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="field-set">
                                        <div class="caption-wraper">
                                            <label class="field_label"><?php echo $genderField->getCaption(); ?>
                                                <?php if ($genderField->requirement->isRequired()) { ?>
                                                    <span class="spn_must_field">*</span>
                                                <?php } ?>
                                            </label>
                                        </div>
                                        <div class="field-wraper">
                                            <div class="field_cover">
                                                <div class="custom-cols custom-cols--onehal">
                                                    <?php echo $genderField->getHtml(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="field-set">
                                        <div class="caption-wraper">
                                            <label class="field_label"> <?php echo $countryField->getCaption(); ?>
                                                <?php if ($countryField->requirement->isRequired()) { ?>
                                                    <span class="spn_must_field">*</span>
                                                <?php } ?>
                                            </label>
                                        </div>
                                        <div class="field-wraper">
                                            <div class="field_cover custom-select-search">
                                                <?php echo $countryField->getHTML(); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="field-set">
                                        <div class="caption-wraper">
                                            <label class="field_label"><?php echo Label::getLabel('LBL_PHONE'); ?>
                                                <?php if ($phoneField->requirement->isRequired()) { ?>
                                                    <span class="spn_must_field">*</span>
                                                <?php } ?>
                                            </label>
                                        </div>
                                        <div class="field-wraper">
                                            <div class="field_cover">
                                                <div class="custom-cols custom-cols--onehal">
                                                    <ul>
                                                        <li class="custom-select-search"><?php echo $phoneCode->getHTML(); ?></li>
                                                        <li><?php echo $phoneField->getHTML(); ?></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="field-set">
                                        <div class="caption-wraper">
                                            <label class="field_label"> <?php echo $timeZoneField->getCaption(); ?>
                                                <?php if ($timeZoneField->requirement->isRequired()) { ?>
                                                    <span class="spn_must_field">*</span>
                                                <?php } ?>
                                            </label>
                                        </div>
                                        <div class="field-wraper">
                                            <div class="field_cover">
                                                <?php echo $timeZoneField->getHTML(); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if (isset($offlineSession)) { ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label"> <?php echo $offlineSession->getCaption(); ?>
                                                    <?php if ($offlineSession->requirement->isRequired()) { ?>
                                                        <span class="spn_must_field">*</span>
                                                    <?php } ?>
                                                </label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <label class="switch-group d-flex align-items-center justify-content-between">
                                                        <span class="switch-group__label offline-status-js"><?php echo ($offlineSession->checked) ? Label::getLabel('LBL_Active') : Label::getLabel('LBL_In-active'); ?></span>
                                                        <span class="switch switch--small">
                                                            <input class="switch__label" type="<?php echo $offlineSession->fldType; ?>" name="<?php echo $offlineSession->getName(); ?>" value="<?php echo $offlineSession->value; ?>" <?php echo ($offlineSession->checked) ? 'checked' : ''; ?>>
                                                            <i class="switch__handle bg-green"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if ($bookingBeforeField) { ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label"> <?php echo $bookingBeforeField->getCaption(); ?>
                                                    <?php if ($bookingBeforeField->requirement->isRequired()) { ?>
                                                        <span class="spn_must_field">*</span>
                                                    <?php } ?>
                                                </label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <?php echo $bookingBeforeField->getHTML(); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="field-set">
                                        <div class="caption-wraper">
                                            <label class="field_label"> <?php echo $siteLangField->getCaption(); ?>
                                                <?php if ($siteLangField->requirement->isRequired()) { ?>
                                                    <span class="spn_must_field">*</span>
                                                <?php } ?>
                                            </label>
                                        </div>
                                        <div class="field-wraper">
                                            <div class="field_cover">
                                                <?php echo $siteLangField->getHTML(); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php if ($freeTrialField) { ?>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="field-set">
                                            <div class="caption-wraper">
                                                <label class="field_label"> <?php echo $freeTrialField->getCaption(); ?>
                                                    <?php if ($freeTrialField->requirement->isRequired()) { ?>
                                                        <span class="spn_must_field">*</span>
                                                    <?php } ?>
                                                </label>
                                            </div>
                                            <div class="field-wraper">
                                                <div class="field_cover">
                                                    <label class="switch-group d-flex align-items-center justify-content-between">
                                                        <span class="switch-group__label free-trial-status-js"><?php echo ($freeTrialField->checked) ? Label::getLabel('LBL_Active') : Label::getLabel('LBL_In-active'); ?></span>
                                                        <span class="switch switch--small">
                                                            <input class="switch__label" type="<?php echo $freeTrialField->fldType; ?>" name="<?php echo $freeTrialField->getName(); ?>" value="<?php echo $freeTrialField->value; ?>" <?php echo ($freeTrialField->checked) ? 'checked' : ''; ?>>
                                                            <i class="switch__handle bg-green"></i>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                            <div class="row submit-row">
                                <div class="col-sm-12">
                                    <div class="field-set">
                                        <div class="field-wraper">
                                            <div class="field_cover">
                                                <?php echo $profileFrm->getFieldHtml('btn_submit'); ?>
                                                <?php echo $profileFrm->getFieldHtml('btn_next'); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            </form>
                            <?php echo $profileFrm->getExternalJS(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var statusActive = '<?php echo Label::getLabel('LBL_Active'); ?>';
    var statusInActive = '<?php echo Label::getLabel('LBL_In-active'); ?>';


    $(document).ready(function() {
        $("[name='user_timezone'], [name='user_country_id'], [name='user_phone_code']").select2({
            formatNoMatches: function() {
                return 'testfound';
            },
            "language": {
                "noResults": function() {
                    return langLbl.noresultfound;
                }
            },
        });
        $("[name='user_country_id']").on('change', function() {
            $("[name='user_phone_code']").val($("[name='user_country_id']").val());
            $("[name='user_phone_code']").css({
                width: '100%'
            });
            $("[name='user_phone_code']").next().remove();
            $("[name='user_phone_code']").select2({
                formatNoMatches: function() {
                    return 'testfound';
                },
                "language": {
                    "noResults": function() {
                        return langLbl.noresultfound;
                    }
                },
            });
        });
        $('input[name="user_username"]').on('keypress', function(e) {
            if (e.which == 32) {
                return false;
            }
        });
        $('input[name="user_username"]').on('change', function(e) {
            var user_name = $(this).val();
            user_name = user_name.replace(/ /g, "");
            $(this).val(user_name);
            $('.user_username_span').html(user_name);
        });
        $('input[name="user_username"]').on('keyup', function() {
            var user_name = $(this).val();
            $('.user_username_span').html(user_name);
        });
        $('input[name="user_trial_enabled"]').on('change', function() {
            let status = ($(this).is(':checked')) ? statusActive : statusInActive;
            $('.free-trial-status-js').text(status);
        });
        $('input[name="user_offline_sessions"]').on('change', function() {
            let status = ($(this).is(':checked')) ? statusActive : statusInActive;
            $('.offline-status-js').text(status);
        });
    });
</script>