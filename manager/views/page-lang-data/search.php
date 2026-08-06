<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'plang_key' => Label::getLabel('LBL_PAGE_KEY'),
    'plang_title' => Label::getLabel('LBL_TITLE'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table-dashed']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['plang_id']
                ];
                if ($canEdit) {
                    $data['editButton'] = [
                        'href' => 'javascript:void(0)',
                        'onclick' => "langForm(" . $row['plang_id'] . "," . $row['plang_lang_id'] . ")",
                        'title' => Label::getLabel('LBL_Edit', $siteLangId),
                        'label' => '<i class="icn">
                                            <svg class="svg">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/sprite-actions.svg#edit">
                                                </use>
                                            </svg>
                                        </i>' . Label::getLabel('LBL_Edit', $siteLangId),
                    ];


                    $data['deleteButton'] = false;
                }
                $actionItems = $this->includeTemplate('_partial/listing-action-buttons.php', $data, false, true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($arr_listing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmPagesSearchPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'pageSize' => $post['pagesize'], 'page' => $post['pageno'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);