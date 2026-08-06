<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'afcomm_user_id' => Label::getLabel('LBL_AFFILIATE'),
    'afcomm_commission' => Label::getLabel('LBL_COMMISSION_[%]'),
    'action' => Label::getLabel('LBL_ACTION')
];
if (!Course::isEnabled()) {
    unset($arrFlds['comm_courses']);
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'afcomm_user_id':
                $str = "<span class='label label-success'>" . Label::getLabel('LBL_GLOBAL_COMMISSION') . "</span>";
                if (!empty($row['user_id'])) {
                    $str = $row['user_first_name'] . ' ' . $row['user_last_name'];
                }
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['afcomm_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'commissionForm(' . $row['afcomm_id'] . ')');
                    if (!empty($row['user_id'])) {
                       $action->addOtherBtn(Label::getLabel('LBL_DELETE'), 'remove(' . $row['afcomm_id'] . ')','delete','javascript:void(0)');
                    }
                }
                $action->addOtherBtn(Label::getLabel('LBL_HISTORY'), 'viewHistory(' . $row['user_id'] . ')','history','javascript:void(0)');
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key] ?? '-');
                break;
        }
    }
}

if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'frmCommPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'pageSize' => $pageSize, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
