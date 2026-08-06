<div class="headerwrap" style="table-layout: fixed;display: table;width: 100%;padding: 12px 0;background: #0000000F;position: revert;">
    <div class="one_third_grid"></div>
    <div class="one_third_grid">
        <a href="javascript:void(0);" onclick="activate(<?php echo $_SESSION['preview_theme'] ?>)" class="btn--small btn btn--primary"><?php echo Label::getLabel('LBL_Activate'); ?></a>
        <a href="<?php echo MyUtility::makeUrl('Themes', 'stopPreview', [], CONF_WEBROOT_BACKEND); ?>" class="btn--small btn btn--primary"><?php echo Label::getLabel('LBL_Close_Preview'); ?></a>
    </div>
    <div class="one_third_grid"></div>
</div>
<style>
    .headerwrap {
        table-layout: fixed;
        display: table;
        width: 100%;
        padding: 12px 0;
        background: #0000000F;
        position: revert;
    }
    .one_third_grid {
        display: table-cell;
        vertical-align: middle;
        width: 33.3%;
        text-align: center;
    }
</style>
<script>
    function activate(themeId) {
        if (themeId < 1) {
            fcom.error(langLbl.invalidRequest);
            return false;
        }
        if (confirm(langLbl.confirmActivate)) {
            fcom.updateWithAjax(fcom.makeUrl('Themes', 'activate', [1], '<?php echo CONF_WEBROOT_BACKEND ?>'), {themeId: themeId}, function (res) {
                window.location.reload();
            });
        }
    }
</script>