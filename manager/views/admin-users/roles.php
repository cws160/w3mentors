<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<form class="form">
    <?php
    $arr_flds = [
        'listserial' => Label::getLabel('LBL_Sr._No'),
        'module' => Label::getLabel('LBL_Module'),
        'permission' => Label::getLabel('LBL_Permissions'),
    ];
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
                case 'module':
                    $td->appendElement('plaintext', [], $row, true);
                    break;
                case 'permission':
                    $listing = AdminPrivilege::getPermissions();
                    $options = '';
                    foreach ($listing as $key => $list) {
                        $selected = '';
                        if (isset($userData[$sn]) && !empty($userData[$sn]) && $userData[$sn]['admperm_value'] == $key) {
                            $selected = 'selected';
                        }
                        $options .= "<option value=" . $key . " " . $selected . ">" . $list . "</option>";
                    }
                    $disbaled = (!$canEdit) ? 'disabled' : '';
                    $td->appendElement('plaintext', [], "<select name='permission' onChange='updatePermission(" . $sn . ",this.value)' " . $disbaled . ">" . $options . "</select>", true);
                    break;
            }
        }
    }
    if (count($arr_listing) == 0) {
        $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
    }
    echo $tbl->getHtml();
    ?>
</form>