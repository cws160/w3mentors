<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$tooltips = [
    'slstat_total_sales' => Label::getLabel('ASR_GROSS_SALES_TOOLTIP'),
    'slstat_discount' => Label::getLabel('ASR_DISCOUNT_TOOLTIP'),
    'slstat_net_sales' => Label::getLabel('ASR_NET_SALES_TOOLTIP'),
    'slstat_credit_discount' => Label::getLabel('ASR_CREDIT_DISCOUNT_TOOLTIP'),
];
$columnArr = [
    'srno' => Label::getLabel('LBL_SRNO'),
    'slstat_date' => Label::getLabel('LBL_DATE'),
    'slstat_total_sales' => Label::getLabel('LBL_GROSS_SALES'),
    'slstat_discount' => Label::getLabel('LBL_DISCOUNT'),
    'slstat_credit_discount' => Label::getLabel('LBL_REWARDS'),
    'slstat_net_sales' => Label::getLabel('LBL_NET_SALES'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$tr = $tbl->appendElement('thead')->appendElement('tr');
foreach ($columnArr as $key => $value) {
    $th = $tr->appendElement('th', ['title' => $tooltips[$key] ?? ''], $value);
    if (!empty($tooltips[$key] ?? '')) {
        $svg = $th->appendElement('svg', ['xmlns' => "http://www.w3.org/2000/svg", 'width' => "12", 'height' => "12", 'viewBox' => "0 0 24 24", 'style' => 'margin-left:3px;margin-bottom: -1px']);
        $svg->appendElement('path', ['d' => "M12 2c5.514 0 10 4.486 10 10s-4.486 10-10 10-10-4.486-10-10 4.486-10 10-10zm0-2c-6.627 0-12 5.373-12 12s5.373 12 12 12 12-5.373 12-12-5.373-12-12-12zm-.001 5.75c.69 0 1.251.56 1.251 1.25s-.561 1.25-1.251 1.25-1.249-.56-1.249-1.25.559-1.25 1.249-1.25zm2.001 12.25h-4v-1c.484-.179 1-.201 1-.735v-4.467c0-.534-.516-.618-1-.797v-1h3v6.265c0 .535.517.558 1 .735v.999z"]);
    }
}
$srno = $page == 1 ? 0 : $postedData['pagesize'] * ($page - 1);
foreach ($records as $row) {
    $srno++;
    $tr = $tbl->appendElement('tr');
    foreach ($columnArr as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'srno':
                $td->appendElement('plaintext', [], $srno);
                break;
            case 'slstat_date':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key]));
                break;
            case 'slstat_total_sales':
            case 'slstat_net_sales':
            case 'slstat_discount':
            case 'slstat_credit_discount':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row[$key]), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key]);
                break;
        }
    }
}
if (empty($records)) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($columnArr)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'recordCount' => $recordCount, 'page' => $page];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
