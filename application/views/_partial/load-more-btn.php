<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$nextPage = $page + 1;
if ($nextPage <= $pageCount) {
    ?>
    <button id="loadMoreBtn" onClick="goToLoadMore(<?php echo $nextPage; ?>);" class="btn btn--show"><?php echo Label::getLabel('LBL_Load_More'); ?></button>
<?php } ?>