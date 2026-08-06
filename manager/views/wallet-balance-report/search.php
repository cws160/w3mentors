<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [
    'srno' => Label::getLabel('LBL_SRNO'),
    'user_name' => Label::getLabel('LBL_USER_NAME'),
    'type' => Label::getLabel('LBL_USER_TYPE'),
    'user_wallet_balance' => Label::getLabel('LBL_REMAINING_BALANCE'),
];
$userTypeArray = User::getUserTypes();
$signUpForStr = Label::getLabel('LBL_SIGNING_UP_FOR_TEACHER');
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srno = $page == 1 ? 0 : $postedData['pagesize'] * ($page - 1);
foreach ($records as $sn => $row) {
    $srno++;
    $tr = $tbl->appendElement('tr');
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'srno':
                $td->appendElement('plaintext', [], $srno);
                break;
            case 'user_wallet_balance':
                $td->appendElement('plaintext', [], MyUtility::formatMoney($row[$key]));
                break;
            case 'type':
                $str = '<ul class="chips">';
                if($row['user_is_affiliate']){
                    $str .= '<li class="chip supplier">' . $userTypeArray[User::AFFILIATE] . '</li>';
                }
                else{
                    $str .= '<li class="chip supplier">' . $userTypeArray[User::LEARNER] . '</li>';
                    if ($row['user_is_teacher']) {
                        $str .= '<li class="chip advertiser">' . $userTypeArray[User::TEACHER]. '</li>';
                    } elseif ($row['user_registered_as'] == User::TEACHER) {
                        $str .= '<li><small class="badge badge-danger">' . $signUpForStr . '</small></li>';
                    }
                }
                $str .= '</ul>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($records) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$this->includeTemplate('_partial/pagination.php', ['pageCount' => $pageCount, 'recordCount' => $recordCount, 'page' => $page], false);