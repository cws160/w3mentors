<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'course_id' => Label::getLabel('LBL_ID'),
    'course_title' => Label::getLabel('LBL_TITLE'),
    'teacher_name' => Label::getLabel('LBL_TEACHER'),
    'cate_name' => Label::getLabel('LBL_CATEGORY'),
    'subcate_name' => Label::getLabel('LBL_SUBCATEGORY'),
    'coapre_updated' => Label::getLabel('LBL_PUBLISHED_ON'),
    'course_active' => Label::getLabel('LBL_STATUS'),
    'action' => Label::getLabel('LBL_ACTION'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$paymentMethod = OrderPayment::getMethods();
foreach ($arrListing as $row) {
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'action':
                $action = new Action($row['course_id']);
                $action->addViewBtn(Label::getLabel('LBL_VIEW'),  'view(' . $row['course_id'] . ')');
                if ($canEdit === true) {
                    $action->addOtherBtn(Label::getLabel('LBL_PREVIEW'), 'userLogin("' . $row['course_teacher_id'] . '", "' . $row['course_id'] . '","preview")', 'preview', 'javascript:void(0)');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            case 'teacher_name':
                $td->appendElement('plaintext', [], $row['teacher_first_name'] . ' ' . $row['teacher_last_name'], true);
                break;
            case 'coapre_updated':
                $fmtDate = MyDate::formatDate($row[$key]);
                $td->appendElement('plaintext', [], MyDate::showDate($fmtDate, true), true);
                break;
            case 'course_active':
                $active = "active";
                if ($row['course_active'] == AppConstant::NO) {
                    $active = 'inactive';
                }
                $statusClass = "";
                if ($canEdit === false) {
                    $statusClass = "disabled";
                }
                $str = '<label class="statustab ' . $active . '" ' . (($canEdit) ? 'onclick="updateStatus(\'' . $row['course_id'] . '\', \'' . $row['course_active'] . '\')"' : "") . '>
				  <span data-off="' . Label::getLabel('LBL_Active') . '" data-on="' . Label::getLabel('LBL_Inactive') . '" class="switch-labels "></span>
				  <span class="switch-handles ' . $statusClass . '"></span>
				</label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key] ?? Label::getLabel('LBL_NA'), true);
                break;
        }
    }
}
if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'pageSize' => $post['pagesize'], 'page' => $post['page'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
