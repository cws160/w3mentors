<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [
    'srno' => Label::getLabel('LBL_SRNO'),
    'affiliate_name' => Label::getLabel('LBL_AFFILIATE'),
    'afstat_referees' => Label::getLabel('LBL_REFEREE_COUNT'),
    'afstat_referee_sessions' => Label::getLabel('LBL_SESSIONS_COUNT'),
    'afstat_signup_revenue' => Label::getLabel('LBL_SIGN-UP_REVENUE'),
    'afstat_order_revenue' => Label::getLabel('LBL_SESSION_REVENUE'),
    'total_revenue' => Label::getLabel('LBL_TOTAL_REVENUE')
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srno = $page == 1 ? 0 : $postedData['pagesize'] * ($page - 1);
foreach ($records as $sn => $row) {
    $srno++;
    $tr = $tbl->appendElement('tr');
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'srno':
                $td->appendElement('plaintext', [], $srno);
                break;
            case 'afstat_signup_revenue':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row['afstat_signup_revenue']));              
                break;
            case 'afstat_order_revenue':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row['afstat_order_revenue']));               
                break;
            case 'total_revenue':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row['afstat_signup_revenue'] + $row['afstat_order_revenue']));
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($records) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$this->includeTemplate('_partial/pagination.php', ['pageCount' => $pageCount, 'recordCount' => $recordCount, 'page' => $page], false);
