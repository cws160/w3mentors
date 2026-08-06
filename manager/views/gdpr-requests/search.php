<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'gdpreq_id' => Label::getLabel('LBL_REQ_ID'),
    'gdpreq_user_name' => Label::getLabel('LBL_USER_NAME'),
    'user_email' => Label::getLabel('LBL_USER_EMAIL'),
    'gdpreq_reason' => Label::getLabel('LBL_REASON'),
    'gdpreq_added_on' => Label::getLabel('LBL_REQUESTED_ON'),
    'gdpreq_updated_on' => Label::getLabel('LBL_UPDATED_ON'),
    'gdpreq_status' => Label::getLabel('LBL_STATUS'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $th->appendElement('th', [], $val);
}
$status = GdprRequest::getStatusArr();
foreach ($gdprRequests as $sn => $row) {
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'gdpreq_reason':
                $td->appendElement('plaintext', [], CommonHelper::renderHtml(CommonHelper::truncateCharacters($row['gdpreq_reason'], 50)));
                break;
            case 'gdpreq_user_name':
                $td->appendElement('plaintext', [], CommonHelper::renderHtml(implode(" ", [$row['user_first_name'], $row['user_last_name']])));
                break;
            case 'gdpreq_added_on':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                break;
            case 'gdpreq_updated_on':
                $text = MyDate::showDate($row[$key], true);
                if ($row['gdpreq_status'] == GdprRequest::STATUS_PENDING) {
                    $text = Label::getLabel('LBL_NA');
                }
                $td->appendElement('plaintext', [], $text, true);
                break;
            case 'gdpreq_status':
                $td->appendElement('plaintext', [], $status[$row[$key]], true);
                break;
            case 'action':
                if ($canEdit) {
                    $class = $event = 'view(' . $row['gdpreq_id'] . ')';
                    if ($row['gdpreq_status'] != GdprRequest::STATUS_PENDING) {
                        $class = "disabled";
                        $event = "";
                    }
                    $action = new Action($row['gdpreq_id']);
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'), $event, ['class' => $class]);
                    $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                }
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key]);
                break;
        }
    }
}
if (count($gdprRequests) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'pageSize' => $pageSize, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
