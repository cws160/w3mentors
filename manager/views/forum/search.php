<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds['listserial'] = Label::getLabel('LBL_SR_NO');
$arrFlds['fque_title'] = Label::getLabel('LBL_Title');
$arrFlds['fque_user'] = Label::getLabel('LBL_User');
$arrFlds['fque_lang_id'] = Label::getLabel('LBL_FORUM_LANGUAGE');
$arrFlds['fque_status'] = Label::getLabel('LBL_Status');
$arrFlds['fque_added_on'] = Label::getLabel('LBL_Added_On');
if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered table-dragable', 'id' => 'teachingLangages']);
$th = $tbl->appendElement('thead')->appendElement('tr');
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['fque_id']);
    if (1 == $row['fque_deleted']) {
        $tr->setAttribute("class", 'disabled');
    }
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'fque_status':
                $options = ForumQuestion::getQuestionStatusArray();
                $td->appendElement('plaintext', [], $options[$row['fque_status']]);
                break;
            case 'fque_title':
                $td->appendElement('div', [], $row['fque_title'], true);
                break;
            case 'fque_added_on':
                $td->appendElement('div', [], MyDate::showDate($row['fque_added_on'], true), true);
                break;
            case 'fque_user':
                $td->appendElement('div', [], $row['user_name'], true);
                break;
            case 'fque_lang_id':
                $td->appendElement('div', [], $languages[$row['fque_lang_id']] ?? '', true);
                break;
            case 'action':
                if ($canEdit) {
                    $action = new Action($row['fque_id']);
                    $action->addViewBtn(Label::getLabel('LBL_View'), 'view(' . $row['fque_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord(' . $row['fque_id'] . ')');
                    if (0 < $row['fstat_comments'] && 1 == $row['fque_comments_allowed']) {
                        $action->addOtherBtn(Label::getLabel('LBL_View_Comments'), 'javascript:void(0)', 'comment', MyUtility::makeUrl('Forum', 'Comments', [$row['fque_id']]));
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
if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($post, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => ceil($recordCount / $post['pagesize']), 'pageSize' => $post['pagesize'], 'page' => $post['pageno'], 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
