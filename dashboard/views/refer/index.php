<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form--small');
$frm->setFormTagAttribute('onsubmit', 'search(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-sm-6 col-lg-';
$frm->developerTags['fld_default_col'] = 4;

if ($siteUserType == USER::AFFILIATE) {
    ($frm->getField('keyword'))->setFieldTagAttribute('placeholder', Label::getLabel('LBL_USER'));

    $fld = $frm->getField('date_from');
    $fld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_FROM_DATE'));
    $fld->setFieldTagAttribute('readonly', 'readonly');
    $fld->setFieldTagAttribute('class', 'field--calender');

    $fld = $frm->getField('date_to');
    $fld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_TO_DATE'));
    $fld->setFieldTagAttribute('readonly', 'readonly');
    $fld->setFieldTagAttribute('class', 'field--calender');
} else {
    $fld = $frm->getField('repnt_comment');
    $fld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_KEYWORD'));
}

$submitBtn = $frm->getField('btn_submit');
$submitBtn->setWrapperAttribute('class', 'form-buttons-group col-lg-3 col-sm-6');
$btnReset = $frm->getField('btn_reset');
$btnReset->addFieldTagAttribute('onclick', 'clearSearch()');

$mailFrm->setFormTagAttribute('class', 'form');
$mailFrm->setFormTagAttribute('onsubmit', 'sendMails(this); return(false);');

$referralUrl = MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL) . '?referral=' . $referCode;
$refMsg = Label::getLabel('ASR_SHARE_TEXT');
$refInviteInfo = Label::getLabel('ASR_REFER_FRIEND_INVITE_INFO_TEXT');
if ($siteUserType == User::AFFILIATE) {
    $refMsg = Label::getLabel('ASR_AFFILIATE_SHARE_TEXT');
    $refInviteInfo = Label::getLabel('ASR_AFFILIATE_REFER_FRIEND_INVITE_INFO_TEXT');
}
$searchTitle =  ($siteUserType == User::AFFILIATE) ?  Label::getLabel('LBL_YOUR_REFEREES') : Label::getLabel('LBL_TRACK_YOUR_REWARD_POINTS');
$shareText = str_replace(['{full_name}', '{website_url}'], [$fullName, MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONT_URL)], Label::getLabel('ASR_SHARE_TEXT'));

