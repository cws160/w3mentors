<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'setupStep2(this); return(false);');
$frm->setFormTagAttribute('action', MyUtility::makeUrl('TeacherRequest', 'setupStep2'));
$profileImageField = $frm->getField('user_profile_image');
$profileImageField->setFieldTagAttribute('class', 'btn btn--bordered btn--small color-secondary');
$usrVideoLink = $frm->getField('tereq_video_link');
$usrVideoLink->addFieldTagAttribute('onblur', 'validateVideolink(this);');
$usrBio = $frm->getField('tereq_biography');
$this->includeTemplate('teacher-request/_partial/leftPanel.php', ['step' => 2]);
?>
<script>
    var useMouseScroll = "<?php echo htmlspecialchars(Label::getLabel('LBL_USE_MOUSE_SCROLL_TO_ADJUST_IMAGE')); ?>";
    var lblCroperInfoText = '<?php echo htmlspecialchars(Label::getLabel('LBL_USE_MOUSE_SCROLL_TO_ADJUST_IMAGE')); ?>';
</script>
<div class="page-block__right">
    <div class="page-block__head">
        <div class="head__title">
            <h4><?php echo Label::getLabel('LBL_Tutor_registration'); ?></h4>
        </div>
    </div>
    <div class="page-block__body">
        <?php echo $frm->getFormTag() ?>
        <div class="block-content">
            <div class="block-content__head">
                <div class="info__content">
                    <h5><?php echo Label::getLabel('LBL_PROFILE_MEDIA_TITLE'); ?></h5>
                    <p><?php echo Label::getLabel('LBL_PROFILE_MEDIA_DESC'); ?></p>
                </div>
            </div>
            <div class="block-content__body">
                <div class="img-upload">
                    <div class="img-upload__media">
                        <div class="avtar" id="avtar-js">
                            <img id="user-profile-pic--js" src="<?php echo MyUtility::makeUrl('Image', 'show', [$imageType, $userId, Afile::SIZE_MEDIUM]) . '?t=' . time(); ?>">
                        </div>
                    </div>
                    <div class="img-upload__content">
                        <h6><?php echo $profileImageField->getCaption(); ?><span class="spn_must_field">*</span></h6>
                        <span class="btn btn--bordered color-primary btn--small btn--fileupload btn--wide me-2">
                            <?php
                            echo $profileImageField->getHTML();
                            echo Label::getLabel('LBL_Upload');
                            ?>
                        </span>
                        <?php $fileSize = MyUtility::convertBitesToMb($fileSize) . ' ' . Label::getLabel('LBL_MB'); ?>
                        <small>(<?php echo str_replace(['{size}', '{ext}'], [$fileSize, implode(", ", $imageExt)], Label::getLabel('LBL_IMAGE_MAX_SIZE_{size}_AND_ALLOWED_EXT_{ext}')); ?>)</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo Label::getLabel('LBL_OTHER_DIMENSIONS'); ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <div class="avtar-views">
                                        <ul class="avtar-views-list">
                                            <li class="avtar-views-item">
                                                <figure class="avtar avtar--round avtar--xl">
                                                    <?php echo '<img src="' . MyUtility::makeUrl('Image', 'show', [$imageType, $userId, Afile::SIZE_LARGE], CONF_WEBROOT_FRONT_URL) . '?' . time() . '" />'; ?>
                                                </figure>
                                            </li>
                                            <li class="avtar-views-item">
                                                <figure class="avtar avtar--round avtar--md">
                                                    <?php echo '<img src="' . MyUtility::makeUrl('Image', 'show', [$imageType, $userId, Afile::SIZE_MEDIUM], CONF_WEBROOT_FRONT_URL) . '?' . time() . '" />'; ?>
                                                </figure>
                                            </li>
                                            <li class="avtar-views-item">
                                                <figure class="avtar avtar--round avtar--sm">
                                                    <?php echo '<img src="' . MyUtility::makeUrl('Image', 'show', [$imageType, $userId, Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL) . '?' . time() . '" />'; ?>
                                                </figure>
                                            </li>
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
                                <label class="field_label">
                                    <?php echo $usrVideoLink->getCaption(); ?>
                                    <small><?php echo Label::getLabel('LBL_video_desc'); ?></small>
                                    <?php if ($usrVideoLink->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $usrVideoLink->getHTML(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label"><?php echo $usrBio->getCaption(); ?> <small><?php echo Label::getLabel('LBL_About_self_Fld_Desc'); ?></small>
                                    <?php if ($usrBio->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $usrBio->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="block-content__foot">
                <div class="form__actions">
                    <input type="submit" name="save" value="<?php echo Label::getLabel('LBL_SAVE'); ?>" />
                    <input type="button" name="next" onclick="setupStep2(document.frmFormStep2, true)" value="<?php echo Label::getLabel('LBL_NEXT'); ?>" />
                </div>
            </div>
        </div>
        <?php
        echo $frm->getFieldHtml('update_profile_img');
        echo $frm->getFieldHtml('rotate_left');
        echo $frm->getFieldHtml('rotate_right');
        echo $frm->getFieldHtml('img_data');
        echo $frm->getFieldHtml('action');
        ?>
        </form>
        <?php echo $frm->getExternalJs(); ?>
    </div>
</div>