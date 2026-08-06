<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
if (0 == count($records)) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$arrFlds['listserial'] = Label::getLabel('LBL_SR_NO');
$arrFlds['fquecom_comment'] = Label::getLabel('LBL_Forum_Comment');
$arrFlds['user_name'] = Label::getLabel('LBL_Forum_commented_by');
$arrFlds['fquecom_accepted'] = Label::getLabel('LBL_Forum_comment_Accepted');
$arrFlds['fstat_likes'] = Label::getLabel('LBL_Forum_Comment_likes');
$arrFlds['fstat_dislikes'] = Label::getLabel('LBL_Forum_Comment_dislikes');
$arrFlds['fquecom_added_on'] = Label::getLabel('LBL_Forum_Comment_Added_on');

$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered table-dragable', 'id' => 'teachingLangages']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($records as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['fque_id']);
    if (1 == $row['fque_deleted']) {
        $tr->setAttribute("class", 'disabled');
    }
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;

            case 'user_name':
                $td->appendElement('plaintext', [], $row['user_first_name'] . ' ' . $row['user_last_name']);
                break;
            case 'fquecom_comment':
                $td->appendElement('div', [], $row['fquecom_comment'], true);
                break;
            case 'fquecom_added_on':
                $td->appendElement('div', [], MyDate::showDate($row['fquecom_added_on'], true), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}

if (count($records) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'pageSize' => $post['pagesize'], 'page' => $post['pageno'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
