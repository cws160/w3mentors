<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [
    'listserial' => Label::getLabel('LBL_srNo'),
    'tereq_reference' => Label::getLabel('LBL_REFERENCE_NUMBER'),
    'user_full_name' => Label::getLabel('LBL_NAME'),
    'user_email' => Label::getLabel('LBL_EMAIL'),
    'tereq_comments' => Label::getLabel('LBL_COMMENTS'),
    'tereq_date' => Label::getLabel('LBL_REQUESTED_ON'),
    'status' => Label::getLabel('LBL_STATUS'),
    'action' => Label::getLabel('LBL_ACTION'),
];
if(!$canEdit) {
    unset($arrFlds['action']);
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    foreach ($arrFlds as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'user_full_name':
                $td->appendElement('plaintext', [], CommonHelper::renderHtml(implode(" ", [$row['tereq_first_name'], $row['tereq_last_name']])));
                break;
            case 'tereq_comments':
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'status':
                $td->appendElement('plaintext', [], TeacherRequest::getStatuses($row['tereq_status']), true);
                break;
            case 'action':
                if ($canEdit) {
                    $action = new Action($row['tereq_id']);
                    $action->addViewBtn(Label::getLabel('LBL_View'),  'view(' . $row['tereq_id'] . ')');
                    $action->addOtherBtn(Label::getLabel('LBL_QUALIFICATIONS'), 'javascript:void(0)', 'qualification', MyUtility::makeUrl('TeacherRequests', 'qualifications', [$row['tereq_user_id']]));
                    if (empty($row['user_deleted']) && $row['tereq_status'] == TeacherRequest::STATUS_PENDING) {
                        $action->addOtherBtn(Label::getLabel('LBL_CHANGE_STATUS'),  'changeStatusForm(' . $row['tereq_id'] . ')', 'edit');
                    }
                    $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                }
                /* ] */
                break;
            case 'tereq_date':
                $td->appendElement('plaintext', [], MyDate::showDate($row['tereq_date'], true));
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
