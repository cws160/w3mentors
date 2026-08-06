<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'srno' => Label::getLabel('LBL_SRNO'),
    'order_id' => Label::getLabel('LBL_Order_Id'),
    'user_username' => Label::getLabel('LBL_Customer'),
    'order_total_amount' => Label::getLabel('LBL_Amount'),
    'order_addedon' => Label::getLabel('LBL_Date'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $key => $val) {
    $e = $th->appendElement('th', [], $val, true);
}
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($records as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        if (!empty($row['couhis_released'])) {
            $td = $tr->appendElement('td', ['class' => 'text-danger']);
        } else {
            $td = $tr->appendElement('td');
        }
        switch ($key) {
            case 'srno':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'order_id':
                $td->appendElement('plaintext', [], Order::formatOrderId(FatUtility::int($row[$key])), true);
                break;
            case 'user_username':
                $td->appendElement('plaintext', [], $row['user_first_name'] . ' ' . $row['user_last_name'], true);
                break;
            case 'order_total_amount':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row[$key]), true);
                break;
            case 'couhis_added_on':
            case 'order_addedon':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($records) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();

echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'frmSearchPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
