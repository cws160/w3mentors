<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$fields = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'learner_name' => Label::getLabel('LBL_REVIEW_BY'),
    'teacher_name' => Label::getLabel('LBL_REVIEW_TO'),
    'ratrev_title' => Label::getLabel('LBL_REVIEW_DETAIL'),
    'ratrev_status' => Label::getLabel('LBL_STATUS'),
    'ratrev_created' => Label::getLabel('LBL_POSTED'),
];
if ($canEdit) {
    $fields['action'] = Label::getLabel('LBL_ACTION');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($fields as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo = $postedData['pageno'] == 1 ? 0 : $postedData['pagesize'] * ($postedData['pageno'] - 1);
foreach ($reviews as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    foreach ($fields as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'ratrev_status':
                $td->appendElement('plaintext', [], RatingReview::getStatues($row[$key]), true);
                break;
            case 'action':
                $ul = $td->appendElement("ul", ["class" => "actions"]);
                $li = $ul->appendElement("li");
                $li->appendElement('a', ['href' => 'javascript:void(0)', 'title' =>  Label::getLabel('LBL_EDIT'), 'onclick' => 'form(' . $row['ratrev_id'] . ');'], '<svg class="svg" width="18" height="18"><use xlink:href="' . CONF_WEBROOT_BACKEND . 'images/retina/sprite-actions.svg#edit"></use></svg>', true);
                break;
            case 'ratrev_created':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($reviews) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($fields)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $postedData['pageno'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
