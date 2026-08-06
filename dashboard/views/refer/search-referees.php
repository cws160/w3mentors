<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($records) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$arrFields = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'username' => Label::getLabel('LBL_USER'),
    'user_registered_as' => Label::getLabel('LBL_REGISTERED_AS'),
    'user_created' => Label::getLabel('LBL_JOINING_DATE'),
    
];
$userTypes = User::getUserTypes();
$tbl = new HtmlElement('table', ['class' => 'table table--styled table--responsive table--aligned-middle']);
$th = $tbl->appendElement('tr', ['class' => 'title-row']);
foreach ($arrFields as $val) {
    $th->appendElement('th', [], $val);
}
$sr_no = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($records as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr', ['class' => '']);
    foreach ($arrFields as $key => $val) {
        $div = $tr->appendElement('td')->appendElement('div', ['class' => 'flex-cell']);
        $div->appendElement('div', ['class' => 'flex-cell__label'], $val, true);
        switch ($key) {
            case 'listserial':
                $div->appendElement('plaintext', [], $sr_no);
                break;
            case 'username':
                $div->appendElement('plaintext', [], $row['user_first_name']. " ".  $row['user_last_name'] );
                break;
            case 'user_registered_as':
                $div->appendElement('plaintext', [], $userTypes[$row[$key]]);
                break;    
            case 'user_created':
                $div->appendElement('plaintext', [], MyDate::showDate($row['user_created'], true));
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
    'page' => $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
