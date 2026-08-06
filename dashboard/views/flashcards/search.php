<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (empty($cards)) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive table--aligned-middle">
        <tr class="title-row">
            <th><?php echo $teachlangLabel = Label::getLabel('LBL_Language'); ?></th>
            <th><?php echo $titleLabel = Label::getLabel('LBL_Title'); ?></th>
            <th><?php echo $detailLabel = Label::getLabel('LBL_Detail'); ?></th>
            <th><?php echo $dateLabel = Label::getLabel('LBL_Date'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_Action'); ?></th>
        </tr>
        <?php foreach ($cards as $card) { ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $teachlangLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo $card['tlang_name'] ?? Label::getLabel('LBL_FREE_TRIAL'); ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $titleLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo $card['flashcard_title']; ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $detailLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo nl2br($card['flashcard_detail']); ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $dateLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyDate::showDate($card['flashcard_addedon'], true); ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="actions-group">
                                <a href="javascript:void(0)" onclick="form(<?php echo $card['flashcard_id']; ?>)" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--issue icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#edit'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_Edit'); ?></div>
                                </a>
                                <a href="javascript:void(0);" onclick="remove('<?php echo $card['flashcard_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                    <svg class="icon icon--issue icon--small">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#trash'; ?>"></use>
                                    </svg>
                                    <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_Remove'); ?></div>
                                </a>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'frmSearchPaging']);
$pagingArr = [
    'page' => $postedData['pageno'],
    $postedData['pageno'],
    'pageSize' => $postedData['pagesize'],
    'pageCount' => $pageCount,
    'recordCount' => $recordCount
];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>