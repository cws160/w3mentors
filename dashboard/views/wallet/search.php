<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($txns) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$arrFields = [
    'usrtxn_id' => Label::getLabel('LBL_Txn_ID'),
    'usrtxn_type' => Label::getLabel('LBL_Type'),
    'usrtxn_amount' => Label::getLabel('LBL_amount'),
    'usrtxn_datetime' => Label::getLabel('LBL_Date'),
    'usrtxn_comment' => Label::getLabel('LBL_Comments')
];
$tbl = new HtmlElement('table', ['class' => 'table table--styled table--responsive table--aligned-middle']);
$th = $tbl->appendElement('tr', ['class' => 'title-row']);
foreach ($arrFields as $val) {
    $th->appendElement('th', [], $val);
}
foreach ($txns as $sn => $row) {
    $tr = $tbl->appendElement('tr', ['class' => '']);
    foreach ($arrFields as $key => $val) {
        $div = $tr->appendElement('td')->appendElement('div', ['class' => 'flex-cell']);
        $div->appendElement('div', ['class' => 'flex-cell__label'], $val, true);
        switch ($key) {
            case 'usrtxn_id':
                $div->appendElement('plaintext', [], Transaction::formatTxnId($row[$key]));
                break;
            case 'usrtxn_datetime':
                $div->appendElement('plaintext', [], MyDate::showDate($row['usrtxn_datetime'], true));
                break;
            case 'usrtxn_type':
                $div->appendElement('div', ['class' => 'flex-cell__content'], Transaction::getTypes($row[$key]), true);
                break;
            case 'usrtxn_amount':
                $div->appendElement('div', ['class' => 'flex-cell__content'], MyUtility::formatMoney($row[$key]), true);
                break;
            case 'usrtxn_comment':
                $div->appendElement('div', array('class' => 'flex-cell__content'), strip_tags($row[$key]), true);
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
