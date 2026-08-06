<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [
    'srno' => Label::getLabel('LBL_SRNO'),
    'teacher_name' => Label::getLabel('LBL_TEACHER'),
    'testat_lessons' => Label::getLabel('LBL_LESSONS'),
    'testat_classes' => Label::getLabel('LBL_CLASSES'),
    'testat_courses' => Label::getLabel('LBL_COURSES'),
    'testat_students' => Label::getLabel('LBL_STUDENTS'),
    'testat_reviewes' => Label::getLabel('LBL_REVIEWES'),
    'testat_ratings' => Label::getLabel('LBL_RATINGS')
];
if (!Course::isEnabled()) {
    unset($arrFlds['testat_courses']);
}
if (!GroupClass::isEnabled()) {
    unset($arrFlds['testat_classes']);
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srno = $page == 1 ? 0 : $postedData['pagesize'] * ($page - 1);
foreach ($records as $sn => $row) {
    $srno++;
    $tr = $tbl->appendElement('tr');
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'srno':
                $td->appendElement('plaintext', [], $srno);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($records) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$this->includeTemplate('_partial/pagination.php', ['pageCount' => $pageCount, 'recordCount' => $recordCount, 'page' => $page], false);
