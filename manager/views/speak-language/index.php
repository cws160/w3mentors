<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
                <?php if ($canEdit) { ?>
                    <a href="javascript:void(0);" onclick="form(0, 0);" class="btn btn-primary"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a>
                <?php } ?>
                <a href="javascript:void(0);" onclick="exportCSV();" class="btn btn-primary"><?php echo Label::getLabel('LBL_EXPORT'); ?></a>
            </div>
        </div>
        <section>
            <div style="display:none;">
                <?php
                $frmSearch->setFormTagAttribute('onsubmit', 'search(this); return(false);');
                $frmSearch->setFormTagAttribute('class', 'form');
                $frmSearch->developerTags['colClassPrefix'] = 'col-md-';
                $frmSearch->developerTags['fld_default_col'] = 4;
                $btn_clear = $frmSearch->getField('btn_clear');
                $btn_clear->addFieldTagAttribute('onclick', 'clearSearch()');
                echo $frmSearch->getFormHtml();
                ?>
            </div>
        </section>
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