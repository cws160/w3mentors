<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal layout--' . $formLayout);
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = '12';
if ($lang_id > 0) {
    $frm->setFormTagAttribute('onsubmit', 'setupLang(this); return(false);');
} else {
    $frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
}
if (!$canEdit || $frmType == Configurations::FORM_MEDIA_AND_LOGOS) {
    $submitBtn = $frm->getField('btn_submit');
    if (!empty($submitBtn)) {
        $frm->removeField($submitBtn);
    }
    $langDataBtn = $frm->getField('update_langs_data');
    if (!empty($langDataBtn)) {
        $frm->removeField($langDataBtn);
    }
}
$tbid = isset($tabId) ? $tabId : 'tabs_' . $frmType;
switch ($frmType) {
    case Configurations::FORM_COMMON_SETTINGS:
        $registrationApproval = $frm->getField('CONF_ADMIN_APPROVAL_REGISTRATION');
        $registrationApproval->setFieldTagAttribute('id', 'registrationApproval');
        $registrationApproval->setFieldTagAttribute('class', 'registration-js');
        $registrationVerification = $frm->getField('CONF_EMAIL_VERIFICATION_REGISTRATION');
        $registrationVerification->setFieldTagAttribute('id', 'registrationVerification');
        $registrationVerification->setFieldTagAttribute('class', 'registration-js');
        $autoRegistration = $frm->getField('CONF_AUTO_LOGIN_REGISTRATION');
        $autoRegistration->setFieldTagAttribute('id', 'autoRegistration');
        $autoRegistration->setFieldTagAttribute('class', 'registration-js');
        $courseEnable = $frm->getField('CONF_ENABLE_COURSES');
        $courseEnable->setFieldTagAttribute('onchange', 'checkCourses(this)');
        $courseEnable->setFieldTagAttribute('id', 'confEnableCoursesJs');
        $subPlanEnable = $frm->getField('CONF_ENABLE_SUBSCRIPTION_PLAN');
        $subPlanEnable->setFieldTagAttribute('id', 'confEnableSubPlanJs');
        break;
    case Configurations::FORM_THIRD_PARTY_APIS:
        $googleClientJson = $frm->getField('CONF_GOOGLE_CLIENT_JSON');
        if ($isGoogleAuthSet) {
            if (empty($accessToken)) {
                $googleClientJson->htmlAfterField = '<p class="mb-0 color-secondary">' . Label::getLabel("LBL_GOOGLE_CREDENTIALS_NOT_AUTHORIZED", $lang_id) . ' <a  href="javascript:void(0);" onclick="googleAuthorize();">' . Label::getLabel("LBL_CLICK_HERE_TO_AUTHORIZED") . '</a>' . '</span>';
            } else {
                $googleClientJson->htmlAfterField = '<p class="mb-0 color-secondary">' . Label::getLabel('LBL_GOOGLE_CREDENTIALS_ALREADY_AUTHORIZED', $lang_id) . '</span>';
            }
        }
        break;
    case Configurations::FORM_DASHBOARD_CLASSES:
        $fld = $frm->getField('CONF_GROUP_CLASS_DURATION');
        $fld->setWrapperAttribute('class', 'form__list--check');
        break;
    case Configurations::FORM_PWA_SETTINGS:
        if (empty($iconData)) {
            $frm->getField('icon')->requirement->setRequired();
        } else {
            $icon_img_fld = $frm->getField('icon_img');
            $icon_img_fld->value = '<img src="' . MyUtility::makeUrl('Image', 'show', [Afile::TYPE_PWA_APP_ICON, 0, Afile::SIZE_SMALL]) . '?' . time() . '" alt="' . Label::getLabel('LBL_App Icon', $lang_id) . '" style="height:80px;border:1px solid #AAA;">';
        }
        $frm->setFormTagAttribute('class', 'form form_horizontal');
        $frm->developerTags = ['colClassPrefix' => 'col-md-', 'fld_default_col' => 12];
        $frm->getField('pwa_settings[background_color]')->overrideFldType('color');
        $frm->getField('pwa_settings[theme_color]')->overrideFldType('color');
        break;
    case Configurations::FORM_AFFILIATE_SETTINGS:
        $affiliateEnable = $frm->getField('CONF_ENABLE_AFFILIATE_MODULE');
        $affiliateEnable->setFieldTagAttribute('onchange', 'checkAffiliates(this)');
        $affiliateEnable->setFieldTagAttribute('id', 'confEnableAffiliatesJs');
        break;
    case Configurations::FORM_DASHBOARD_COURSES:
        if (FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_MUX) {
            $encoding = $frm->getField('CONF_MUX_ENCODING_TIER');
            $encoding->addFieldTagAttribute('onchange', 'getResolutions(this.value)');
        }
        break;
}
?>

