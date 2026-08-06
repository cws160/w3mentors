<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_ADD_USER_TRANSACTIONS'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a href="javascript:void(0);" onclick="transactions(<?php echo $userId; ?>);"><?php echo Label::getLabel('LBL_TRANSACTIONS'); ?></a></li>
            <li><a class="active" href="javascript:void(0);"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php
    $frm->developerTags['colClassPrefix'] = 'col-md-';
    $frm->developerTags['fld_default_col'] = 12;
    $frm->setFormTagAttribute('id', 'addressFrm');
    $frm->setFormTagAttribute('class', 'form');
    $frm->setFormTagAttribute('onsubmit', 'setupTransaction(this); return(false);');
    echo $frm->getFormHtml();
    ?>
</div>