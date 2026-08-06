<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<section class="forum-header section bg-gradiant section--page-header text-center">
    <div class="container container--narrow">
        <h1><?php echo Label::getLabel('LBL_VIDEO_CONTENT'); ?></h1>
    </div>
</section>
<section class="section">
    <div class="container container--fixed">
        <div class="search-result pb-4">
            <h3><?php echo Label::getLabel('LBL_Showing'); ?> <span id="start_record">0</span> - <span id="end_record">0</span> <?php echo Label::getLabel('LBL_of'); ?> <span id="total_records">0</span> <?php echo Label::getLabel('LBL_Videos'); ?></h3>
        </div>
        <div id="bibleListingContainer"></div>
    </div>
</section>