<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
define('SYSTEM_FRONT', true);
$url = MyUtility::makeFullUrl('', '', [], CONF_WEBROOT_FRONTEND) . ltrim(MyUtility::makeUrl('Blog', 'postDetail', [$post_id], CONF_WEBROOT_FRONT_URL), '/');
$frm->setFormTagAttribute('id', 'bpCat');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$identiFierFld = $frm->getField('post_identifier');
$identiFierFld->setFieldTagAttribute('onkeyup', "Slugify(this.value,'seourl_custom','post_id');getSlugUrl($(\"#seourl_custom\"),$(\"#seourl_custom\").val())");
$IDFld = $frm->getField('post_id');
$IDFld->setFieldTagAttribute('id', "post_id");
$urlFld = $frm->getField('seourl_custom');
$urlFld->setFieldTagAttribute('id', "seourl_custom");
$urlFld->htmlAfterField = "<small class='text--small'>" .  $url . '</small>';
$urlFld->setFieldTagAttribute('onkeyup', "getSlugUrl(this,this.value)");
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Blog_Post_Setup'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <?php $inactive = ($post_id == 0) ? 'is-inactive' : ''; ?>
            <li><a class="active" href="javascript:void(0);" onclick="blogPostForm(<?php echo $post_id ?>);"><?php echo Label::getLabel('LBL_General'); ?></a></li>
            <?php foreach ($languages as $langId => $langName) { ?>
                <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="<?php echo $langId; ?>" <?php if ($post_id > 0) { ?> onclick="langForm(<?php echo $post_id ?>, <?php echo $langId; ?>);" <?php } ?>><?php echo $langName; ?></a></li>
            <?php } ?>
            <li class="<?php echo $inactive; ?>"><a href="javascript:void(0);" data-id="media" <?php if ($post_id > 0) { ?> onclick="postImages(<?php echo $post_id ?>);" <?php } ?>><?php echo Label::getLabel('LBL_Post_Images'); ?></a></li>
        </ul>
    </nav>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>