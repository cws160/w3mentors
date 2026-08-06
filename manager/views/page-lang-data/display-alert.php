<?php if (isset($pageData['plang_warring_msg']) && !empty($pageData['plang_warring_msg'])) { ?>
<div class="alert alert-solid-warning fade alertWarningJs show" role="alert">
    <div class="alert-text"><?php echo CommonHelper::renderHtml($pageData['plang_warring_msg']); ?></div>
    <div class="alert-close">
        <button type="button" class="btn-close closeAlertJs" data-bs-dismiss="alert" aria-label="Close"
            data-name="<?php echo 'alert_' . $pageData['plang_id']; ?>">

        </button>
    </div>
</div>
<?php } ?>