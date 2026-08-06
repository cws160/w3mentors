
<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'seourl_original' => Label::getLabel('LBL_Original'),
    'seourl_custom' => Label::getLabel('LBL_Custom'),
    'seourl_httpcode' => Label::getLabel('LBL_httpcode'),
    'seourl_lang_id' => Label::getLabel('LBL_Language'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($records as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'seourl_lang_id':
                $td->appendElement('plaintext', [], $langCodes[$row[$key]] ?? Label::getLabel('LBL_NA'));
                break;
            case 'action':
                $action = new Action($row['seourl_id']);
                $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'urlForm(' . $row['seourl_id'] . ')');
                $action->addRemoveBtn(Label::getLabel('LBL_Delete'),  'deleteRecord(' . $row['seourl_id'] . ')');
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($records) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();

echo FatUtility::createHiddenFormFromData($post, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'recordCount' => $recordCount, 'page' => $post['pageno']];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
