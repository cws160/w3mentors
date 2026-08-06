<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$allAccessfrm->developerTags['colClassPrefix'] = 'col-md-';
$allAccessfrm->developerTags['fld_default_col'] = 12;
?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <?php echo $frm->getFormHtml(); ?>
        </div>
        <div class="card">
            <div class="card-head">
                <div class="card-head-label">
                    <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Admin_User_Listing'); ?> : <?php echo $data['admin_username']; ?></h3>
                </div>
            </div>
            <?php if ($canEdit) { ?>
                <div class="card-body">
                    <?php echo $allAccessfrm->getFormHtml(); ?>
                </div>
            <?php } ?>
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