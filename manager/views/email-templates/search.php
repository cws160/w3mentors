<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'etpl_name' => Label::getLabel('LBL_name'),
    'etpl_subject' => Label::getLabel('LBL_subject'),
    'etpl_status' => Label::getLabel('LBL_Status'),
];
$arr_flds['action'] = Label::getLabel('LBL_Action');
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no, true);
                break;
            case 'etpl_status':
                $active = "active";
                $statucAct = '';
                if ($row['etpl_status'] == AppConstant::YES && $canEdit === true) {
                    $active = 'active';
                    $statucAct = 'inactiveStatus(this)';
                }
                if ($row['etpl_status'] == AppConstant::NO && $canEdit === true) {
                    $active = 'inactive';
                    $statucAct = 'activeStatus(this)';
                }

                $statusClass = "";
                if ($canEdit === false) {
                    $statusClass = "disabled";
                }

                $str = '<label id="' . $row['etpl_code'] . '" class="statustab ' . $active . ' status_' . $row['etpl_code'] . '" onclick="' . $statucAct . '">
				  <span data-off="' . Label::getLabel('LBL_Active') . '" data-on="' . Label::getLabel('LBL_Inactive') . '" class="switch-labels "></span>
				  <span class="switch-handles '. $statusClass.'"></span>
				</label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['etpl_code']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'editEtplLangForm("' . $row['etpl_code'] . '",' . $langId . ')');
                }
                if ($row['etpl_code'] != 'emails_header_footer_layout') {
                    $action->addOtherBtn(Label::getLabel('LBL_Preview'), 'javascript:void(0)', 'preview', MyUtility::makeUrl('EmailTemplates', 'preview', [$row['etpl_code'], $row['etpl_lang_id']]),'_blank');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
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
