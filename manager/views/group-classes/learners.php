<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="card">
            <div class="card-table">
                <div id="listItems" class="table-responsive">
                    <div class="table-processing loaderJs">
                        <div class="spinner spinner--sm spinner--brand"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo $frmSrch->getFormHtml(); ?>
</main>