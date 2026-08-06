<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
if (count($resources) == 0) {
?>
    <tr>
        <td colspan="4" class="-no-shadow">
            <?php $this->includeTemplate('_partial/no-record-found.php'); ?>
        </td>
    </tr>
    <?php
} else {
    $titleLabel = Label::getLabel('LBL_FILENAME');
    $typeLabel = Label::getLabel('LBL_TYPE');
    $dateLabel = Label::getLabel('LBL_DATE');
    foreach ($resources as $resrc) { ?>
        <tr>
            <td>
                <div class="flex-cell">
                    <div class="flex-cell__label"></div>
                    <div class="flex-cell__content">
                        <?php
                        $fld = $resrcFrm->getField('resources[]');
                        ?>
                        <label class="checkbox">
                            <input type="checkbox" name="resources[]" value="<?php echo $resrc['resrc_id']; ?>">
                            <i class="input-helper"></i>
                        </label>
                    </div>
                </div>
            </td>
            <td>
                <div class="flex-cell">
                    <div class="flex-cell__label"><?php echo $titleLabel; ?></div>
                    <div class="flex-cell__content">
                        <div class="file-attachment">
                            <div class="d-flex">
                                <div class="file-attachment__media d-none d-sm-flex">
                                    <svg class="attached-media">
                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#<?php echo Resource::getFileIcon($resrc['resrc_type']); ?>"></use>
                                    </svg>
                                </div>
                                <div class="file-attachment__content">
                                    <p class="mb-0 bold-600 color-black">
                                        <?php echo $resrc['resrc_name']; ?>
                                    </p>
                                    <span class="m-0 style-italic me-3 color-gray-900">
                                        <?php echo $resrc['resrc_size']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </td>
            <td>
                <div class="flex-cell">
                    <div class="flex-cell__label"><?php echo $typeLabel; ?></div>
                    <div class="flex-cell__content">
                        <div style="max-width: 250px;"><?php echo strtoupper($resrc['resrc_type']); ?></div>
                    </div>
                </div>
            </td>
            <td>
                <div class="flex-cell">
                    <div class="flex-cell__label"><?php echo $dateLabel; ?></div>
                    <div class="flex-cell__content"><?php echo MyDate::showDate($resrc['resrc_created'], true); ?></div>
                </div>
            </td>
        </tr>
<?php
    }
}
