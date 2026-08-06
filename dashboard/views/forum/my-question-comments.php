<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (0 == count($records)) { ?>
    <div class="modal-header">
        <h5><?php echo Label::getLabel('LBL_QUESTION_COMMENTS'); ?></h5>
        <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
    </div>
    <?php $this->includeTemplate('_partial/no-record-found.php'); ?>
<?php return;
}
$arrFields = [
    'user_detail' => Label::getLabel('LBL_Comment_basic_info'),
    'fquecom_comment' => Label::getLabel('LBL_Forum_Comment'),
];
$tbl = new HtmlElement('table', ['class' => 'table table--bordered table--responsive']);
$th = $tbl->appendElement('tr', ['class' => 'title-row']);
foreach ($arrFields as $key => $val) {
    if ($key == 'fquecom_comment') {
        $th->appendElement('th', ["width" => "65%"], $val);
        continue;
    }
    $th->appendElement('th', [], $val);
}
foreach ($records as $sn => $comment) {
    $tr = $tbl->appendElement('tr', ['id' => 'myqueid_' . $comment['fquecom_id']]);
    foreach ($arrFields as $key => $val) {
        $div = $tr->appendElement('td')->appendElement('div', ['class' => 'flex-cell']);
        $div->appendElement('div', ['class' => 'flex-cell__label'], $val, true);
        switch ($key) {
            case 'fquecom_comment':
                $div->appendElement('div', ['class' => 'flex-cell__content'], nl2br($comment[$key]), true);
                break;
            case 'user_detail':
                $dv = $div->appendElement('div', ['class' => 'flex-cell__content d-sm-block']);
                $dv->appendElement('div', [], '<strong>' . Label::getLabel('LBL_User') . ':</strong> ' . $comment['user_first_name'] . ' ' . $comment['user_last_name'], true);
                $dv->appendElement('div', [], '<strong>' . Label::getLabel('LBL_Accepted') . ':</strong> ' . (1 == $comment['fquecom_accepted'] ? Label::getLabel('LBL_Yes') : '-'), true);
                $dv->appendElement('div', [], '<strong>' . Label::getLabel('LBL_Added_on') . ':</strong> ' . MyDate::showDate($comment['fquecom_added_on'], true), true);
                $dv->appendElement('div', [], '<strong>' . Label::getLabel('LBL_Likes') . ':</strong> ' . $comment['fstat_likes'], true);
                $dv->appendElement('div', [], '<strong>' . Label::getLabel('LBL_Dislikes') . ':</strong> ' . $comment['fstat_dislikes'], true);
                break;
            default:
                $div->appendElement('div', ['class' => 'flex-cell__content'], $comment[$key], true);
                break;
        }
    }
}
?>

<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_QUESTION_COMMENTS'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <?php echo $tbl->getHtml(); ?>
</div>
<?php
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'frmUserSearchPaging']);
$pagingArr = [
    'pageCount' => $pageCount,
    'page' => $page,
    'pageSize' => $pageSize,
    'recordCount' => $recordCount,
    'callBackJsFunc' => 'goToCommentsSearchPage'
];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>