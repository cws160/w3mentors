<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds['listserial'] = Label::getLabel('LBL_SR_NO');
$arrFlds['ftag_name'] = Label::getLabel('LBL_Tag_Name');
$arrFlds['ftag_language_id'] = Label::getLabel('LBL_LANGUAGE');
$arrFlds['ftag_active'] = Label::getLabel('LBL_STATUS');
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
    $tr->setAttribute("id", $row['ftag_id']);
    if (1 == $row['ftag_deleted']) {
        $tr->setAttribute("class", 'disabled');
    }
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'ftag_language_id':
                $td->appendElement('plaintext', [], $languages[$row['ftag_language_id']], true);
                break;
            case 'ftag_name':
                $text = $row['ftag_name'];
                if ($row['ftag_deleted']) {
                    $text .= '<br>' . '<span class="text-danger"> ' . Label::getLabel('LBL_Deleted_Record') . ' </span>';
                }
                $td->appendElement('plaintext', [], $text, true);
                break;
            case 'ftag_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['ftag_active'] == AppConstant::YES) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                }
                if ($row['ftag_active'] == AppConstant::NO) {
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false || 1 == $row['ftag_deleted']) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['ftag_id'] . '" class="statustab status_' . $row['ftag_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                        <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                        <span class="switch-handles ' . $statusClass . '"></span>
                    </label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                if ($canEdit) {
                    $action = new Action($row['ftag_id']);
                    if (0 == $row['ftag_deleted']) {
                        $action->addEditBtn(Label::getLabel('LBL_Edit'),  'form(' . $row['ftag_id'] . ')');
                        $action->addRemoveBtn(Label::getLabel('LBL_Delete'),  'deleteRecord(' . $row['ftag_id'] . ')');
                    } elseif (1 == $row['ftag_deleted']) {
                        $action->addOtherBtn(Label::getLabel('LBL_Restore_Tag'),  'restoreTag(' . $row['ftag_id'] . ')', 'icon-restore');
                    }
                    $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
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
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
