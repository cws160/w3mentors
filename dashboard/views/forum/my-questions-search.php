<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
if (0 == count($arrListing)) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$arrFields = [
    'sno' => Label::getLabel('LBL_SrNo'),
    'fque_title' => Label::getLabel('LBL_Title'),
    'fque_status' => Label::getLabel('LBL_Status'),
    'fque_added_on' => Label::getLabel('LBL_Added'),
    'action' => Label::getLabel('LBL_Actions')
];
$tbl = new HtmlElement('table', ['class' => 'table table--styled table--responsive table--aligned-middle']);
$th = $tbl->appendElement('tr', ['class' => 'title-row']);
foreach ($arrFields as $val) {
    $th->appendElement('th', [], $val);
}
$srNo = $post['pageno'] == 1 ? 0 : $post['pagesize'] * ($post['pageno'] - 1);
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr', ['id' => 'myqueid_' . $row['fque_id']]);
    foreach ($arrFields as $key => $val) {
        $div = $tr->appendElement('td')->appendElement('div', ['class' => 'flex-cell']);
        $div->appendElement('div', ['class' => 'flex-cell__label'], $val, true);
        switch ($key) {
            case 'sno':
                $div->appendElement('plaintext', [], $srNo);
                break;
            case 'fque_title':
                $dv = $div->appendElement('div', ['class' => 'flex-cell__content d-sm-block']);
                $dv->appendElement('div', [], $row[$key], true);
                if (0 < $row['fstat_comments']) {
                    $replVars = ['{count}' => $row['fstat_comments']];
                    $lbl = CommonHelper::replaceStringData(Label::getLabel('LBL_Comments_{count}'), $replVars);
                    $dv->appendElement('div', [], '<strong>' . $lbl . '</strong>', true);
                }
                break;
            case 'fque_added_on':
                $div->appendElement('div', ['class' => 'flex-cell__content'], MyDate::showDate($row[$key], true), true);
                break;
            case 'fque_status':
                $status = $statusArr[$row[$key]] ?? Label::getLabel('LBL_NA');
                $div->appendElement('div', ['class' => 'flex-cell__content'], $status, true);
                break;
            case 'action':
                $dv = $div->appendElement('div', ['class' => 'flex-cell__content'])->appendElement('div', ['class' => 'actions-group']);
                if (ForumQuestion::FORUM_QUE_RESOLVED != $row['fque_status']) {
                    $dv->appendElement(
                        'a',
                        [
                            'href' => MyUtility::generateUrl('Forum', 'form', [$row['fque_id']]),
                            'class' => 'btn btn--bordered btn--shadow btn--equal m-1 is-hover',
                            'title' => Label::getLabel('LBL_Forum_My_question_edit'),
                            'data-row_id' => $row['fque_id'],
                        ],
                        '<svg data-type="new" class="icon icon--edit icon--small">'
                            . '<use xlink:href="' . CONF_WEBROOT_FRONT_URL . 'images/forum/sprite.svg#edit"></use>'
                            . '</svg>',
                        true
                    );
                }
                if (0 < $row['fstat_comments'] || 1 == $row['fque_comments_allowed']) {
                    $dv->appendElement(
                        'a',
                        [
                            'href' => 'javascript:void(0);',
                            'class' => 'btn btn--bordered btn--shadow btn--equal m-1 is-hover',
                            'title' => Label::getLabel('LBL_Forum_My_question_View_Comments'),
                            'data-record_id' => $row['fque_id'],
                            'onClick' => 'viewComments(' . $row['fque_id'] . ')',
                        ],
                        '<svg data-type="new" class="icon icon--message">'
                            . '<use xlink:href="' . CONF_WEBROOT_FRONT_URL . 'images/forum/sprite.svg#message"></use>'
                            . '</svg>',
                        true
                    );
                }
                if (!in_array($row['fque_status'], [ForumQuestion::FORUM_QUE_SPAMMED, ForumQuestion::FORUM_QUE_DRAFT])) {
                    $dv->appendElement(
                        'a',
                        [
                            'href' => MyUtility::makeUrl('Forum', 'View', [CommonHelper::renderHtml($row['fque_slug'])], CONF_WEBROOT_FRONT_URL),
                            'class' => 'btn btn--bordered btn--shadow btn--equal m-1 is-hover',
                            'title' => Label::getLabel('LBL_Forum_My_question_View'),
                            'target' => '_blank',
                        ],
                        '<svg data-type="new" class="icon icon--message icon--small">'
                            . '<use xlink:href="' . CONF_WEBROOT_FRONT_URL . 'images/forum/sprite.svg#view-icon"></use>'
                            . '</svg>',
                        true
                    );
                }
                break;
            default:
                $div->appendElement('div', ['class' => 'flex-cell__content'], $row[$key], true);
                break;
        }
    }
}
echo $tbl->getHtml();
$pagingArr = [
    'pageSize' => $post['pagesize'],
    'page' => $post['pageno'],
    $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize']),
];
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
