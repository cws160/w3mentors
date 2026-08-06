<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$flds = array(
    'srno' => Label::getLabel('LBL_SRNO'),
    'user_full_name' => Label::getLabel('LBL_USER_NAME'),
    'user_email' => Label::getLabel('LBL_USER_EMAIL'),
    'user_type' => Label::getLabel('LBL_USER_TYPE'),
    'rescheduledCount' => Label::getLabel('LBL_RESCHEDULED'),
    'cancelledCount' => Label::getLabel('LBL_CANCELLED')
);
$tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table table--hovered'));
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($flds as $val) {
    $e = $th->appendElement('th', array(), $val);
}
$srno = $page == 1 ? 0 : $post['pagesize'] * ($page - 1);
foreach ($logs as $sn => $row) {
    $srno++;
    $tr = $tbl->appendElement('tr');
    foreach ($flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'srno':
                $td->appendElement('plaintext', [], $srno);
                break;
            case 'user_full_name':
                $td->appendElement('plaintext', array(), $row['user_first_name'] . ' ' . $row['user_last_name'], true);
                break;
            case 'user_type':
                $str = Label::getLabel('LBL_LEARNER');
                if ($row['user_is_teacher']) {
                    $str .= " | " . Label::getLabel('LBL_TEACHER');
                }
                $td->appendElement('plaintext', array(), $str, true);
                break;
            case 'rescheduledCount':
                $label = ' <a class="link-dotted link-text" href = "' . MyUtility::makeUrl('LessonStats', 'viewLogs', [$row['user_id'], SessionLog::LESSON_RESCHEDULED_LOG])
                    .'" >' . Label::getLabel('LBL_SESSIONS') . '</a>';
                $td->appendElement('plaintext', array(), intval($row[$key]) > 0 ? ($row[$key] . $label) : $row[$key], true);
                break;
            case 'cancelledCount':
                $label = ' <a class="link-dotted link-text" href = "' . MyUtility::makeUrl('LessonStats', 'viewLogs', [$row['user_id'], SessionLog::LESSON_CANCELLED_LOG])
                    . '" >' . Label::getLabel('LBL_SESSIONS') . '</a>';
                $td->appendElement('plaintext', array(), intval($row[$key]) > 0 ? ($row[$key] . $label) : $row[$key], true);
                break;
            default:
                $td->appendElement('plaintext', array(), $row[$key], true);
                break;
        }
    }
}
if (count($logs) == 0) {
    $tbl->appendElement('tr')->appendElement('td', array('colspan' => count($flds)), Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, array('name' => 'srchFormPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $post['pageno'], 'recordCount' => $recordCount);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
