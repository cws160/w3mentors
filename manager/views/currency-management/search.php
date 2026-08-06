<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'dragdrop' => '<i class="ion-arrow-move icon"></i>',
    'listserial' => Label::getLabel('LBL_SRNO'),
    'currency_name' => Label::getLabel('LBL_CURRENCY_NAME'),
    'currency_code' => Label::getLabel('LBL_CURRENCY_CODE'),
    'currency_symbol' => Label::getLabel('LBL_CURRENCY_SYMBOL'),
    'currency_active' => Label::getLabel('LBL_STATUS'),
];
if (!$canEdit) {
    unset($arr_flds['dragdrop']);
} else {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'currencyList']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$sr_no = 0;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr', []);
    $tr->setAttribute("id", $row['currency_id']);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        switch ($key) {
            case 'dragdrop':
                if ($row['currency_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'currency_active':
                $active = "active";
                $statucAct = '';
                $strTxt = Label::getLabel('LBL_Active');
                if ($row['currency_active'] == AppConstant::YES) {
                    $active = 'active';
                    $statucAct = 'inactiveStatus(this)';
                }
                if ($row['currency_active'] == AppConstant::NO) {
                    $strTxt = Label::getLabel('LBL_Inactive');
                    $active = 'inactive';
                    $statucAct = 'activeStatus(this)';
                }
                $disabledClass = "";
                $statusCls = '';
                if ($canEdit == false || $row['currency_is_default'] == AppConstant::YES) {
                    $disabledClass = "disabled-switch opacity-5";
                    $statucAct = "";
                    $statusCls = 'disabled';
                }
                $str = '<label id="' . $row['currency_id'] . '" class="statustab ' . $active . ' ' . $disabledClass . '" onclick="' . $statucAct . '">
					<span data-off="' . Label::getLabel('LBL_Active') . '" data-on="' . Label::getLabel('LBL_Inactive') . '" class="switch-labels status_' . $row['currency_id'] . '"></span>
					<span class="switch-handles '. $statusCls.'"></span>
					</label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'currency_name':
                if($row[$key] != '') {
                    $td->appendElement('plaintext', [], $row[$key], true);
                } else {
                    $td->appendElement('plaintext', [], $row['currency_code'], true);
                }
                if ($row['currency_is_default'] == AppConstant::YES) {
                    $td->appendElement('br', []);
                    $td->appendElement('plaintext', [], '<small>[' . Label::getLabel('LBL_THIS_IS_YOUR_DEFAULT_CURRENCY') . ']</small>', true);
                }
                break;
            case 'currency_code':
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'action':
                $action = new Action($row['currency_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'editCurrencyForm(' . $row['currency_id'] . ')');
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
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
?>
<script>
    $(document).ready(function() {
        $('#currencyList').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('CurrencyManagement', 'updateOrder'), order, function(res) {
                    searchCurrency(document.frmCurrencySearch);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>