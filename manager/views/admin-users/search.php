<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'admin_name' => Label::getLabel('LBL_FULL_NAME'),
    'admin_username' => Label::getLabel('LBL_USERNAME'),
    'admin_email' => Label::getLabel('LBL_EMAIL'),
    'admin_active' => Label::getLabel('LBL_STATUS'),
];
if ($canEdit || $canViewAdminPermissions) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = 0;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'action':
                if ($row['admin_id'] == $adminLoggedInId || $row['admin_id'] == 1) {
                    break;
                }
                $action = new Action($row['admin_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'editForm(' . $row['admin_id'] . ')');
                    $action->addOtherBtn(Label::getLabel('LBL_CHANGE_PASSWORD'), 'changePasswordForm(' . $row['admin_id'] . ')', 'password');
                }
                if ($row['admin_id'] > 1 && $row['admin_id'] != $adminLoggedInId && $canViewAdminPermissions) {
                    $action->addOtherBtn(Label::getLabel('LBL_Admin_Permissions'), 'javascript:void(0)', 'user-permission', MyUtility::makeUrl('AdminUsers', 'permissions', [$row['admin_id']]));
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            case 'admin_active':
                if ($row['admin_id'] > 1 && $row['admin_id'] != $adminLoggedInId) {
                    $active = "active";
                    $statucAct = '';
                    $statusClass = '';
                    if ($row['admin_active'] == AppConstant::YES) {
                        $active = 'active';
                        if ($canEdit) {
                            $statucAct = 'inactiveStatus(this)';
                        }
                    }
                    if ($row['admin_active'] == AppConstant::NO) {
                        $active = '';
                        if ($canEdit) {
                            $statucAct = 'activeStatus(this)';
                        }
                    }
                    if ($canEdit === false) {
                        $statusClass = "disabled";
                    }
                    $str = '<label id="' . $row['admin_id'] . '" class="statustab ' . $active . ' status_' . $row['admin_id'] . '" onclick="' . $statucAct . '">
                                <span data-off="' . Label::getLabel('LBL_Active') . '" data-on="' . Label::getLabel('LBL_Inactive') . '" class="switch-labels"></span>
                                <span class="switch-handles ' . $statusClass . '"></span>
                            </label>';
                    $td->appendElement('plaintext', [], $str, true);
                }
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
