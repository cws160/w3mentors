<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frmSearch->setFormTagAttribute('onsubmit', 'searchQuestions(this); return(false);');
$frmSearch->setFormTagAttribute('class', 'form');
$frmSearch->developerTags['colClassPrefix'] = 'col-md-';
$frmSearch->developerTags['fld_default_col'] = 3;
$submitBtn = $frmSearch->getField('btn_submit');
$submitBtn->developerTags['col'] = 6;
$fld = $frmSearch->getField('btn_clear');
$fld->addFieldTagAttribute('onclick', 'clearSearch()');
$catefld = $frmSearch->getField('ques_cate_id');
$catefld->addFieldTagAttribute('onchange', 'getSubcategories(this.value)');

$subcatefld = $frmSearch->getField('ques_subcate_id');
$subcatefld->addFieldTagAttribute('id', 'subCategories')
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
                <?php echo $frmSearch->getFormHtml(); ?>
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