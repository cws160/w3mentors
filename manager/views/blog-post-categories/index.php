<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$searchFrm->setFormTagAttribute('onsubmit', 'searchBlogPostCategories(this); return(false);');
$searchFrm->setFormTagAttribute('class', 'form');
$btn_clear = $searchFrm->getField('btn_clear');
$btn_clear->addFieldTagAttribute('onclick', 'clearSearch()');
$submitBtn = $searchFrm->getField('btn_submit');
$submitBtn->developerTags['col'] = 6;
$searchFrm->setFormTagAttribute('style', 'display:none');
?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
                <?php if ($bpcategory_parent) { ?>
                    <a href="<?php echo MyUtility::makeUrl('BlogPostCategories'); ?>" class="btn btn-primary"><?php echo Label::getLabel('LBL_BACK'); ?></a>
                <?php } ?>
                <?php if ($canEdit) { ?>
                    <a href="javascript:void(0);" onclick="addCategoryForm(0);" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
                <?php } ?>
                <a href="javascript:void(0);" onclick="exportCSV();" class="btn btn-primary"><?php echo Label::getLabel('LBL_EXPORT'); ?></a>
            </div>
        </div>
        <?php  echo $searchFrm->getFormHtml();  ?>
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