<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$flds = array(
    'listserial' => Label::getLabel('LBL_SR'),
    'taecherName' => Label::getLabel('LBL_TEACHER_NAME'),
    'learnerName' => Label::getLabel('LBL_LEARNER_NAME'),
    'order_id' => Label::getLabel('LBL_ORDER_DETAILS'),
    'prevTimings' => Label::getLabel('LBL_PREV_TIMINGS'),
    'sesslog_prev_status' => Label::getLabel('LBL_PREV_STATUS'),
    'sesslog_changed_status' => Label::getLabel('LBL_ACTION_PERFORMED'),
    'sesslog_created' => Label::getLabel('LBL_ADDED_ON'),
    'sesslog_comment' => Label::getLabel('LBL_REASON')
);
if (SessionLog::LESSON_CANCELLED_LOG == $post['reportType']) {
    unset($flds['prevTimings']);
}
$tbl = new HtmlElement('table', array('width' => '100%', 'class' => 'table table--hovered'));
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($flds as $val) {
    $e = $th->appendElement('th', array(), $val);
}
$srNo = $page == 1 ? 0 : $pageSize * ($page - 1);
$statusArr = Lesson::getStatuses();
foreach ($logs as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    foreach ($flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->setAttribute('width', '2%');
                $td->appendElement('plaintext', array(), $srNo);
                break;
            case 'taecherName':
                $td->setAttribute('width', '15%');
                $td->appendElement('span', [], $row['teacher_first_name'] . ' ' . $row['teacher_last_name'], true);
                break;
            case 'learnerName':
                $td->appendElement('span', [], $row['learner_first_name'] . ' ' . $row['learner_last_name'], true);
                break;
            case 'order_id':
                $td->setAttribute('width', '15%');
                $td->appendElement('plaintext', array(), Label::getLabel('LBL_O-ID') . ': ' . Order::formatOrderId(FatUtility::int($row['order_id'])) . '<br> ' . Label::getLabel('LBL_LESSON_ID') . ': ' . $row['ordles_id'], true);
                break;
            case 'prevTimings':
                $td->setAttribute('width', '20%');
                $timings = Label::getLabel('LBL_NA');
                if (!empty($row['sesslog_prev_starttime']) && !empty($row['sesslog_prev_endtime'])) {
                    $timings = Label::getLabel('LBL_ST') . ': ' . MyDate::showDate($row['sesslog_prev_starttime'], true) . '<br> ' . Label::getLabel('LBL_ET') . ': ' . MyDate::showDate($row['sesslog_prev_endtime'], true);
                }
                $td->appendElement('plaintext', array(), $timings, true);
                break;
            case 'sesslog_prev_status':
                $td->appendElement('plaintext', array(), $statusArr[$row['sesslog_prev_status']], true);
                break;
            case 'sesslog_changed_status':
                $td->appendElement('plaintext', array(), $statusArr[$row['sesslog_changed_status']], true);
                break;
            case 'sesslog_created':
                $td->appendElement('plaintext', array(), MyDate::showDate($row['sesslog_created'], true), true);
                break;
            case 'sesslog_comment':
                $td->appendElement('span', array(), nl2br($row['sesslog_comment']), true);
                break;
            default:
                $td->appendElement('plaintext', array(), $row[$key]);
                break;
        }
    }
}
?>
    <div class="card-head">
        <div class="card-head-label">
            <h3 class="card-head-title"><?php echo $logTypeLabel . ' - <span class="label--info">' . $user['user_first_name'] . ' ' . $user['user_last_name'] . '</span>'; ?></h3>
        </div>
    </div>
<?php
if (empty($logs)) {
    $tbl->appendElement('tr')->appendElement('td', array('colspan' => count($flds)), Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, array('name' => 'srchFormPaging'));
$pagingArr = array('pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>