<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_Sr.'),
    'earch_id' => Label::getLabel('LBL_Id'),
    'earch_subject' => Label::getLabel('LBL_Subject'),
    'earch_to_email' => Label::getLabel('LBL_Sent_To'),
    'earch_senton' => Label::getLabel('LBL_Sent_On'),
    'action' => 'Action'
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'earch_senton':
                $text = Label::getLabel('LBL_PENDING');
                if ($row[$key] !== null) {
                    $text = $row[$key];
                }
                $td->appendElement('plaintext', [], $text);
                break;
            case 'action':
                $ul = $td->appendElement("ul", ["class" => "actions"]);
                $li = $ul->appendElement("li");
                $li->appendElement(
                    'a',
                    ['href' => MyUtility::makeUrl(
                        'SentEmails',
                        'view',
                        [$row['earch_id']]
                    ), 'class' => 'button small green', 'title' => Label::getLabel('LBL_View_Details')],
                    '<svg class="svg" width="18" height="18">
                                            <use xlink:href="' . CONF_WEBROOT_BACKEND . 'images/retina/sprite-actions.svg#view">
                                            </use>
                                        </svg>',
                    true
                );
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
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'frmSentEmailSearchPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
