<?php defined('SYSTEM_INIT') or die('Invalid Usage.');

$arrFlds = [
    'listserial' => Label::getLabel('LBL_srNo'),
    'user_id' => Label::getLabel('LBL_USER'),
    'comhis_lessons' => Label::getLabel('LBL_LESSON_FEES_[%]'),
    'comhis_classes' => Label::getLabel('LBL_CLASS_FEES_[%]'),
    'comhis_courses' => Label::getLabel('LBL_COURSE_FEES_[%]'),
    'comhis_created' => Label::getLabel('LBL_ADDED_ON')
];
if (!Course::isEnabled()) {
    unset($arrFlds['comhis_courses']);
}
if (!GroupClass::isEnabled()) {
    unset($arrFlds['comhis_classes']);
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo =  $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'user_id':
                $str = "<span class='label label-success'>" . Label::getLabel('LBL_GLOBAL_COMMISSION') . "</span>";
                if (!empty($row['user_id'])) {
                    $str = $row['user_first_name'] . ' ' . $row['user_last_name'];
                }
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'comhis_created':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key]);
                break;
        }
    }
}
if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORD_FOUND'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'frmSearchPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>