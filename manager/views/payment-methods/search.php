<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'dragdrop' => '<i class="ion-arrow-move icon"></i>',
    'listserial' => Label::getLabel('LBL_SRNO'),
    'pmethod_code' => Label::getLabel('LBL_Payment_Method_Code'),
    'pmethod_name' => Label::getLabel('LBL_Payment_Method_Name'),
    'pmethod_type' => Label::getLabel('LBL_Type'),
    'pmethod_active' => Label::getLabel('LBL_Status'),
];
if (!$canEdit) {
    unset($arr_flds['dragdrop']);
} else {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'paymentMethod']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$paymentMethodType = PaymentMethod::getTypeArray();
$sr_no = 0;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr', ['id' => $row['pmethod_id'], 'class' => '']);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['pmethod_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'pmethod_type':
                $td->appendElement('plaintext', [], $paymentMethodType[$row['pmethod_type']]);
                break;
            case 'pmethod_active':
                if ($row['pmethod_code'] == WalletPay::KEY) {
                    break;
                }
                $active = "active";
                $statucAct = '';
                if ($row['pmethod_active'] == AppConstant::YES && $canEdit === true) {
                    $active = 'active';
                    $statucAct = 'inactiveStatus(this)';
                }
                if ($row['pmethod_active'] == AppConstant::NO && $canEdit === true) {
                    $active = 'inactive';
                    $statucAct = 'activeStatus(this)';
                }

                $statusClass = "";
                if ($canEdit === false) {
                    $statusClass = "disabled";
                }
                $str = '<label id="' . $row['pmethod_id'] . '" class="statustab ' . $active . '" onclick="' . $statucAct . '">
				<span data-off="' . Label::getLabel('LBL_Active') . '" data-on="' . Label::getLabel('LBL_Inactive') . '" class="switch-labels status_' . $row['pmethod_id'] . '"></span>
				<span class="switch-handles '. $statusClass.'"></span>
				</label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'pmethod_code':
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'pmethod_name':
                $td->appendElement('plaintext', [], Label::getLabel('LBL_' . $row['pmethod_code']), true);
                break;
            case 'action':
                $action = new Action($row['pmethod_id']);
                if ($canEdit && (!is_null($row['pmethod_settings']) || !is_null($row['pmethod_fees']))) {
                    if (!is_null($row['pmethod_settings'])) {
                        $action->addOtherBtn(Label::getLabel('LBL_Settings'),'settingForm("' . $row['pmethod_id'] . '")','edit','javascript:void(0)');
                    }
                    if (!is_null($row['pmethod_fees']) && $row['pmethod_type'] == PaymentMethod::TYPE_PAYOUT) {
                        $action->addOtherBtn(Label::getLabel('LBL_TXN_FEE'),'txnfeeForm("' . $row['pmethod_id'] . '")','sync-currency','javascript:void(0)');
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
if (count($arr_listing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
?>
<script>
    $(document).ready(function() {
        $('#paymentMethod').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('PaymentMethods', 'updateOrder'), order, function(res) {
                    searchGateway(document.frmGatewaySearch);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>