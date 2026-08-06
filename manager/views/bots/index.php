<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form layout--');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = '12';
if (!$canEdit) {
    $submitBtn = $frm->getField('btn_submit');
    $frm->removeField($submitBtn);
}
?>

<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <?php echo $frm->getFormHtml(); ?>
            </div>
        </div>
    </div>
</main>