<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');

$arrFlds = [
    'listserial' => Label::getLabel('LBL_srNo'),
    'ftagreq_username' => Label::getLabel('LBL_User'),
    'ftagreq_name' => Label::getLabel('LBL_TAG'),
    'ftagreq_language_id' => Label::getLabel('LBL_FORUM_LANGUAGE'),
    'ftagreq_status' => Label::getLabel('LBL_status'),
];

if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}

$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered table-dragable', 'id' => 'ForumReportIssueReasons']);
$th = $tbl->appendElement('thead')->appendElement('tr');
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['ftagreq_id']);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'ftagreq_username':
                $td->appendElement('plaintext', [], $row['user_first_name'] . ' ' . $row['user_last_name'], true);
                break;
            case 'ftagreq_name':
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'ftagreq_status':
                $status = Label::getLabel('LBL_NA');
                if (array_key_exists($row[$key], $statusArr)) {
                    $status = $statusArr[$row[$key]];
                }
                $td->appendElement('plaintext', [], $status, true);
                break;
            case 'ftagreq_language_id':
                $language = Label::getLabel('LBL_NA');
                if (array_key_exists($row[$key], $languages)) {
                    $language = $languages[$row[$key]];
                }
                $td->appendElement('plaintext', [], $language, true);
                break;
            case 'ftagreq_status':
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'action':
                if ($canEdit && ForumTagRequest::STATUS_PENDING == $row['ftagreq_status']) {
                    $action = new Action($row['ftagreq_id']);
                    $action->addOtherBtn(Label::getLabel('LBL_Change_Status'),  'getStatusChangeForm(' . $row['ftagreq_id'] . ')', 'edit');
                    $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                } else {
                    $td->appendElement('plaintext', [], Label::getLabel('ERR_NA'), true);
                }
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}

if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();

echo FatUtility::createHiddenFormFromData($post, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'pageSize' => $post['pagesize'], 'page' => $post['pageno'], 'recordCount' => $recordCount];
echo $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
