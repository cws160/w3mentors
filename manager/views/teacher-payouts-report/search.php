<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [
    'srno' => Label::getLabel('LBL_SRNO'),
    'user_name' => Label::getLabel('LBL_TEACHER_NAME'),
    'total_amount' => Label::getLabel('LBL_AMOUNT_PAID'),
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
            case 'user_name':
                $td->appendElement('plaintext', [], $row[$key] . '<br/>' . Label::getLabel('LBL_USER_ID') . ': ' . $row['user_id'], true);
                break;
            case 'total_amount':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row[$key]));
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
