<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($notes) == 0) {
    $this->includeTemplate('_partial/no-record-found-notes.php', ['isPreview' => $isPreview, 'ordcrsId' => $post['ordcrs_id']]);
    return;
}
?>
<?php foreach ($notes as $note) { ?>
    <div class="notes">
        <div class="notes__body">
            <div class="notes__content">
                <h6 class="notes__title"><?php echo $note['lecture_order'] . '. ' . $note['lecture_title']; ?></h6>
                <p><?php echo nl2br($note['lecnote_notes']); ?></p>
            </div>
            <div class="notes__actions">
                <div class="actions-group">
                    <?php if ($isPreview == 0) { ?>
                        <a href="javascript:void(0);" onclick="notesForm('<?php echo $note['lecnote_id']; ?>', '<?php echo $post['ordcrs_id'] ?>');" class="btn btn--equal btn--transparent color-black is-hover">
                            <svg class="icon icon--edit icon--small">
                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#edit"></use>
                            </svg>
                            <div class="tooltip tooltip--top bg-black">
                                <?php echo Label::getLabel('LBL_EDIT'); ?>
                            </div>
                        </a>
                        <a href="javascript:void(0);" onclick="removeNotes('<?php echo $note['lecnote_id']; ?>', '<?php echo $post['ordcrs_id'] ?>');" class="btn btn--equal btn--transparent color-black is-hover">
                            <svg class="icon icon--edit icon--small">
                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#delete-icon"></use>
                            </svg>
                            <div class="tooltip tooltip--top bg-black">
                                <?php echo Label::getLabel('LBL_DELETE'); ?>
                            </div>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
<?php
$pagingArr = [
    'page' => $post['page'],
    'pageSize' => $post['pagesize'],
    'pageCount' => ceil($recordCount / $post['pagesize']),
    'recordCount' => $recordCount,
    'callBackJsFunc' => 'goToNotesSearchPage'
];

$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmNotesPaging']);
?>