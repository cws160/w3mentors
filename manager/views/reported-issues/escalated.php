<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$frm->setFormTagAttribute('onsubmit', 'search(this, 1); return(false);');
$frm->setFormTagAttribute('class', 'form');
$fld = $frm->getField('btn_clear');
$fld->addFieldTagAttribute('onclick', 'clearSearch()');
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
                <h4> <?php echo Label::getLabel('LBL_Search...'); ?></h4>
            </div>
            <div class="card-body js--filter-target" style="display:none;">
                <?php echo $frm->getFormHtml(); ?>
            </div>
        </div>
        <div class="card">
            <div class="card-table">
                <div id="issueListing" class="table-responsive">
                    <div class="table-processing loaderJs">
                        <div class="spinner spinner--sm spinner--brand"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>