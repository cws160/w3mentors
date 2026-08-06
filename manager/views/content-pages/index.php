<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <?php if ($canEdit) { ?>
                <div class="action-toolbar">
                    <a href="javascript:void(0);" onclick="addFormNew(0);" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
                </div>
            <?php } ?>
        </div>
        <div class="card">
            <div class="card-head js--filter-trigger">
                <h4> <?php echo Label::getLabel('LBL_Search...'); ?></h4>
            </div>
            <div class="card-body js--filter-target" style="display:none;">
                <?php
                $frmSearch->setFormTagAttribute('onsubmit', 'searchPages(this); return(false);');
                $frmSearch->setFormTagAttribute('class', 'form');
                $frmSearch->developerTags['colClassPrefix'] = 'col-md-';
                $frmSearch->developerTags['fld_default_col'] = 4;
                $btn_clear = $frmSearch->getField('btn_clear');
                $btn_clear->addFieldTagAttribute('onclick', 'clearSearch()');
                echo $frmSearch->getFormHtml();
                ?>
            </div>
        </div>
        <div class="card">
            <div class="card-table">
                <div id="pageListing" class="table-responsive">
                    <?php echo Label::getLabel('LBL_Pages'); ?>
                </div>
            </div>
        </div>
    </div>
</main>