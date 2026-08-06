<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'bpcomment_author_name' => Label::getLabel('LBL_Author_Name'),
    'bpcomment_author_email' => Label::getLabel('LBL_Author_Email'),
    'bpcomment_content' => Label::getLabel('LBL_Comment'),
    'bpcomment_approved' => Label::getLabel('LBL_Status'),
    'post_title' => Label::getLabel('LBL_Post_Title'),
    'bpcomment_added_on' => Label::getLabel('LBL_Posted_On'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'post']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td', ['style' => 'max-width:300px;']);
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'bpcomment_added_on':
                $td->appendElement('plaintext', [], MyDate::showDate($row['bpcomment_added_on'], true));
                break;
            case 'bpcomment_author_name':
                $td->appendElement('plaintext', [], ucfirst($row[$key]), true);
                break;
            case 'bpcomment_approved':
                $td->appendElement('plaintext', [], BlogPost::getCommentStatuses($row[$key]), true);
                break;
            case 'post_title':
                $td->appendElement('plaintext', [], ucfirst($row[$key]), true);
                break;
            case 'bpcomment_content':
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'action':
                $action = new Action($row['bpcomment_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'view(' . $row['bpcomment_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord(' . $row['bpcomment_id'] . ')');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], nl2br($row[$key]), true);
                break;
        }
    }
}
if (count($arr_listing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
