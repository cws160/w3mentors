<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if ((!empty(FatApp::getConfig('CONF_FACEBOOK_APP_ID')) && !empty(FatApp::getConfig('CONF_FACEBOOK_APP_SECRET'))) || !empty(FatApp::getConfig('CONF_GOOGLE_CLIENT_JSON')) || !empty(FatApp::getConfig('CONF_APPLE_CLIENT_ID'))) { ?>
    <div class="social-actions">
        <?php if (!empty(FatApp::getConfig('CONF_FACEBOOK_APP_ID')) && !empty(FatApp::getConfig('CONF_FACEBOOK_APP_SECRET'))) { ?>
            <a class="social-button social-button--fb social-button--block" href="<?php echo MyUtility::makeUrl('GuestUser', 'facebookLogin'); ?>">
                <span class="social-button__media">
                    <svg xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path fill="#1877F2" d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z" />
                    </svg>
                </span>
                <span class="social-button__label"><?php echo Label::getLabel("LBL_SIGN_IN_WITH_FACEBOOK") ?></span>
            </a>
        <?php }
        if (!empty(FatApp::getConfig('CONF_GOOGLE_CLIENT_JSON'))) { ?>
            <a class="social-button social-button--google social-button--block" href="<?php echo MyUtility::generateFullUrl('GuestUser', 'googleLogin'); ?>">
                <span class="social-button__media">
                    <svg xmlns="https://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                        <g transform="translate(-187 -241)">
                            <rect width="24" height="24" transform="translate(187 241)" fill="none" />
                            <g transform="translate(190 243)">
                                <path d="M4.211,144.619l-.661,2.469-2.417.051a9.517,9.517,0,0,1-.07-8.871h0l2.152.395.943,2.139a5.67,5.67,0,0,0,.053,3.817Z" transform="translate(0 -133.137)" fill="#fbbb00" />
                                <path d="M270.753,208.176a9.5,9.5,0,0,1-3.387,9.183h0l-2.711-.138-.384-2.395a5.662,5.662,0,0,0,2.436-2.891h-5.08v-3.758h9.125Z" transform="translate(-251.919 -200.451)" fill="#518ef8" />
                                <path d="M44.824,314.835h0a9.5,9.5,0,0,1-14.315-2.906l3.079-2.52a5.65,5.65,0,0,0,8.142,2.893Z" transform="translate(-29.377 -297.927)" fill="#28b446" />
                                <path d="M43.126,2.187l-3.078,2.52a5.649,5.649,0,0,0-8.329,2.958L28.625,5.131h0a9.5,9.5,0,0,1,14.5-2.944Z" transform="translate(-27.562)" fill="#f14336" />
                            </g>
                        </g>
                    </svg>
                </span>
                <span class="social-button__label"><?php echo Label::getLabel("LBL_SIGN_IN_WITH_GOOGLE") ?></span>
            </a>
        <?php }
        if (!empty(FatApp::getConfig('CONF_APPLE_CLIENT_ID'))) { ?>
            <a class="social-button social-button--ap social-button--block" href="<?php echo MyUtility::generateFullUrl('GuestUser', 'appleLogin'); ?>">
                <span class="social-button__media">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M11.624 7.222c-.876 0-2.232-.996-3.66-.96-1.884.024-3.612 1.092-4.584 2.784-1.956 3.396-.504 8.412 1.404 11.172.936 1.344 2.04 2.856 3.504 2.808 1.404-.06 1.932-.912 3.636-.912 1.692 0 2.172.912 3.66.876 1.512-.024 2.472-1.368 3.396-2.724 1.068-1.56 1.512-3.072 1.536-3.156-.036-.012-2.94-1.128-2.976-4.488-.024-2.808 2.292-4.152 2.4-4.212-1.32-1.932-3.348-2.148-4.056-2.196-1.848-.144-3.396 1.008-4.26 1.008zm3.12-2.832c.78-.936 1.296-2.244 1.152-3.54-1.116.048-2.46.744-3.264 1.68-.72.828-1.344 2.16-1.176 3.432 1.236.096 2.508-.636 3.288-1.572z" />
                    </svg>
                </span>
                <span class="social-button__label"><?php echo Label::getLabel("LBL_SIGN_IN_WITH_APPLE") ?></span>
            </a>
        <?php } ?>
    </div>
    <?php if(!isset($isForumPage)) { ?>
        <div class="or"><span><?php echo Label::getLabel('LBL_OR') ; ?></span></div>
    <?php } ?>
<?php } ?>