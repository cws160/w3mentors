<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($resources) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
?>
<table class="table table--styled table--responsive">
    <tr class="title-row">
        <th><?php echo $filename = Label::getLabel('LBL_FILENAME'); ?></th>
        <th><?php echo $typeLabel = Label::getLabel('LBL_TYPE'); ?></th>
        <th><?php echo $dateLabel = Label::getLabel('LBL_DATE'); ?></th>
        <th><?php echo $actionLabel = Label::getLabel('LBL_Actions'); ?></th>
    </tr>
    <?php foreach ($resources as $resrc) { ?>
        <tr>
            <td>
                <div class="flex-cell">
                    <div class="flex-cell__label"><?php echo $filename; ?></div>
                    <div class="flex-cell__content">
                        <div class="file-attachment">
                            <div class="d-flex">
                                <div class="file-attachment__media d-none d-sm-flex">
                                    <svg class="attached-media">
                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#<?php echo Resource::getFileIcon($resrc['resrc_type']); ?>"></use>
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
                    <div class="flex-cell__label"><?php echo $typeLabel; ?> </div>
                    <div class="flex-cell__content">
                        <div style="max-width: 250px;"><?php echo strtoupper($resrc['resrc_type']); ?></div>
                    </div>
                </div>
            </td>
            <td>
                <div class="flex-cell">
                    <div class="flex-cell__label"> <?php echo $dateLabel; ?> </div>
                    <div class="flex-cell__content">
                        <?php echo MyDate::showDate($resrc['resrc_created'], true); ?>
                    </div>
                </div>
            </td>
            <td>
                <div class="flex-cell">
                    <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                    <div class="flex-cell__content">
                        <div class="actions-group">
                            <a href="javascript:void(0);" onclick="remove('<?php echo $resrc['resrc_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--issue icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#trash'; ?>"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_DELETE'); ?></div>
                            </a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    <?php } ?>
</table>
<?php
$pagingArr = [
    'page' => $post['page'],
    'pageSize' => $post['pagesize'],
    'pageCount' => ceil($recordCount / $post['pagesize']),
    'recordCount' => $recordCount,
    'callBackJsFunc' => 'goToSearchPage'
];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmPaging']);
