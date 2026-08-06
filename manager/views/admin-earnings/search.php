<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$tooltips = [];
$columnArr = [
    'srno' => Label::getLabel('LBL_SRNO'),
    'admtxn_amount' => Label::getLabel('LBL_EARNING'),
    'admtxn_record_type' => Label::getLabel('LBL_EARNING_TYPE'),
    'admtxn_datetime' => Label::getLabel('LBL_DATETIME'),
    'admtxn_comment' => Label::getLabel('LBL_DESCRIPTION'),
    'action' => Label::getLabel('LBL_ACTION'),
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
$srno = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($records as $row) {
    $srno++;
    $tr = $tbl->appendElement('tr');
    foreach ($columnArr as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'srno':
                $td->appendElement('plaintext', [], $srno);
                break;
            case 'admtxn_amount':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row[$key]), true);
                break;
            case 'admtxn_record_type':
                $td->appendElement('plaintext', [], AdminTransaction::getTypes($row[$key]), true);
                break;
            case 'admtxn_datetime':
                $td->appendElement('plaintext', [], MyDate::showDate($row['admtxn_datetime'], true));
                break;
            case 'action':
                $trgt = '';
                $href = 'javascript:void(0)';
                $onCLick = '';
                $statusClass = "disabled";
                if ($row['admtxn_record_type'] == AppConstant::LESSON && $canViewLessons == true) {
                    $onCLick = 'viewLesson(' . $row['admtxn_record_id'] . ')';
                    $statusClass = '';
                } else if ($row['admtxn_record_type'] == AppConstant::COURSE && $canViewCourses == true && $canViewOrders == true) {
                    $onCLick = 'javascript:void(0)';
                    $href = MyUtility::makeUrl('Orders', 'view', [$row['admtxn_record_id']]);
                    $trgt = '_blank';
                    $statusClass = '';
                }else if ($row['admtxn_record_type'] == AppConstant::SUBPLAN && $canViewOrders == true) {
                    $onCLick = 'javascript:void(0)';
                    $href = MyUtility::makeUrl('OrderSubscriptionPlans', '', []).'?ordsplan_id='.$row['admtxn_record_id'];
                    $trgt = '_blank';
                    $statusClass = '';
                }
                 else if ($row['admtxn_record_type'] == AppConstant::GCLASS && $canViewClasses == true) {
                    $onCLick = 'viewClass(' . $row['admtxn_record_id'] . ')';
                    $statusClass = '';
                }
               
                $ul = $td->appendElement("ul", ["class" => "actions"]);
                $li = $ul->appendElement("li");
                $li->appendElement('a', ['href' => $href, 'target' => $trgt, 'class' => 'button small green '. $statusClass, 'title' => Label::getLabel('LBL_View'), "onclick" => $onCLick], '<svg class="svg" width="18" height="18"><use xlink:href="' . CONF_WEBROOT_BACKEND . 'images/retina/sprite-actions.svg#view"></use></svg>', true);
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
$pagingArr = [
    'pageSize' => $post['pagesize'],
    'page' => $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
