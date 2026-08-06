<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'user_image' => Label::getLabel('LBL_IMAGE'),
    'user_full_name' => Label::getLabel('LBL_NAME/ID'),
    'user_email_phone' => Label::getLabel('LBL_EMAIL/PHONE'),
    'type' => Label::getLabel('LBL_TYPE'),
    'user_created' => Label::getLabel('LBL_REGISTERED'),
    'user_featured' => Label::getLabel('LBL_FEATURED'),
    'user_verified' => Label::getLabel('LBL_VERIFIED'),
    'user_active' => Label::getLabel('LBL_STATUS'),
    'action' => Label::getLabel('LBL_ACTION'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo = $page == 1 ? 0 : $pageSize * ($page - 1);
$userTypeArray = User::getUserTypes();
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
$signUpForStr = Label::getLabel('LBL_SIGNING_UP_FOR_TEACHER');
$yesNoArr = AppConstant::getYesNoArr();
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr', []);
    foreach ($arrFlds as $key => $val) {
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'user_full_name':
                $td->appendElement('plaintext', [], $row[$key] . '<br/>' . Label::getLabel('LBL_USER_ID') . ': ' . $row['user_id'], true);
                break;
            case 'user_image':
                $td->appendElement('img', ['style' => 'width:40px;', 'src' => MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $row['user_id']], CONF_WEBROOT_FRONT_URL)], '', true);
                break;
            case 'user_active':
                $active = "";
                $statusAct = 'changeStatus(this,1)';
                if ($row['user_active'] == AppConstant::ACTIVE) {
                    $active = 'active';
                    $statusAct = 'changeStatus(this,0)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['user_id'] . '" class="statustab status_' . $row['user_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
				  <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
				  <span class="switch-handles ' . $statusClass . '"></span>
				</label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'user_created':
                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
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
            case 'user_email_phone':
                $emailPhone = $row['user_email'] . '<br/>';
                $emailPhone .= '<span dir="ltr">' . ($ccode[$row['user_phone_code']] ?? '') . ' ' . $row['user_phone_number'] . '<span>';
                $td->appendElement('plaintext', [], $emailPhone, true);
                break;
            case 'user_featured':
                $td->appendElement('plaintext', [], AppConstant::getYesNoArr($row[$key]), true);
                break;
            case 'user_verified':
                $verified = is_null($row[$key] ?? null) ? 0 : 1;
                $td->appendElement('plaintext', [], $yesNoArr[$verified], true);
                break;
            case 'action':
                $action = new Action($row['user_id']);
                $action->addViewBtn(Label::getLabel('LBL_VIEW'),  'view(' . $row['user_id'] . ')');
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'userForm(' . $row['user_id'] . ')');
                }
                if ($canEdit == true) {
                    $action->addOtherBtn(Label::getLabel('LBL_LOGIN_INTO_PROFILE'),  'userLogin(' . $row['user_id'] . ')', 'login');
                }
                $action->addOtherBtn(Label::getLabel('LBL_TRANSACTIONS'), 'transactions(' . $row['user_id'] . ')', 'sync-currency');
                $action->addOtherBtn(Label::getLabel('LBL_ADDRESSES'), 'addresses(' . $row['user_id'] . ')', 'pin');
                if ($canEdit) {
                    $action->addOtherBtn(Label::getLabel('LBL_CHANGE_PASSWORD'), 'changePassword(' . $row['user_id'] . ')', 'password');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key] ?? Label::getLabel('LBL_NA'), true);
                break;
        }
    }
}
if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'pageSize' => $pageSize, 'recordCount' => $recordCount];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
