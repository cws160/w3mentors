<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'post_identifier' => Label::getLabel('LBL_POST_IDENTIFIER'),
    'post_title' => Label::getLabel('LBL_Post_Title'),
    'categories' => Label::getLabel('LBL_Category'),
    'post_added_on' => Label::getLabel('LBL_Added_Date'),
    'post_published_on' => Label::getLabel('LBL_Published_Date'),
    'post_published' => Label::getLabel('LBL_Post_Status'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'post']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    if ($row['post_published'] == 1) {
        $tr->setAttribute("id", $row['post_id']);
    }
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'post_published_on':
                $td->appendElement('plaintext', [], MyDate::showDate($row['post_published_on'], true));
                break;
            case 'post_added_on':
                $td->appendElement('plaintext', [], MyDate::showDate($row['post_added_on'], true));
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'post_title':
            case 'post_identifier':
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'post_published':
                $td->appendElement('plaintext', [], BlogPost::getStatuses($row[$key]), true);
                break;
            case 'child_count':
                if ($row[$key] == 0) {
                    $td->appendElement('plaintext', [], $row[$key], true);
                } else {
                    $td->appendElement('a', ['href' => MyUtility::makeUrl('BlogPostCategories', 'index', [$row['post_id']]), 'title' => Label::getLabel('LBL_View_Categories')], $row[$key]);
                }
                break;
            case 'action':
                $action = new Action($row['post_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'addBlogPostForm(' . $row['post_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord(' . $row['post_id'] . ')');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            case 'categories':
                $td->appendElement('plaintext', [], implode(", ", explode(",", $row['categories'] ?? '')), true);
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
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
