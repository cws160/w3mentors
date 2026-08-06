<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'order_id' => Label::getLabel('LBL_ORDER_ID'),
    'user_full_name' => Label::getLabel('LBL_USER_NAME'),
    'order_total_amount' => Label::getLabel('LBL_TOTAL'),
    'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
    'order_addedon' => Label::getLabel('LBL_DATETIME'),
    'action' => Label::getLabel('LBL_ACTION'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($orders as $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'order_id':
                $td->appendElement('plaintext', [], Order::formatOrderId($row[$key]));
                break;
            case 'user_full_name':
                $td->appendElement('plaintext', [], $row['user_full_name'], true);
                break;
            case 'buyer_user_name':
                $td->appendElement('plaintext', [], $row[$key] . '<br/>' . $row['buyer_email'], true);
                break;
            case 'order_total_amount':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row['order_total_amount']), true);
                break;
            case 'order_addedon':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                break;
            case 'order_payment_status':
                $td->appendElement('span', [], Order::getPaymentArr($row[$key]));
                break;
            case 'action':
                $action = new Action($row['order_id']);
                $action->addOtherBtn(Label::getLabel('LBL_DOWNLOAD_INVOICE'), 'javascript:void(0)', 'download', MyUtility::makeUrl('Orders', 'viewInvoice', [$row['order_id']]).'?t='.time(), '_blank');
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key]);
                break;
        }
    }
}
if (count($orders) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'pageSize' => $post['pagesize'], 'page' => $post['pageno'], 'pageSize' => $post['pagesize'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
