<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<option value=""><?php echo Label::getLabel('LBL_SELECT'); ?></option>
<?php
if (count($subcategories) > 0) {
    foreach ($subcategories as $id => $name) {
        ?>
        <option <?php echo (($subCatgId == $id) ? 'selected = "selected"' : ''); ?> value="<?php echo $id; ?>">
            <?php echo $name; ?>
        </option><?php
    }
}