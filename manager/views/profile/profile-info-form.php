<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$imgFrm->addFormTagAttribute('class', 'ggg');
$imgFrm->setFormTagAttribute('action', MyUtility::makeUrl('Profile', 'uploadProfileImage'));
$userNameFld = $frm->getField('admin_username');
$userNameFld->addFieldTagAttribute('id', 'admin_username');
$emailFld = $frm->getField('admin_email');
$emailFld->addFieldTagAttribute('id', 'admin_email');
$frm->setFormTagAttribute('id', 'profileInfoFrm');
$frm->setFormTagAttribute('class', 'form');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 6;
$frm->setFormTagAttribute('onsubmit', 'updateProfileInfo(this); return(false);');
$extensionLabel = Label::getLabel('LBL_ALLOWED_FILE_EXTS_{ext}');
$maxSizeLabel = Label::getLabel('LBL_MAX_SIZE_{size}');
$fld = $imgFrm->getField('update_profile_img');
$fld->htmlAfterField = '<span>' . str_replace('{size}', MyUtility::convertBitesToMb($fileSize) . ' MB', $maxSizeLabel) . '</span>';
$fld->htmlAfterField .= '<br><span>' . str_replace('{ext}', implode(',', $fileExt), $extensionLabel) . '</span>';
?>
<div class="col-lg-6">
    <div class="card">
        <div class="card-head">
            <div class="card-head-label">
                <h3 class="card-head-title">
                    <?php echo Label::getLabel('LBL_MY_PROFILE'); ?>
                </h3>
            </div>
        </div>
        <div class="card-body">
            <div class="profile-details">
                <div class="text-center">
                    <div class="avatar avatar-outline avatar-circle">
                        <div class="avatar__holder">
                            <img src="<?php echo MyUtility::makeUrl('image', 'show', [Afile::TYPE_ADMIN_PROFILE_IMAGE, AdminAuth::getLoggedAdminId(), 'LARGE']) . '?' . time(); ?>" alt="">
                        </div>
                        <?php echo $imgFrm->getExternalJS(); ?>
                        <?php if (!empty($image)) { ?>
                            <a class="avatar__cancel" href="javascript:void(0)" onClick="removeProfileImage()">
                                <svg class="svg" width="12" height="12">
                                    <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-actions.svg#delete">
                                    </use>
                                </svg>
                            </a>
                        <?php } ?>
                        <span class="avatar__upload">
                            <svg class="svg" width="12" height="12">
                                <use xlink:href="<?php echo CONF_WEBROOT_BACKEND ?>images/retina/sprite-actions.svg#edit">
                                </use>
                            </svg>
                            <?php echo $imgFrm->getFieldHtml('user_profile_image'); ?>
                        </span>
                    </div>
                </div>
                <div class="text-center">
                    <?php echo $imgFrm->getFormTag(); ?>
                    <?php
                    echo $imgFrm->getFieldHtml('update_profile_img');
                    echo $imgFrm->getFieldHtml('rotate_left');
                    echo $imgFrm->getFieldHtml('rotate_right');
                    echo $imgFrm->getFieldHtml('remove_profile_img');
                    echo $imgFrm->getFieldHtml('action');
                    echo $imgFrm->getFieldHtml('img_data');
                    ?>
                    </form>
                    <div id="dispMessage"></div>
                </div>
                <div class="mt-5">
                    <?php echo $frm->getFormHtml(); ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $("[name='admin_timezone']").select2();
</script>