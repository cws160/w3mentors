<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!-- [ FOOTER ========= -->
<?php if (!$courseQuiz) { ?>
    <footer class="footer">
        <div class="container">
            <div class="row justify-content-between">
                <?php if (MyUtility::isDemoUrl() || true == WHITE_LABELED) { ?>
                    <?php
                    $url = (false == WHITE_LABELED) ? '<a target="_blank"  href="http://w3mentors.com">' . FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, 'w3mentors') . '</a>' : FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, 'w3mentors');
                    $replacements = array(
                        '{year}' => '&copy; ' . date("Y"),
                        '{product}' => '<span class="bold-600">' . $url . '</span>',
                        '{owner}' => '<a target="_blank" rel="nofollow" class="underline color-primary" href="https://www.w3mentors.com">FATbit Technologies</a>',
                    );
                    ?>
                    <div class="col-md-auto">
                        <div class="copyright mb-2 mb-md-0">
                            <?php
                            echo $str = CommonHelper::replaceStringData(Label::getLabel('LBL_COPYRIGHT_TEXT', $siteLangId), $replacements);
                            ?>
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <div class="small text-md-right">
                            <?php
                            if (false == WHITE_LABELED) {
                                echo $str = CommonHelper::replaceStringData(Label::getLabel('LBL_DEVELOPED_BY_TEXT', $siteLangId), $replacements);
                            }
                            ?>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="col-md-auto">
                        <div class="copyright mb-2 mb-md-0">
                            Copyright &copy; <?php echo date("Y"); ?>
                            <span class="bold-600">
                                <a target="_blank" href="https://w3mentors.com">
                                    <?php echo FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, ''); ?>
                                </a>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-auto">
                        <div class="small text-md-right">
                            Technology Partner: <a target="_blank" rel="nofollow" class="bold-600" href="https://www.w3mentors.com">FATbit</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </footer>
<?php } ?>
<!-- ] -->
</page>
<!-- Custom Loader -->
<div id="app-alert" class="alert-position alert-position--top-right fadeInDown animated"></div>
<script>
    <?php if ($siteUserId > 0) { ?>
        setTimeout(getBadgeCount(), 1000);
    <?php }
    if (Message::getMessageCount() > 0) { ?>
        fcom.success('<?php echo Message::getData()['msgs'][0]; ?>');
    <?php }
    if (Message::getDialogCount() > 0) { ?>
        fcom.warning('<?php echo Message::getData()['dialog'][0]; ?>');
    <?php }
    if (Message::getErrorCount() > 0) { ?>
        fcom.error('<?php echo Message::getData()['errs'][0]; ?>');
    <?php } ?>
</script>