<div class="card-head py-0 overflow-auto">
    <?php if (in_array($frmType, Configurations::getLangTypeForms())) { ?>
        <nav class="tab tab-inline">
            <ul style="white-space: nowrap;">
                <?php if ($frmType != Configurations::FORM_MEDIA_AND_LOGOS) { ?>
                    <li><a href="javascript:void(0)" class="<?php echo ($lang_id == 0) ? 'active' : ''; ?>" onClick="getForm(<?php echo $frmType; ?>, '<?php echo $tbid; ?>')">Basic</a></li>
                <?php } ?>
                <?php foreach ($languages as $langId => $langName) { ?>
                    <li><a href="javascript:void(0);" class="lang-form-js <?php echo ($lang_id == $langId) ? 'active' : ''; ?>" onClick="getLangForm(<?php echo $frmType; ?>,<?php echo $langId; ?>, '<?php echo $tbid; ?>')"><?php echo $langName; ?></a></li>
                <?php } ?>
            </ul>
        </nav>
    <?php } ?>
</div>

<?php if ($frmType != Configurations::FORM_MEDIA_AND_LOGOS) { ?>
    <div class="card-body">
        <?php echo $frm->getFormHtml(); ?>
    </div>
<?php } ?>
</div>

<?php
if ($frmType == Configurations::FORM_MEDIA_AND_LOGOS) {
    $isCourseEnabled = Course::isEnabled();
    $frm->developerTags['fld_default_col'] = '12';
    $fronLogo = $frm->getField('front_logo');

    $faviconFld = $frm->getField('favicon');
    $homeBannerBg = $frm->getField('home_banner_bg');
    $blogImg = $frm->getField('blog_img');
    $lessonImg = $frm->getField('lesson_img');
    $applyToTeachImage = $frm->getField('apply_to_teach_banner');
    $affiliateRegisterImage = $frm->getField('affiliate_register_img');
?>
    <div class="card-body">
        <?php echo $frm->getFormTag(); ?>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <h6><?php echo $fronLogo->getCaption(); ?></h6>
                    <span class="form-text text-muted">
                        <strong> <?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER", $lang_id) ?>:</strong> <?php echo str_replace(['{width}', '{height}'], ['200', '100'], Label::getLabel('LBL_For_best_view_width_{width}px_and_height_{height}px', $lang_id)) ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="dropzone mt-3 dropzoneContainerJs">
                        <div class="dropzone-uploaded dropzoneUploadedJs">
                            <?php $logoImg =  MyUtility::getLogo($lang_id); ?>
                            <?php echo $logoImg; ?>
                            <div class="dropzone-uploaded-action">
                                <?php if ($canEdit) { ?>
                                    <ul class="actions">
                                        <li>
                                            <a href="javascript:void(0)" class="logoFiles-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $fronLogo->getFieldTagAttribute('data-file_type') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT', $lang_id); ?>">
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#edit">
                                                    </use>
                                                </svg>
                                            </a>
                                        </li>
                                        <?php if (!empty($mediaData[Afile::TYPE_FRONT_LOGO])) { ?>
                                            <li>
                                                <a href="javascript:void(0)" onclick="removeMedia('<?php echo Afile::TYPE_FRONT_LOGO ?>', '<?php echo $lang_id ?>', this);" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE', $lang_id); ?>">
                                                    <svg class="svg" width="18" height="18">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#delete">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="separator  my-3">

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <h6><?php echo $faviconFld->getCaption(); ?></h6>
                    <span class="form-text text-muted">
                        <strong> <?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER", $lang_id) ?>:</strong> <?php echo str_replace(['{width}', '{height}'], ['200', '100'], Label::getLabel('LBL_For_best_view_width_{width}px_and_height_{height}px', $lang_id)) ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="dropzone mt-3 dropzoneContainerJs">
                        <div class="dropzone-uploaded dropzoneUploadedJs">
                            <?php $img =  MyUtility::getFavicon($lang_id) . '?' . time(); ?>
                            <img src=" <?php echo $img; ?>">
                            <div class="dropzone-uploaded-action">
                                <?php if ($canEdit) { ?>
                                    <ul class="actions">
                                        <li>
                                            <a href="javascript:void(0)" class="logoFiles-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $faviconFld->getFieldTagAttribute('data-file_type') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT', $lang_id); ?>">
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#edit">
                                                    </use>
                                                </svg>
                                            </a>
                                        </li>
                                        <?php if (!empty($mediaData[Afile::TYPE_FAVICON])) { ?>
                                            <li>
                                                <a href="javascript:void(0)" onclick="removeMedia('<?php echo Afile::TYPE_FAVICON ?>', '<?php echo $lang_id ?>', this);" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE', $lang_id); ?>">
                                                    <svg class="svg" width="18" height="18">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#delete">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="separator  my-3">

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <h6><?php echo $homeBannerBg->getCaption(); ?></h6>
                    <span class="form-text text-muted">
                        <strong> <?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER", $lang_id) ?>:</strong> <?php echo  sprintf(Label::getLabel('LBL_Dimensions_%s', $lang_id), '2000*600'); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="dropzone mt-3 dropzoneContainerJs">
                        <div class="dropzone-uploaded dropzoneUploadedJs">
                            <?php $img =  MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_HOME_BANNER_BACKGROUND, 0, Afile::SIZE_MEDIUM, $lang_id]) . '?' . time(); ?>
                            <img src=" <?php echo $img; ?>">
                            <div class="dropzone-uploaded-action">
                                <?php if ($canEdit) { ?>
                                    <ul class="actions">
                                        <li>
                                            <a href="javascript:void(0)" class="logoFiles-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $homeBannerBg->getFieldTagAttribute('data-file_type') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT', $lang_id); ?>">
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#edit">
                                                    </use>
                                                </svg>
                                            </a>
                                        </li>
                                        <?php if (!empty($mediaData[Afile::TYPE_HOME_BANNER_BACKGROUND])) { ?>
                                            <li>
                                                <a href="javascript:void(0)" onclick="removeMedia('<?php echo Afile::TYPE_HOME_BANNER_BACKGROUND ?>', '<?php echo $lang_id ?>', this);" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE', $lang_id); ?>">
                                                    <svg class="svg" width="18" height="18">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#delete">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="separator  my-3">

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <h6><?php echo $lessonImg->getCaption(); ?></h6>
                    <span class="form-text text-muted">
                        <strong> <?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER", $lang_id) ?>:</strong> <?php echo  sprintf(Label::getLabel('LBL_Dimensions_%s', $lang_id), '2000*600'); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="dropzone mt-3 dropzoneContainerJs">
                        <div class="dropzone-uploaded dropzoneUploadedJs">
                            <?php $img =  MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_LESSON_PAGE_IMAGE, 0, Afile::SIZE_SMALL, $lang_id]) . '?' . time(); ?>
                            <img src=" <?php echo $img; ?>">
                            <div class="dropzone-uploaded-action">
                                <?php if ($canEdit) { ?>
                                    <ul class="actions">
                                        <li>
                                            <a href="javascript:void(0)" class="logoFiles-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $lessonImg->getFieldTagAttribute('data-file_type') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT', $lang_id); ?>">
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#edit">
                                                    </use>
                                                </svg>
                                            </a>
                                        </li>
                                        <?php if (!empty($mediaData[Afile::TYPE_LESSON_PAGE_IMAGE])) { ?>
                                            <li>
                                                <a href="javascript:void(0)" onclick="removeMedia('<?php echo Afile::TYPE_LESSON_PAGE_IMAGE ?>', '<?php echo $lang_id ?>', this);" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE', $lang_id); ?>">
                                                    <svg class="svg" width="18" height="18">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#delete">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="form-group">
                    <div class="separator  my-3">

                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <h6><?php echo $applyToTeachImage->getCaption(); ?></h6>
                    <span class="form-text text-muted">
                        <strong> <?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER", $lang_id) ?>:</strong> <?php echo  sprintf(Label::getLabel('LBL_Dimensions_%s', $lang_id), '2000*900'); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="dropzone mt-3 dropzoneContainerJs">
                        <div class="dropzone-uploaded dropzoneUploadedJs">
                            <?php $img =  MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_APPLY_TO_TEACH_BANNER, 0, Afile::SIZE_SMALL, $lang_id]) . '?' . time(); ?>
                            <img src=" <?php echo $img; ?>">
                            <div class="dropzone-uploaded-action">
                                <?php if ($canEdit) { ?>
                                    <ul class="actions">
                                        <li>
                                            <a href="javascript:void(0)" class="logoFiles-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $applyToTeachImage->getFieldTagAttribute('data-file_type') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT', $lang_id); ?>">
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#edit">
                                                    </use>
                                                </svg>
                                            </a>
                                        </li>
                                        <?php if (!empty($mediaData[Afile::TYPE_APPLY_TO_TEACH_BANNER])) { ?>
                                            <li>
                                                <a href="javascript:void(0)" onclick="removeMedia('<?php echo Afile::TYPE_APPLY_TO_TEACH_BANNER ?>', '<?php echo $lang_id ?>', this);" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE', $lang_id); ?>">
                                                    <svg class="svg" width="18" height="18">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#delete">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-12">
                <div class="form-group">
                    <div class="separator  my-3">

                    </div>
                </div>
            </div>
            <?php if ($isCourseEnabled) {
                $certLogoFld = $frm->getField('certificate_logo');
            ?>
                <div class="col-md-6">
                    <div class="form-group">
                        <h6><?php echo $certLogoFld->getCaption(); ?></h6>
                        <span class="form-text text-muted">
                            <strong> <?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER", $lang_id) ?>:</strong> <?php echo  sprintf(Label::getLabel('LBL_Dimensions_%s', $lang_id), '140*47'); ?></span>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <div class="dropzone mt-3 dropzoneContainerJs">
                            <div class="dropzone-uploaded dropzoneUploadedJs">
                                <?php $img =  MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_CERTIFICATE_LOGO, 0, Afile::SIZE_SMALL, $lang_id]) . '?' . time(); ?>
                                <img src=" <?php echo $img; ?>">
                                <div class="dropzone-uploaded-action">
                                    <?php if ($canEdit) { ?>
                                        <ul class="actions">
                                            <li>
                                                <a href="javascript:void(0)" class="logoFiles-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $certLogoFld->getFieldTagAttribute('data-file_type') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT', $lang_id); ?>">
                                                    <svg class="svg" width="18" height="18">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#edit">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>
                                            <?php if (!empty($mediaData[Afile::TYPE_CERTIFICATE_LOGO])) { ?>
                                                <li>
                                                    <a href="javascript:void(0)" onclick="removeMedia('<?php echo Afile::TYPE_CERTIFICATE_LOGO ?>', '<?php echo $lang_id ?>', this);" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE', $lang_id); ?>">
                                                        <svg class="svg" width="18" height="18">
                                                            <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#delete">
                                                            </use>
                                                        </svg>
                                                    </a>
                                                </li>
                                            <?php } ?>
                                        </ul>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <div class="separator  my-3">

                        </div>
                    </div>
                </div>
            <?php } ?>

            <div class="col-md-6">
                <div class="form-group">
                    <h6><?php echo $affiliateRegisterImage->getCaption(); ?></h6>
                    <span class="form-text text-muted">
                        <strong> <?php echo Label::getLabel("LBL_IMAGE_DISCLAIMER", $lang_id) ?>:</strong> <?php echo  sprintf(Label::getLabel('LBL_Dimensions_%s', $lang_id), '2000*900'); ?></span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <div class="dropzone mt-3 dropzoneContainerJs">
                        <div class="dropzone-uploaded dropzoneUploadedJs">
                            <?php $img =  MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_AFFILIATE_REGISTRATION_BANNER, 0, Afile::SIZE_SMALL, $lang_id]) . '?' . time(); ?>
                            <img src=" <?php echo $img; ?>">
                            <div class="dropzone-uploaded-action">
                                <?php if ($canEdit) { ?>
                                    <ul class="actions">
                                        <li>
                                            <a href="javascript:void(0)" class="logoFiles-Js" data-bs-toggle="tooltip" data-placement="top" data-file_type="<?php echo $affiliateRegisterImage->getFieldTagAttribute('data-file_type') ?>" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_EDIT', $lang_id); ?>">
                                                <svg class="svg" width="18" height="18">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#edit">
                                                    </use>
                                                </svg>
                                            </a>
                                        </li>
                                        <?php if (!empty($mediaData[Afile::TYPE_AFFILIATE_REGISTRATION_BANNER])) { ?>
                                            <li>
                                                <a href="javascript:void(0)" onclick="removeMedia('<?php echo Afile::TYPE_AFFILIATE_REGISTRATION_BANNER ?>', '<?php echo $lang_id ?>', this);" data-bs-toggle="tooltip" data-placement="top" title="" data-bs-original-title="<?php echo Label::getLabel('LBL_CLICK_HERE_TO_REMOVE', $lang_id); ?>">
                                                    <svg class="svg" width="18" height="18">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_BACKEND; ?>images/retina/sprite-actions.svg#delete">
                                                        </use>
                                                    </svg>
                                                </a>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        echo  $frm->getFieldHTML('lang_id');
        echo $frm->getFieldHTML('form_type');
        ?>
        </form>
    </div>
    <?php echo $frm->getExternalJS(); ?>
<?php } ?>