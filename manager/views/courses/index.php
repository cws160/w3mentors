<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$srchFrm->setFormTagAttribute('onsubmit', 'search(this); return(false);');
$srchFrm->setFormTagAttribute('id', 'frmSearch');
$srchFrm->setFormTagAttribute('class', 'form');
$srchFrm->developerTags['colClassPrefix'] = 'col-md-';
$srchFrm->developerTags['fld_default_col'] = 3;
$srchFrm->getField('keyword')->addFieldtagAttribute('class', 'search-input');
$srchFrm->getField('btn_reset')->addFieldtagAttribute('onclick', 'clearSearch();');
$catefld = $srchFrm->getField('course_cateid');
$subcatefld = $srchFrm->getField('course_subcateid');
$subcatefld->addFieldtagAttribute('id', 'subCategories');
$catefld->addFieldtagAttribute('onchange', 'getSubcategories(this.value);');
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
                <?php echo $srchFrm->getFormHtml(); ?>
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
<script>
    $(document).ready(function() {
        var catId = "<?php echo !empty($catefld->value) ? $catefld->value : 0; ?>";
        if (catId > 0) {
            $('.js--filter-trigger').click();
        }
    });
</script>