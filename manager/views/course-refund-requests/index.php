<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frmSearch->setFormTagAttribute('onsubmit', 'search(this); return(false);');
$frmSearch->setFormTagAttribute('id', 'search');
$frmSearch->setFormTagAttribute('class', 'form');
$frmSearch->getField('keyword')->addFieldtagAttribute('placeholder', Label::getLabel('LBL_SEARCH_BY_COURSE_TITLE_OR_SUBTITLE'));
$btn = $frmSearch->getField('btn_clear');
$btn->setFieldTagAttribute('onClick', 'clearSearch()');
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
                <h4><?php echo Label::getLabel('LBL_SEARCH'); ?></h4>
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
    var REFUND_DECLINED = "<?php echo Course::REFUND_DECLINED ?>";
    var refundApproved = "<?php echo $frmSearch->getField('corere_status')->value ?? 0; ?>";
    $(document).ready(function() {
        if (refundApproved > 0) {
            $('.card-head.js--filter-trigger').click();
        }
    });
</script>