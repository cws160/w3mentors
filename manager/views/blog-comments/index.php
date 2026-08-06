<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$search->setFormTagAttribute('onsubmit', 'searchBlogComments(this); return(false);');
$search->setFormTagAttribute('class', 'form');
$btn_clear = $search->getField('btn_clear');
$btn_clear->addFieldTagAttribute('onclick', 'clearSearch()');
$submitBtn = $search->getField('btn_submit');
$submitBtn->developerTags['col'] = 6;
?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
                <a href="javascript:void(0);" onclick="exportCSV();" class="btn btn-primary"><?php echo Label::getLabel('LBL_EXPORT'); ?></a>
            </div>
        </div>
        <div class="card">
            <div class="card-head js--filter-trigger">
                <h4> <?php echo Label::getLabel('LBL_Search'); ?></h4>
            </div>
            <div class="card-body js--filter-target" style="display:none;">
                <?php echo $search->getFormHtml(); ?>
            </div>
        </div>
        <div class="card">
            <div class="card-table">
                <div id="listing" class="table-responsive">
                    <div class="table-processing loaderJs">
                        <div class="spinner spinner--sm spinner--brand"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>