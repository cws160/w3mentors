<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$disabledHelpTxt = '';
if ($isCourseRemoved == false) {
    $disabledHelpTxt = "<div class='page-alert'>" .
        "<div class='alert alert--info'>
            <span>" . Label::getLabel('LBL_INFO_FOR_DISABLED_COURSES_STATS').
    "</span>" .
        "</div>" .
        "</div>";
} ?>
<?php if ($isGroupClassRemoved == false) {
    $disabledHelpTxt = "<div class='page-alert'>" .
        "<div class='alert alert--info'>
            <span>" . Label::getLabel('LBL_INFO_FOR_DISABLED_CLASSES_STATS').
    "</span>" .
        "</div>" .
        "</div>";
} ?>
<?php if ($isCourseRemoved == false && $isGroupClassRemoved  == false) {
    $disabledHelpTxt = "<div class='page-alert'>" .
        "<div class='alert alert--info'>
            <span>" . Label::getLabel('LBL_INFO_FOR_DISABLED_COURSE_CLASSES_STATS').
    "</span>" .
        "</div>" .
        "</div>";
} ?>
<?php echo  $disabledHelpTxt; ?>
<main class="main">
    <div class='container'>
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
                <span class="-color-secondary span-right pt-2" id="regendatedtime"><?php echo $regendatedtime; ?></span>
                <?php if($canEditReportStatsRegenerate) { ?> 
                <a href="javascript:void(0);" onclick="regenerate();" class="btn btn-primary"><?php echo Label::getLabel('LBL_REGENERATE'); ?></a>
                <?php } ?>
                <a href="javascript:void(0);" onclick="exportCSV();" class="btn btn-primary"><?php echo Label::getLabel('LBL_EXPORT'); ?></a>
            </div>
        </div>
        <div class="card">
            <div class="card-head js--filter-trigger">
                <h4> <?php echo Label::getLabel('LBL_Search...'); ?></h4>
            </div>
            <div class="card-body js--filter-target" style="display:none;">
                <?php
                $frm->setFormTagAttribute('onsubmit', 'search(this); return(false);');
                $frm->setFormTagAttribute('class', 'form');
                $fld = $frm->getField('btn_clear');
                $fld->setFieldTagAttribute('onclick', 'clearSearch()');
                echo $frm->getFormHtml();
                ?>
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