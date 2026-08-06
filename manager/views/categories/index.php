<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frmSearch->setFormTagAttribute('onsubmit', 'search(this); return(false);');
$frmSearch->setFormTagAttribute('id', 'search');
$frmSearch->setFormTagAttribute('style', 'display:none');
$action = ($frmSearch->getField('cate_type')->value == Category::TYPE_COURSE) ? 'index' : 'quiz';
?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap breadcrumbJs"  data-menu="<?php echo MyUtility::makeUrl('Categories', $action); ?>">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
                <?php if ($canEdit || $parentId > 0) { ?>
                    <?php if ($parentId > 0) { ?>
                        
                        <a href="<?php echo MyUtility::makeUrl('Categories', $action); ?>" class="btn btn-primary"><?php echo Label::getLabel('LBL_BACK'); ?></a>
                    <?php } ?>
                    <?php if ($canEdit) { ?>
                        <a href="javascript:void(0);" onclick="categoryForm(0, '<?php echo $siteLangId; ?>');" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
                    <?php } ?>
                <?php } ?>
                <a href="javascript:void(0);" onclick="exportCSV();" class="btn btn-primary"><?php echo Label::getLabel('LBL_EXPORT'); ?></a>
            </div>
        </div>
        <?php echo $frmSearch->getFormHtml(); ?>
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