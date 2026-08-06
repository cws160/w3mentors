<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'metool_code' => Label::getLabel('LBL_code'),
    'metool_info' => Label::getLabel('LBL_INFO'),
    'metool_status' => Label::getLabel('LBL_Status'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($records as $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td', ['style' => 'max-width:400px']);
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'metool_info':
                $td->appendElement('plaintext', [], html_entity_decode($row['metool_info']), true);
                break;
            case 'metool_status':
                $newStatus = empty($row['metool_status']) ? '1' : '0';
                $activeClass = empty($row['metool_status']) ? 'inactive' : 'active';
                $changeStatus = ($canEdit && $row['metool_status'] != AppConstant::ACTIVE) ? 'onclick="changeStatus(' . $row['metool_id'] . ',' . $newStatus . ')"' : '';
                $statusClass = "";
                if ($canEdit === false) {
                    $statusClass = "disabled";
                }
                $str = '<label id="' . $row['metool_id'] . '" class="statustab ' . $activeClass . '" ' . $changeStatus . '>
                        <span data-off="' . Label::getLabel('LBL_ACTIVE') . '" data-on="' . Label::getLabel('LBL_INACTIVE') . '" class="switch-labels"></span>
                        <span class="switch-handles '. $statusClass.'"></span>
                        </label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['metool_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'meetingToolForm(' . $row['metool_id'] . ')');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($records) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmMeetingToolSearchPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'page' => $post['pageno'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
