<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$form->setFormTagAttribute('onsubmit', 'search(this); return(false);');
$form->setFormTagAttribute('class', 'form');
$form->developerTags['colClassPrefix'] = 'col-md-';
$form->developerTags['fld_default_col'] = 3;
$submitBtn = $form->getField('btn_submit');
$submitBtn->developerTags['col'] = 6;
$fld = $form->getField('btn_clear');
$fld->addFieldTagAttribute('onclick', 'clearSearch()');
?>
<script>
    var STATUS_APPROVED = <?php echo TeacherRequest::STATUS_APPROVED; ?>;
    var STATUS_CANCELLED = <?php echo TeacherRequest::STATUS_CANCELLED; ?>;
</script>
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
                <h4><?php echo Label::getLabel('LBL_SEARCH'); ?></h4>
            </div>
            <div class="card-body js--filter-target" style="display:none;">
                <?php echo $form->getFormHtml(); ?>
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