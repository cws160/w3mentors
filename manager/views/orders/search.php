<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'order_id' => Label::getLabel('LBL_ORDER_ID'),
    'learner_full_name' => Label::getLabel('LBL_USER_NAME'),
    'order_type' => Label::getLabel('LBL_ORDER_TYPE'),
    'service_type' => Label::getLabel('LBL_SERVICE_TYPE'),
    'order_net_amount' => Label::getLabel('LBL_NET_TOTAL'),
    'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
    'order_status' => Label::getLabel('LBL_STATUS'),
    'order_addedon' => Label::getLabel('LBL_DATETIME'),
    'action' => Label::getLabel('LBL_ACTION'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
foreach ($orders as $row) {
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'action':
                $action = new Action($row['order_id']);
                $action->addOtherBtn(Label::getLabel('LBL_VIEW'), 'javascript:void(0)', 'view', MyUtility::makeUrl('Orders', 'View', [$row['order_id']]));


                $action->addOtherBtn(Label::getLabel('LBL_DOWNLOAD_INVOICE'), 'javascript:void(0)', 'download', MyUtility::makeUrl('Orders', 'viewInvoice', [$row['order_id']]) . '?t=' . time(), '_blank');


                if ($canEdit && $row['order_payment_status'] == Order::UNPAID && $row['order_status'] != Order::STATUS_CANCELLED) {
                    $action->addCancelBtn(Label::getLabel('LBL_CANCEL_ORDER'), 'cancelOrder(' . $row['order_id'] . ')');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            case 'order_id':
                $td->appendElement('plaintext', [], Order::formatOrderId($row[$key]), true);
                break;
            case 'order_type':
                $td->appendElement('plaintext', [], Order::getTypeArr($row[$key]), true);
                break;
            case 'order_net_amount':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row[$key]), true);
                break;
            case 'order_payment_status':
                $td->appendElement('plaintext', [], Order::getPaymentArr($row[$key]), true);
                break;
            case 'order_status':
                $td->appendElement('plaintext', [], Order::getStatusArr($row[$key]), true);
                break;
            case 'order_addedon':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($orders) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'pageSize' => $post['pagesize'], 'page' => $post['pageno'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
