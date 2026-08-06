<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if ($lecture) {
    $containerClass = 'col-xl-12';
?>
    <div class="row justify-content-between">
        <div class="<?php echo $containerClass; ?>">
            <div class="cms-container">
                <div class="editor-content iframe-content">
                    <iframe onload="resetIframe(this);" src="<?php echo MyUtility::makeUrl('Tutorials', 'frame', [$lecture['lecture_id']]); ?>" style="border:none; width:100%; height:30px;"></iframe>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="message-display no-skin">
                <div class="message-display__media">
                    <svg>
                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#stuck"></use>
                    </svg>
                </div>
                <h4 class="mb-3"><?php echo stripslashes(Label::getLabel("LBL_YOU_HAVE_COMPLETED_THE_LAST_LECTURE_IN_THIS_COURSE")); ?></h4>
            </div>
        </div>
    </div>
<?php } ?>