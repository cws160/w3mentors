<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<option value=""><?php echo Label::getLabel('LBL_ROOT_CATEGORY'); ?></option>
<?php if (count($categories) > 0) { ?>
    <?php foreach ($categories as $id => $name) { ?>
    <option <?php /* echo (($subCatgId == $id) ? 'selected = "selected"' : ''); */ ?> value="<?php echo $id; ?>">
        <?php echo $name; ?>
    </option>
    <?php } ?>
<?php } ?>