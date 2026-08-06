<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="main mainJs">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="card">
            <div class="card-head js--filter-trigger">
                <h4><?php echo Label::getLabel('LBL_SEARCH'); ?></h4>
            </div>
            <div class="card-body js--filter-target" style="display:none;">
                <?php
                $frmSearch->setFormTagAttribute('onsubmit', 'searchPageLangData(this); return(false);');
                $frmSearch->setFormTagAttribute('class', 'form');
                $frmSearch->developerTags['colClassPrefix'] = 'col-md-';
                $frmSearch->developerTags['fld_default_col'] = 4;
                $btn_clear = $frmSearch->getField('btn_clear');
                $btn_clear->addFieldTagAttribute('onclick', 'clearSearch()');
                echo $frmSearch->getFormHtml();
                ?>
            </div>
        </div>
        <section class="card">
            <div class="card-table">
                <div id="listing">
                    <?php echo Label::getLabel('LBL_Processing...'); ?>
                </div>
            </div>
        </section>
    </div>
</div>