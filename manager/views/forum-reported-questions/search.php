<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds['listserial'] = Label::getLabel('LBL_SR_NO');
$arrFlds['fquerep_title'] = Label::getLabel('LBL_Report_Title');
$arrFlds['fque_title'] = Label::getLabel('LBL_Question');
$arrFlds['username'] = Label::getLabel('LBL_Reported_By');
$arrFlds['fquerep_status'] = Label::getLabel('LBL_Status');
$arrFlds['fquerep_added_on'] = Label::getLabel('LBL_ADDED_ON');
if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered table-dragable', 'id' => 'teachingLangages']);
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
    $tr->setAttribute("id", $row['fquerep_id']);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'fquerep_status':
                $reportStatus = ForumQuestion::getReportStatusArray();
                $td->appendElement('plaintext', [], $reportStatus[$row['fquerep_status']]);
                break;
            case 'fquerep_added_on':
                $td->appendElement('plaintext', [], MyDate::showDate($row['fquerep_added_on'], true));
                break;
            case 'action':
                $action = new Action($row['fque_id']);
                $action->addViewBtn(Label::getLabel('LBL_View'),  'view(' . $row['fquerep_id'] . ')');
                if ($canEdit) {
                    if ($row['fquerep_status'] == ForumQuestion::QUEST_REPORTED_PENDING) {
                        $action->addOtherBtn(Label::getLabel('LBL_Action'),  'actionForm(' . $row['fquerep_id'] . ')', 'edit');
                    }
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
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
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
