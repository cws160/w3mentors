<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($requests) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$arrFields = [
    'withdrawal_id' => Label::getLabel('LBL_WITHDRAWAL_Id'),
    'withdrawal_amount' => Label::getLabel('LBL_AMOUNT'),
    'withdrawal_transaction_fee' => Label::getLabel('LBL_TXN_FEE'),
    'withdrawal_comments' => Label::getLabel('LBL_COMMENTS'),
    'withdrawal_request_date' => Label::getLabel('LBL_DATE'),
    'withdrawal_status' => Label::getLabel('LBL_STATUS')
];
$tbl = new HtmlElement('table', ['class' => 'table table--styled table--responsive table--aligned-middle']);
$th = $tbl->appendElement('tr', ['class' => 'title-row']);
foreach ($arrFields as $val) {
    $th->appendElement('th', [], $val);
}
foreach ($requests as $sn => $row) {
    $tr = $tbl->appendElement('tr', ['class' => '']);
    foreach ($arrFields as $key => $val) {
        $div = $tr->appendElement('td')->appendElement('div', ['class' => 'flex-cell']);
        $div->appendElement('div', ['class' => 'flex-cell__label'], $val, true);
        switch ($key) {
            case 'withdrawal_id':
                $div->appendElement('plaintext', [], WithdrawRequest::formatRequestNumber($row[$key]));
                break;
            case 'withdrawal_amount':
            case 'withdrawal_transaction_fee':
                $div->appendElement('div', ['class' => 'flex-cell__content'], MyUtility::formatMoney($row[$key]), true);
                break;
            case 'withdrawal_status':
                $div->appendElement('div', ['class' => 'flex-cell__content'], WithdrawRequest::getStatuses($row[$key]), true);
                break;
            case 'withdrawal_request_date':
                $div->appendElement('div', ['class' => 'flex-cell__content'], MyDate::showDate($row['withdrawal_request_date'], true), true);
                break;
            case 'withdrawal_comments':
                $div->appendElement('div', ['class' => 'flex-cell__content'], nl2br($row[$key] ?? ''), true);
                break;
            default:
                $div->appendElement('div', ['class' => 'flex-cell__content'], $row[$key], true);
                break;
        }
    }
}
echo $tbl->getHtml();
$pagingArr = [
    'pageSize' => $post['pagesize'],
    'page' => $post['pageno'], $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
