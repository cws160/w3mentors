<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="card">
            <div class="card-head js--filter-trigger">
                <h4> <?php echo Label::getLabel('LBL_Search...'); ?></h4>
            </div>
            <div class="card-body js--filter-target" style="display:none;">
                <?php
                $search->setFormTagAttribute('onsubmit', 'search(this); return(false);');
                $search->setFormTagAttribute('id', 'frmSearch');
                $search->setFormTagAttribute('class', 'form');
                $search->developerTags['colClassPrefix'] = 'col-md-';
                $search->developerTags['fld_default_col'] = 6;
                $search->getField('keyword')->addFieldtagAttribute('class', 'search-input');
                $search->getField('btn_clear')->addFieldtagAttribute('onclick', 'clearSearch();');
                echo $search->getFormHtml();
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