?>
<!-- [ PAGE ========= -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_REFER_AND_EARN'); ?>
                    <?php if ($siteUserType != User::AFFILIATE) { ?>
                        (<?php echo FatUtility::int($creditBalance); ?> <?php echo Label::getLabel('LBL_CREDITS'); ?>)
                    <?php } ?>
                </h1>
            </div>
            <div class="col-sm-auto">
                <?php if ($creditBalance > 0) { ?>
                    <a onclick="redeemPoints(<?php echo $siteUserId; ?>)" href="javascript:void(0)" class="btn btn--secondary">
                        <?php echo Label::getLabel('LBL_REDEEM_TO_WALLET'); ?>
                    </a>
                <?php } ?>
            </div>
        </div>
        <!-- ] ========= -->
    </div>
    <div class="page__body">
        <div class="page-content">
            <!-- [ PAGE PANEL ========= -->
            <div class="page-view mb-5">
                <div class="white-box">

                    <div class="field-group mb-5">
                        <div class="field-group__head">
                            <h5><?php echo Label::getLabel('LBL_Invite_Via_Emails'); ?></h5>
                            <p><?php echo Label::getLabel('ASR_REFER_FRIEND_EMAIL_INFO_TEXT'); ?></p>
                        </div>
                        <div class="field-group__body">
                            <div class="field-share">
                                <?php echo $mailFrm->getFormTag(); ?>
                                <?php echo $mailFrm->getFieldHtml('emails'); ?>
                                <?php echo $mailFrm->getFieldHtml('btn_submit'); ?>
                                </form>
                                <?php echo $mailFrm->getExternalJs(); ?>
                            </div>
                        </div>
                    </div>
                    <div class="field-group">
                        <div class="field-group__head">
                            <h5><?php echo Label::getLabel('LBL_Share_The_Referral_Link'); ?></h5>
                            <p><?php $inviteInfoText = str_replace(['{website_name}'], [FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId)], $refInviteInfo);
                                echo $inviteInfoText; ?></p>
                        </div>
                        <div class="field-group__body">
                            <div class="flex-elements">
                                <div class="flex-elements__large">
                                    <div class="field-copy">
                                        <input id="referral_code" type="text" readonly value="<?php echo $referralUrl ?>">
                                        <small>(<?php echo Label::getLabel('ASR_HELPTEXT_WHATSAPP') ?>)</small>
                                        <a onclick="copyCode();" href="javascript:void(0)" class="btn btn--equal btn--transparent color-primary is-hover">
                                            <svg class="icon icon--copy" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                <path d="M7,6V3A1,1,0,0,1,8,2H20a1,1,0,0,1,1,1V17a1,1,0,0,1-1,1H17v3a1,1,0,0,1-1.007,1H4.007A1,1,0,0,1,3,21L3,7A1,1,0,0,1,4.01,6ZM5,8,5,20H15V8ZM9,6h8V16h2V4H9Z" />
                                            </svg>
                                            <span class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_COPY_URL'); ?></span>
                                        </a>
                                    </div>
                                </div>
                                <div class="flex-elements__small">
                                    <div class="social-sharing">
                                        <a title="Share on whatsapp" class="btn btn--equal btn--whatsapp is-hover share-network-whatsapp st-custom-button" data-network="whatsapp" data-url="<?php echo $referralUrl ?>" href="javascript:void(0)">
                                            <svg class="icon icon--whatsapp" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                <path d="M2.004 22l1.352-4.968A9.954 9.954 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10a9.954 9.954 0 0 1-5.03-1.355L2.004 22zM8.391 7.308a.961.961 0 0 0-.371.1 1.293 1.293 0 0 0-.294.228c-.12.113-.188.211-.261.306A2.729 2.729 0 0 0 6.9 9.62c.002.49.13.967.33 1.413.409.902 1.082 1.857 1.971 2.742.214.213.423.427.648.626a9.448 9.448 0 0 0 3.84 2.046l.569.087c.185.01.37-.004.556-.013a1.99 1.99 0 0 0 .833-.231c.166-.088.244-.132.383-.22 0 0 .043-.028.125-.09.135-.1.218-.171.33-.288.083-.086.155-.187.21-.302.078-.163.156-.474.188-.733.024-.198.017-.306.014-.373-.004-.107-.093-.218-.19-.265l-.582-.261s-.87-.379-1.401-.621a.498.498 0 0 0-.177-.041.482.482 0 0 0-.378.127v-.002c-.005 0-.072.057-.795.933a.35.35 0 0 1-.368.13 1.416 1.416 0 0 1-.191-.066c-.124-.052-.167-.072-.252-.109l-.005-.002a6.01 6.01 0 0 1-1.57-1c-.126-.11-.243-.23-.363-.346a6.296 6.296 0 0 1-1.02-1.268l-.059-.095a.923.923 0 0 1-.102-.205c-.038-.147.061-.265.061-.265s.243-.266.356-.41a4.38 4.38 0 0 0 .263-.373c.118-.19.155-.385.093-.536-.28-.684-.57-1.365-.868-2.041-.059-.134-.234-.23-.393-.249-.054-.006-.108-.012-.162-.016a3.385 3.385 0 0 0-.403.004z" />
                                            </svg>
                                            <span class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_SHARE_VIA_WHATSAPP'); ?></span>
                                        </a>
                                        <a href="javascript:void(0)" title="Share on facebook" class="btn btn--equal btn--facebook is-hover share-network-facebook st-custom-button" data-network="facebook" data-url="https://www.facebook.com/sharer/sharer.php?u=<?php echo $referralUrl ?>&t='<?php echo $shareText; ?>'">
                                            <svg class="icon icon--facebook" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z" />
                                            </svg>
                                            <span class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_SHARE_VIA_FACEBOOK'); ?></span>
                                        </a>
                                        <a href="javascript:void(0)" title="Share on twitter" class="btn btn--equal btn--twitter is-hover share-network-twitter st-custom-button" data-network="twitter" data-url="https://twitter.com/share?url=<?php echo $referralUrl ?>&text=<?php echo $shareText; ?>">
                                            <svg class="icon icon--twitter" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                <path d="M8 2H1L9.26086 13.0145L1.44995 21.9999H4.09998L10.4883 14.651L16 22H23L14.3917 10.5223L21.8001 2H19.1501L13.1643 8.88578L8 2ZM17 20L5 4H7L19 20H17Z"></path>
                                            </svg>
                                            <span class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_SHARE_VIA_X'); ?></span>
                                        </a>
                                        <a href="javascript:void(0)" title="Share on telegram" class="btn btn--equal btn--telegram is-hover share-network-telegram st-custom-button" data-network="telegram" data-url="<?php echo $referralUrl ?>&text=<?php echo $shareText; ?>">
                                            <svg class="icon icon--telegram" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                <path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-3.11-8.83l.013-.007.87 2.87c.112.311.266.367.453.341.188-.025.287-.126.41-.244l1.188-1.148 2.55 1.888c.466.257.801.124.917-.432l1.657-7.822c.183-.728-.137-1.02-.702-.788l-9.733 3.76c-.664.266-.66.638-.12.803l2.497.78z" />
                                            </svg>
                                            <span class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_SHARE_VIA_TELEGRAM'); ?></span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ] -->
            <!-- [ PAGE PANEL ========= -->
            <div class="page-view">
                <div class="page-view__head pb-5">
                    <div class="row align-items-center justify-content-between">
                        <div class="col-sm-6">
                            <h3><?php echo $searchTitle; ?></h3>
                        </div>
                        <div class="col-sm-auto">
                            <div class="buttons-group d-flex align-items-center gap-2">
                                <a href="javascript:void(0)" class="btn btn--secondary slide-toggle-js btn--block-mobile">
                                    <svg class="icon icon--clock icon--small me-2">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                                    </svg>
                                    <?php echo Label::getLabel('LBL_Search'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="page-view__body">
                    <!-- [ FILTERS ========= -->
                    <div class="search-filter slide-target-js mb-4">
                        <?php echo $frm->getFormHtml(); ?>
                    </div>
                    <div id="listing" class="table-scroll"></div>
                </div>
            </div>
            <!-- ] -->
        </div>
        <!-- ] -->
    </div>
    <?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>