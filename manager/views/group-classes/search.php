<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'grpcls_title' => Label::getLabel('LBL_Class_Title'),
    'grpcls_type' => Label::getLabel('LBL_Type'),
    'grpcls_offline' => Label::getLabel('LBL_SERVICE_TYPE'),
    'teacher_name' => Label::getLabel('LBL_Teacher'),
    // 'grpcls_total_seats' => Label::getLabel('LBL_MAX_LEARNERS'),
    'grpcls_entry_fee' => Label::getLabel('LBL_Entry_Fee'),
    'grpcls_start_datetime' => Label::getLabel('LBL_START_TIME'),
    'grpcls_end_datetime' => Label::getLabel('LBL_END_TIME'),
    // 'grpcls_added_on' => Label::getLabel('LBL_CREATED'),
    'grpcls_status' => Label::getLabel('LBL_STATUS'),
    'action' => Label::getLabel('LBL_Action'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($classes as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'grpcls_offline':
                $clsType = ($row['grpcls_offline'] == AppConstant::YES)? Label::getLabel('LBL_OFFLINE'): Label::getLabel('LBL_ONLINE');
                $td->appendElement('plaintext', [], $clsType);
                break;
            case 'teacher_name':
                $td->appendElement('plaintext', [], $row['user_first_name'] . ' ' . $row['user_last_name'], true);
                break;
            case 'grpcls_type':
                $types = GroupClass::getClassTypes();
                $type = $types[$row[$key]];
                if ($row['grpcls_parent'] > 0) {
                    $type = $types[GroupClass::TYPE_PACKAGE];
                }
                $td->appendElement('plaintext', [], $type);
                break;
            case 'grpcls_entry_fee':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row[$key]), true);
                break;
            case 'grpcls_added_on':
            case 'grpcls_start_datetime':
            case 'grpcls_end_datetime':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                break;
            case 'grpcls_status':
                $td->appendElement('plaintext', [], GroupClass::getStatuses($row[$key]), true);
                break;
            case 'action':
                $action = new Action($row['grpcls_id']);
                $action->addOtherBtn(Label::getLabel('LBL_View_Learners'), 'javascript:void(0)', 'view', MyUtility::makeUrl('GroupClasses', 'learners', [$row['grpcls_id']]) );
                if ($row['grpcls_type'] == GroupClass::TYPE_PACKAGE) {
                    $action->addOtherBtn(Label::getLabel('LBL_CLasses'),'javascript:void(0)','classes',MyUtility::makeUrl('PackageClasses') . '?grpcls_parent=' . $row['grpcls_id']);
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], CommonHelper::renderHtml($row[$key]));
                break;
        }
    }
}
if (count($classes) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'pageSize' => $pageSize, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
