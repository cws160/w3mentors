<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$parentId = $frm->getField('parent_id')->value;
$backId = !empty($langParentId) ? $langParentId : '';
?>

<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            
            <div class="action-toolbar">
                <?php if ($parentId > 0) { ?>
                    <a href="<?php echo MyUtility::makeUrl('TeachLanguage', 'index', [$backId]); ?>" class="btn btn-primary"><?php echo Label::getLabel('LBL_BACK'); ?></a>
                <?php } ?>
                <?php if ($canEdit) { ?>
                    <a href="javascript:void(0);" onclick="form(0, '<?php echo $parentId ?>');" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
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
        </div>
    </div>
</main>
<?php echo $frm->getFormHtml(); ?>
<script>
    var parentId = '<?php echo $parentId ?>';
</script>