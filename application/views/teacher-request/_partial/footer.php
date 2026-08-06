<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<footer class="footer">
    <div class="section py-4 section-copyright">
        <div class="container container--narrow">
            <div class="row">
                <?php if (MyUtility::isDemoUrl() || true == WHITE_LABELED) { ?>
                    <?php
                    $url = (false == WHITE_LABELED) ? '<a target="_blank"  href="http://w3mentors.com">' . FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, 'w3mentors') . '</a>' : FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, 'w3mentors');
                    $replacements = array(
                        '{year}' => '&copy; ' . date("Y"),
                        '{product}' => '<span class="bold-600">' . $url . '</span>',
                        '{owner}' => '<a target="_blank" rel="nofollow" class="underline color-primary" href="https://www.w3mentors.com">FATbit Technologies</a>',
                    );
                    ?>
                    <div class="col-md-6">
                        <div class="copyright">
                            <?php
                            echo $str = CommonHelper::replaceStringData(Label::getLabel('LBL_COPYRIGHT_TEXT', $siteLangId), $replacements);
                            ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-md-right">
                            <?php
                            if (false == WHITE_LABELED) {
                                echo $str = CommonHelper::replaceStringData(Label::getLabel('LBL_DEVELOPED_BY_TEXT', $siteLangId), $replacements);
                            }
                            ?>
                        </div>
                    </div>
                <?php } else { ?>
                    <div class="col-md-6">
                        <div class="copyright">
                            Copyright &copy; <?php echo date("Y"); ?>
                            <span class="bold-600">
                                <?php echo FatApp::getConfig('CONF_WEBSITE_NAME_1', FatUtility::VAR_STRING, ''); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="small text-md-right">
                            Technology Partner: <a target="_blank" rel="nofollow" class="bold-600" href="https://www.w3mentors.com">FATbit</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</footer>
<!-- Custom Loader -->
<div id="app-alert" class="alert-position alert-position--top-right"></div>
<script>
    $(document).on('click', '.btn-Back', function() {
        var blockId = parseInt($('.is-process').attr('data-blocks-show')) - 1;
        $('.change-block-js').removeClass('is-process');
        $('li[data-blocks-show="' + blockId + '"]').addClass('is-process');
        $('.page-block__body').hide();
        $('#block--' + blockId).show();
        return false;
    });
</script>