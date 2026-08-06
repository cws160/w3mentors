<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="card">
            <div class="card-head">
                <div class="card-head-label">
                    <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Sent_Emails_List'); ?></h3>
                </div>
            </div>
            <div class="card-table">
                <div id="emails-list" class="table-responsive">
                    <div class="table-processing loaderJs">
                        <div class="spinner spinner--sm spinner--brand"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>