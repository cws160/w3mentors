<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (0 < count($scbscTags)) { ?>
    <div class="tags">
        <div class="tags__overflow">
            <?php foreach ($scbscTags as $sn => $row) { ?>
                <a id="subscribedtag_<?php echo $row['ftag_id']; ?>" href="javascript:void(0);" class="tags__item badge badge--curve color-primary">
                    <?php echo $row['ftag_name']; ?>
                    <span class="ms-3" onclick="unScubscribe(this);" data-row_id="<?php echo $row['ftag_id']; ?>">
                        <svg class="icon icon--cancel icon--small">
                            <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/sprite.svg#cancel"></use>
                        </svg>
                    </span>
                    <?php if (AppConstant::YES == $row['ftag_deleted'] || AppConstant::INACTIVE == $row['ftag_active']) { ?>
                        <svg class="icon icon--alert icon--small is-click-js">
                            <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/sprite.svg#alert"></use>
                        </svg>
                        <small style="display:none;" class="note"><?php echo Label::getLabel('LBL_Tag_Inactive_Or_Deleted_So_No_Further_Notification_Will_Receive'); ?></small>
                    <?php } ?>
                </a>
            <?php } ?>
        </div>
    </div>
<?php
} else {
    $this->includeTemplate('_partial/no-record-found.php');
}
?>
<script>
    totalRecords = <?php echo count($scbscTags); ?>;
    $('.is-click-js').click(function() {
        alert('<?php echo Label::getLabel('LBL_Tag_Inactive_Or_Deleted_So_No_Further_Notification_Will_Receive'); ?>');
    });
</script>
<style>
    .tags .icon--alert {
        font-weight: bold;
        fill: var(--color-yellow);
    }
</style>