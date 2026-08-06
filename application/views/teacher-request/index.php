<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$recaptchaKey = FatApp::getConfig('CONF_RECAPTCHA_SITEKEY', FatUtility::VAR_STRING, '');
if (!empty($recaptchaKey)) {
    $htmlNote = $applyTeachFrm->getField('htmlNote');
    $htmlNote->value = '<div class="field-set"><div class="caption-wraper"><label class="field_label"></label></div><div class="field-wraper"><div class="field_cover">
    <div class="g-recaptcha" data-sitekey="' . $siteKey . '" data-callback="captchaValidate" data-expired-callback="captchaValidate"></div></div></div></div>';
}
$applyTeachFrm->developerTags['colClassPrefix'] = 'col-md-';
$applyTeachFrm->developerTags['fld_default_col'] = 12;
$applyTeachFrm->setFormTagAttribute('class', 'form form--custom');
$applyTeachFrm->setFormTagAttribute('onsubmit', 'teacherSetup(this); return(false);');
$userEmail = $applyTeachFrm->getField('user_email');
$userEmail->setFieldTagAttribute('placeholder', Label::getLabel('LBL_EMAIL'));
$userEmail->setFieldTagAttribute('class', 'form-control');
$userPassword = $applyTeachFrm->getField('user_password');
$userPassword->setFieldTagAttribute('placeholder', Label::getLabel('LBL_PASSWORD'));
$submitBtn = $applyTeachFrm->getField('btn_submit');
$submitBtn->setFieldTagAttribute('class', 'btn btn--secondary btn--large btn--block ');
?>
<section class="section section--hero" style="background-image: url(<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('image', 'show', [Afile::TYPE_APPLY_TO_TEACH_BANNER, 0, Afile::SIZE_LARGE, $siteLangId], CONF_WEBROOT_URL), CONF_DEF_CACHE_TIME); ?>)">
    <div class="container container--xxl">
        <div class="hero-panel">
            <div class="hero-panel__content" data-aos="fade-up" data-aos-duration="1000">
                <div class="hero-form">
                    <div class="hero-form__head">
                        <h1><?php echo Label::getLabel('LBL_APPLY_TO_TEACH'); ?></h1>
                        <p><?php echo Label::getLabel('LBL_APPLY_TO_TEACH_DESCRITPION'); ?></p>
                    </div>
                    <div class="hero-form__body">
                        <?php if (!empty($siteUserId)) { ?>
                            <?php if (empty($siteUser['user_is_teacher'])) { ?>
                                <a href="<?php echo MyUtility::makeUrl('TeacherRequest', 'form'); ?>"
                                    class="btn btn--secondary btn--large btn--block mb-4"><?php echo Label::getLabel('LBL_BECOME_A_TUTOR'); ?></a>
                            <?php }
                            $colSize = '12';
                            $howItWorkSection = false;
                            array_search(ExtraPage::BLOCK_APPLY_TO_TEACH_FEATURES_SECTION, array_column($contentBlocks, 'epage_block_type')) !== false ? $howItWorkSection = true : '';
                            if (!empty($faqs) && $howItWorkSection) {
                                $colSize = '6';
                            }
                            if (!empty($faqs) || $howItWorkSection) { ?>
                                <div class="row g-5">
                                    <?php if (!empty($faqs)) { ?>
                                        <div class="col-<?php echo $colSize; ?>">
                                            <button href="#faq-area" class="btn btn--block btn--primary scroll scroll-section-js">
                                                <?php echo Label::getLabel('LBL_FAQS'); ?>
                                            </button>
                                        </div>
                                    <?php } ?>
                                    <?php if ($howItWorkSection) { ?>
                                        <div class="col-<?php echo $colSize; ?>">
                                            <button href="#how-it-works" class="btn btn--block btn--primary scroll scroll-section-js">
                                                <?php echo Label::getLabel('LBL_HOW_IT_WORKS'); ?>
                                            </button>
                                        </div>
                                    <?php } ?>
                                </div>
                            <?php } ?>
                        <?php } else { ?>
                            <div class="form-register">
                                <?php echo $applyTeachFrm->getFormTag(); ?>
                                <?php echo $applyTeachFrm->getFieldHTML('agree'); ?>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <?php echo $userEmail->getHTML(); ?>
                                            <?php echo $applyTeachFrm->getFieldHTML('user_dashboard'); ?>
                                            <?php echo $applyTeachFrm->getFieldHTML('user_id'); ?>
                                        </div>
                                    </div>
                                    <div class="col-sm-12">
                                        <div class="form-group">
                                            <div class="field-password">
                                                <?php echo $userPassword->getHTML(); ?>
                                                <a href="javascript:void(0);" class="password-toggle">
                                                    <span class="icon" id="hide-password">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16.2" height="17.134" viewBox="0 0 16.2 17.134">
                                                            <path id="Path_6420" data-name="Path 6420" d="M13.685,15.853a7.764,7.764,0,0,1-4.4,1.375,8.437,8.437,0,0,1-8.1-7.269,9.083,9.083,0,0,1,2.5-4.9L1.339,2.536,2.4,1.393,17.222,17.384l-1.059,1.142-2.478-2.673ZM4.74,6.2A7.383,7.383,0,0,0,2.71,9.96a7.171,7.171,0,0,0,3.846,5.031,6.307,6.307,0,0,0,6.038-.316l-1.518-1.638A3.187,3.187,0,0,1,6.9,12.532a3.852,3.852,0,0,1-.468-4.507ZM9.965,11.84,7.538,9.222a2.136,2.136,0,0,0,.419,2.166,1.774,1.774,0,0,0,2.008.452Zm5.909,1.829L14.8,12.514A7.509,7.509,0,0,0,15.852,9.96,7.262,7.262,0,0,0,12.72,5.324a6.315,6.315,0,0,0-5.272-.745L6.267,3.3a7.7,7.7,0,0,1,3.014-.614,8.437,8.437,0,0,1,8.1,7.269,9.2,9.2,0,0,1-1.506,3.709Zm-6.8-7.337a3.236,3.236,0,0,1,2.59,1.058,3.8,3.8,0,0,1,.98,2.794L9.073,6.332Z" transform="translate(-1.181 -1.393)" fill="#a2a2a2"></path>
                                                        </svg>
                                                    </span>
                                                    <span class="icon" id="show-password" style="display: none;">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16.2" height="14.538" viewBox="0 0 16.2 14.538">
                                                            <path id="Path_6422" data-name="Path 6422" d="M9.281,3a8.437,8.437,0,0,1,8.1,7.269,8.436,8.436,0,0,1-8.1,7.269,8.437,8.437,0,0,1-8.1-7.269A8.436,8.436,0,0,1,9.281,3Zm0,12.922a6.873,6.873,0,0,0,6.571-5.652,6.873,6.873,0,0,0-6.57-5.647A6.873,6.873,0,0,0,2.71,10.27a6.874,6.874,0,0,0,6.571,5.653Zm0-2.019a3.509,3.509,0,0,1-3.369-3.634A3.509,3.509,0,0,1,9.281,6.634a3.509,3.509,0,0,1,3.369,3.634A3.509,3.509,0,0,1,9.281,13.9Zm0-1.615a1.95,1.95,0,0,0,1.872-2.019A1.95,1.95,0,0,0,9.281,8.25a1.95,1.95,0,0,0-1.872,2.019A1.95,1.95,0,0,0,9.281,12.288Z" transform="translate(-1.181 -3)" fill="#a2a2a2"></path>
                                                        </svg>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($recaptchaKey)) { ?>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="form-group">
                                                <?php echo $htmlNote->getHTML(); ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <button class="btn btn--primary btn--block" name="btn_submit"><?php echo $submitBtn->value; ?></button>
                                </form>
                                <?php echo $applyTeachFrm->getExternalJs(); ?>
                            </div>

                            <div class="hero-form__footer">
                                <p>
                                    <?php
                                    $termsPage = FatApp::getConfig('CONF_TERMS_AND_CONDITIONS_PAGE', FatUtility::VAR_INT, 0);
                                    $privacyPage = FatApp::getConfig('CONF_PRIVACY_POLICY_PAGE', FatUtility::VAR_INT, 0);
                                    $termsLink = '<a href="' . MyUtility::makeUrl('Cms', 'view', [$termsPage]) . '" class="link">' . Label::getLabel('LBL_Terms_&_Conditions') . '</a>';
                                    $privacyLink = '<a href="' . MyUtility::makeUrl('cms', 'view', [$privacyPage]) . '" class="link">' . Label::getLabel('LBL_Privacy_Policy') . '</a>';
                                    echo sprintf(Label::getLabel('LBL_BY_SIGNING_UP_YOU_AGREE_TO_TERMS'), $termsLink, $privacyLink);
                                    ?>
                                </p>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
if (!empty($contentBlocks)) {
    foreach ($contentBlocks as $sn => $row) {
        switch ($row['epage_block_type']) {
            case ExtraPage::BLOCK_APPLY_TO_TEACH_BENEFITS_SECTION:
                echo '<section class="section">';
                echo FatUtility::decodeHtmlEntities($row['epage_content']);
                echo '</section>';
                break;
            case ExtraPage::BLOCK_APPLY_TO_TEACH_FEATURES_SECTION:
                echo '<section class="section" id="how-it-works" data-aos="fade-up" data-aos-duration="1000">';
                echo FatUtility::decodeHtmlEntities($row['epage_content']);
                echo '</section>';
                break;
            case ExtraPage::BLOCK_APPLY_TO_TEACH_STATIC_BANNER:
                echo '<section class="section section--cta-block">';
                echo FatUtility::decodeHtmlEntities($row['epage_content']);
                echo '</section>';
                break;
            case ExtraPage::BLOCK_APPLY_TO_TEACH_BECOME_A_TUTOR_SECTION:
                echo '<section class="section section--tutor-steps bg-gradiant">';
                echo FatUtility::decodeHtmlEntities($row['epage_content']);
                echo '</section>';
                break;
            default:
                break;
        }
    }
}
?>
<?php if (!empty($faqs)) { ?>
    <section class="section section--faq" id="faq-area" data-aos="fade-up" data-aos-duration="1000">
        <div class="container container--narrow">
            <div class="section__head mb-5">
                <h2><?php echo Label::getLabel('LBL_faq_title_second'); ?></h2>
            </div>
            <div class="section__body">
                <div class="faq-container" id="faqParentJs">
                    <?php foreach ($faqs as $ques) { ?>
                        <?php
                        if (empty($ques['faq_title']) || empty($ques['faq_description'])) {
                            continue;
                        }
                        ?>
                        <div class="faq-row faq-group-js">
                            <a href="javascript:void(0)" data-bs-toggle="collapse" data-bs-target="#description<?php echo $ques['faq_id']; ?>" class="faq-title faq__trigger collapsed">
                                <h5><?php echo $ques['faq_title']; ?></h5>
                            </a>
                            <div class="faq__target faq__target-js collapse" data-bs-parent="#faqParentJs" id="description<?php echo $ques['faq_id']; ?>">
                                <div class="faq-answer">
                                    <div class="editor-content"><?php echo nl2br($ques['faq_description']); ?></div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </section>
<?php } ?>
<?php
$contactUsBanner = ExtraPage::getBlockContent(ExtraPage::BLOCK_APPLY_TO_TEACH_FAQ_CONTACT, $siteLangId);
if(!empty($contactUsBanner)) {
    $this->includeTemplate('_partial/contact-us-section.php', ['contactUsBanner' => $contactUsBanner]);
} 
?>
<?php if (!empty($siteKey) && !empty($secretKey)) { ?>
    <script src='//www.google.com/recaptcha/api.js'></script>
<?php } ?>