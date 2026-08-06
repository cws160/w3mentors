<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$frmSearch->setFormTagAttribute('style', 'display:none;');
?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
                <?php if ($canEdit) { ?>
                    <a href="javascript:void(0);" onclick="preferenceForm(0, '<?php echo $type; ?>');" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
                <?php } ?>
                <a href="javascript:void(0);" onclick="exportCSV();" class="btn btn-primary"><?php echo Label::getLabel('LBL_EXPORT'); ?></a>
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
            <?php echo $frmSearch->getFormHtml(); ?>
        </div>
    </div>
</main>