<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');

if (!$canEdit) {
    unset($columnsArr['action']);
}
foreach ($columnsArr as $val) {
    $e = $th->appendElement('th', [], CommonHelper::renderHtml($val));
}
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    $metaId = FatUtility::int($row['meta_id']);
    $tr->setAttribute("id", $metaId);
    foreach ($columnsArr as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'has_tag_associated':
                $td->appendElement('plaintext', [], is_null($row['meta_id']) ? Label::getLabel('LBL_NO') : Label::getLabel('LBL_YES'));
                break;
            case 'url':
                $td->appendElement('plaintext', [], MetaTag::getOrignialUrlFromComponents($row));
                break;
            case 'action':
                $action = new Action($metaId);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'editMetaTagFormNew(' . $metaId . ',"'.$metaType.'","'.CommonHelper::htmlEntitiesDecode($row[$meta_record_id]) . '")');
                    if ($metaType == MetaTag::META_GROUP_OTHER) {
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord(' . $metaId . ')');
                    }
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($arr_listing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($columnsArr)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
if (isset($pageCount)) {
    $postedData['page'] = $page;
    echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
    $pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount];
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
}
