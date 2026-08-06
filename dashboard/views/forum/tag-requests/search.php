<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
if (0 == count($requests)) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$arrFields = [
    'sno' => Label::getLabel('LBL_Sr_No'),
    'ftagreq_name' => Label::getLabel('LBL_Tag_name'),
    'ftagreq_language_id' => Label::getLabel('LBL_language'),
    'ftagreq_status' => Label::getLabel('LBL_Status'),
    'action' => Label::getLabel('LBL_Actions')
];
$tbl = new HtmlElement('table', ['class' => 'table table--styled table--responsive table--aligned-middle']);
$th = $tbl->appendElement('tr', ['class' => 'title-row']);
foreach ($arrFields as $val) {
    $th->appendElement('th', [], $val);
}
$rowId = 1;
foreach ($requests as $sn => $row) {
    $tr = $tbl->appendElement('tr', ['id' => 'reqtag_' . $row['ftagreq_id']]);
    foreach ($arrFields as $key => $val) {
        $div = $tr->appendElement('td')->appendElement('div', ['class' => 'flex-cell']);
        $div->appendElement('div', ['class' => 'flex-cell__label'], $val, true);
        switch ($key) {
            case 'sno':
                $div->appendElement('plaintext', [], $rowId);
                break;
            case 'ftagreq_status':
                $status = Label::getLabel('LBL_NA');
                if (array_key_exists($row[$key], $statusArr)) {
                    $status = $statusArr[$row[$key]];
                }
                $statusClass = '';
                if (ForumTag::REQUEST_APPROVED == $row[$key]) {
                    $statusClass = 'color-success';
                } elseif (ForumTag::REQUEST_REJECTED == $row[$key]) {
                    $statusClass = 'color-danger';
                }
                '<span class="card-landscape__status ">Canceled</span>';
                $div->appendElement('span', ['class' => 'badge ' . $statusClass . ' badge--curve badge--small ms-0'], $status, true);
                break;
            case 'ftagreq_name':
                $div->appendElement('div', ['class' => 'flex-cell__content'], $row[$key], true);
                break;
            case 'ftagreq_language_id':
                $language = Label::getLabel('LBL_NA');
                if (array_key_exists($row[$key], $languages)) {
                    $language = $languages[$row[$key]];
                }
                $div->appendElement('plaintext', [], $language, true);
                break;
            case 'action':
                if (
                    ForumTagRequest::STATUS_APPROVED == $row['ftagreq_status'] || ForumTagRequest::STATUS_REJECTED == $row['ftagreq_status']
                ) {
                    break;
                }
                $dv = $div->appendElement('div', ['class' => 'flex-cell__content'])->appendElement('div', ['class' => 'actions-group']);
                $dv->appendElement(
                    'a',
                    [
                        'href' => 'javascript:void(0);',
                        'class' => 'btn btn--bordered btn--shadow btn--equal m-1 is-hover',
                        'title' => Label::getLabel('LBL_Forum_Edit_tag_Request'),
                        'onclick' => 'edit(' . $row['ftagreq_id'] . ')',
                        'data-row_id' => $row['ftagreq_id'],
                    ],
                    '',
                    true
                )->appendElement('svg', ['class' => "icon icon--cancel icon--small"], '<use xlink:href="' . CONF_WEBROOT_URL . 'images/sprite.svg#edit' . '"></use>', true);
                break;
            default:
                $div->appendElement('div', ['class' => 'flex-cell__content'], $row[$key], true);
                break;
        }
    }
    $rowId++;
}
echo $tbl->getHtml();
