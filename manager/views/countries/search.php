<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'country_flag' => Label::getLabel('LBL_FLAG'),
    'country_identifier' => Label::getLabel('LBL_IDENTIFIER'),
    'country_name' => Label::getLabel('LBL_Name'),
    'country_code' => Label::getLabel('LBL_Code'),
    'country_dial_code' => Label::getLabel('LBL_DIAL_CODE'),
    'country_active' => Label::getLabel('LBL_Status'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['country_id']);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'country_flag':
                $td->appendElement('img', ['src' => CONF_WEBROOT_FRONTEND . 'flags/' . strtolower($row['country_code']) . '.svg', 'style' => 'border:1px solid #DDD;width:30px;'], '');
                break;
            case 'action':
                $action = new Action($row['country_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_Edit'), 'editCountryFormNew(' . $row['country_id'] . ')');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            case 'country_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['country_active'] == AppConstant::ACTIVE) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                } else {
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['country_id'] . '" class="statustab status_' . $row['country_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                    <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                    <span class="switch-handles ' . $statusClass . '"></span>
                  </label>';
                $td->appendElement('plaintext', [], $str, true);
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
