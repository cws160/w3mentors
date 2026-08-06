<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'ordcrs_id' => Label::getLabel('LBL_ID'),
    'order_id' => Label::getLabel('LBL_ORDER_ID'),
    'learner_name' => Label::getLabel('LBL_LEARNER'),
    'teacher_name' => Label::getLabel('LBL_TEACHER'),
    'course_title' => Label::getLabel('LBL_TITLE'),
    'ordcrs_net_amount' => Label::getLabel('LBL_NET_TOTAL'),
    'order_payment_status' => Label::getLabel('LBL_PAYMENT'),
    'order_created' => Label::getLabel('LBL_DATETIME'),
    'ordcrs_status' => Label::getLabel('LBL_STATUS'),
    'action' => Label::getLabel('LBL_ACTION'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
foreach ($arrListing as $row) {
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'action':
                $action = new Action($row['order_id']);
                $action->addOtherBtn(Label::getLabel('LBL_VIEW'), 'javascript:void(0)', 'view', MyUtility::makeUrl('Orders', 'view', [$row['order_id']]));
                $action->addOtherBtn(Label::getLabel('LBL_DOWNLOAD_INVOICE'), 'javascript:void(0)', 'download', MyUtility::makeUrl('Orders', 'viewInvoice', [$row['order_id']]).'?t='.time(), '_blank');
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            case 'order_id':
                $td->appendElement('plaintext', [], Order::formatOrderId(FatUtility::int($row[$key])), true);
                break;
            case 'learner_name':
                $td->appendElement('plaintext', [], $row['learner_first_name'] . ' ' . $row['learner_last_name'], true);
                break;
            case 'teacher_name':
                $td->appendElement('plaintext', [], $row['teacher_first_name'] . ' ' . $row['teacher_last_name'], true);
                break;
            case 'order_type':
                $td->appendElement('plaintext', [], Order::getTypeArr($row[$key]), true);
                break;
            case 'ordcrs_net_amount':
                $netAmount = $row['ordcrs_amount'] - $row['ordcrs_discount'] - $row['order_reward_value'];
                $td->appendElement('plaintext', [], MyUtility::formatMoney($netAmount), true);
                break;
            case 'order_payment_status':
                $td->appendElement('plaintext', [], Order::getPaymentArr($row[$key]), true);
                break;
            case 'order_addedon':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true), true);
                break;
            case 'ordcrs_status':
                $td->appendElement('plaintext', [], OrderCourse::getStatuses($row[$key]), true);
                break;
            case 'order_created':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmCourseSearchPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'pageSize' => $post['pagesize'], 'page' => $post['page'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
