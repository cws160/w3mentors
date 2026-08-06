<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="gap"></div>
<div class="gap"></div>
<div id="body" class="body">
    <div class="container container--fixed">
        <!-- <div class="row"> -->
        <div class="container container--fluid">
            <div class="panel panel--centered">
                <div class="box box--white">
                    <div class="message message--success align--center">
                        <i class="fa fa-check-circle"></i>
                        <h2><?php echo Label::getLabel('LBL_Congratulations'); ?></h2>
                        <h6><?php echo CommonHelper::renderHtml($textMessage); ?></h6>
                        <span class="gap"></span>
                    </div>
                </div>
            </div>
        </div>
        <!-- </div> -->
    </div>
    <div class="gap"></div>
</div>
