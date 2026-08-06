<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setupNavigationLink(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$nlink_typeFld = $frm->getField('nlink_type');
$nlink_typeFld->setFieldTagAttribute('onchange', 'callPageTypePopulate(this)');
$nlink_cpage_idFld = $frm->getField('nlink_cpage_id');
$nlink_cpage_idFld->setWrapperAttribute('id', 'nlink_cpage_id_div');
$nlink_urlFld = $frm->getField('nlink_url');
$nlink_urlFld->setWrapperAttribute('id', 'nlink_url_div');
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Navigation_Link_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a class="active" href="javascript:void(0)" onclick="navigationLinkForm(<?php echo $nav_id . ',' . $nlink_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php
            $inactive = ($nlink_id == 0) ? 'is-inactive' : '';
            foreach ($languages as $langId => $langName) {
            ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php if ($nlink_id > 0) { ?> onclick="navigationLinkLangForm(<?php echo $nav_id; ?>,<?php echo $nlink_id ?>,<?php echo $langId; ?>);" <?php } ?>>
                        <?php echo $langName; ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>
<script>
    $(document).ready(function() {
        callPageTypePopulate($("select[name='nlink_type']"));
    });
</script>