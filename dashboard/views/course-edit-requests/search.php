<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($editRequests) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive table--aligned-middle">
        <tr class="title-row">
            <th><?php echo $nameLabel = Label::getLabel('LBL_COURSE_TITLE'); ?></th>
            <th><?php echo $startLabel = Label::getLabel('LBL_CREATED_DATE'); ?></th>
            <th><?php echo $endLabel = Label::getLabel('LBL_EXPIRY_DATE'); ?></th>
            <th><?php echo $statusLabel = Label::getLabel('LBL_REQUEST_STATUS'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTIONS'); ?></th>
        </tr>
        <?php
        $naLabel = Label::getLabel('LBL_NA');
        $statuses = Course::getEditRequestStatuses();
        foreach ($editRequests as $editRequest) {
        ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $nameLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="profile-meta">
                                <div class="profile-meta__details">
                                    <p class="bold-600 color-black"><?php echo $editRequest['course_title']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $startLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyDate::showDate($editRequest['coedre_created'], true); ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $endLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyDate::showDate($editRequest['coedre_expired'], true); ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $statusLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php
                            $status = $statuses[$editRequest['coedre_status']];
                            echo $status;
                            ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <a href="javascript:;" onclick="view('<?php echo $editRequest['coedre_id'] ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--cancel icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#view'; ?>"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_VIEW_REQUEST'); ?></div>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php
$pagingArr = [
    'pageSize' => $post['pagesize'],
    'page' => $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize'])
];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
?>