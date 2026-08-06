<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SrNo'),
    'user_full_name' => Label::getLabel('LBL_Full_Name'),
    'user_email' => Label::getLabel('LBL_Email'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($learners as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'user_full_name':
                $td->appendElement('plaintext', [], $row['user_first_name'] . ' ' . $row['user_last_name'], true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key]);
                break;
        }
    }
}
?>

        <?php
        if (count($learners) == 0) {
            $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
        }
        echo $tbl->getHtml();
        $postedData['page'] = $page;
        echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
        $pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'pageSize' => $pageSize, 'recordCount' => $recordCount];
        $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
        ?>