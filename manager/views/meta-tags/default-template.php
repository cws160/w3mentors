<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_Sr._No'),
    'meta_identifier' => Label::getLabel('LBL_Identifier'),
    'meta_title' => Label::getLabel('LBL_Title'),
    'action' => Label::getLabel('LBL_Action'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = 0;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    $metaId = FatUtility::int($row['meta_id']);
    $recordId = FatUtility::int($row['meta_record_id']);
    $tr->setAttribute("id", $metaId);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'action':
                $ul = $td->appendElement("ul", ["class" => "actions actions--centered"]);
                if ($canEdit) {
                    $li = $ul->appendElement("li", ['class' => 'droplink']);
                    $li->appendElement('a', ['href' => 'javascript:void(0)', 'class' => 'button small green', 'title' => Label::getLabel('LBL_Edit')], '<i class="ion-android-more-horizontal icon"></i>', true);
                    $innerDiv = $li->appendElement('div', ['class' => 'dropwrap']);
                    $innerUl = $innerDiv->appendElement('ul', ['class' => 'linksvertical']);
                    $innerLiEdit = $innerUl->appendElement('li');
                    $innerLiEdit->appendElement(
                        'a',
                        [
                            'href' => 'javascript:void(0)', 'class' => 'button small green',
                            'title' => Label::getLabel('LBL_Edit'), "onclick" => "editMetaTagFormNew($metaId,'$metaType',$recordId)"
                        ],
                        Label::getLabel('LBL_Edit'),
                        true
                    );
                    if ($metaType == MetaTag::META_GROUP_ADVANCED) {
                        $innerLiDelete = $innerUl->appendElement('li');
                        $innerLiDelete->appendElement(
                            'a',
                            [
                                'href' => 'javascript:void(0)', 'class' => 'button small green',
                                'title' => Label::getLabel('LBL_Delete'), "onclick" => "deleteRecord($metaId)"
                            ],
                            Label::getLabel('LBL_Delete'),
                            true
                        );
                    }
                }
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($arr_listing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
if (isset($pageCount)) {
    $postedData['page'] = $page;
    echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'frmMetaTagSearchPaging']);
    $pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount];
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
}
