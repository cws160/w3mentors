<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [];
if ($type == ExtraPage::TYPE_HOMEPAGE) {
    $arr_flds['dragdrop'] = '<i class="ion-arrow-move icon"></i>';
}

$arr_flds = $arr_flds + [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'epage_identifier' => Label::getLabel('LBL_PAGE_IDENTIFIER'),
    'epage_label' => Label::getLabel('LBL_PAGE_Title'),
    'epage_active' => Label::getLabel('LBL_Status'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
} else {
    unset($arr_flds['dragdrop']);
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'blockListingTbl']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
$sr_no = 0;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr', ['id' => $row['epage_id']]);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['epage_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'epage_identifier':
                    $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'epage_label':
                if ($row['epage_label'] != '') {
                    $td->appendElement('plaintext', [], $row[$key], true);
                } else {
                    $td->appendElement('plaintext', [], Label::getLabel('LBL_N/A'), true);
                }
                break;
            case 'epage_active':

                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['epage_active'] == AppConstant::ACTIVE) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                } else {
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['epage_id'] . '" class="statustab status_' . $row['epage_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                    <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                    <span class="switch-handles ' . $statusClass . '"></span>
                  </label>';
                $td->appendElement('plaintext', [], $str, true);

                break;
            case 'action':
                $action = new Action($row['epage_id']);
                if ($canEdit &&  $row['epage_editable'] == AppConstant::YES) {
                    $action->addEditBtn(Label::getLabel('LBL_Edit'), 'addBlockFormNew(' . $row['epage_id'] . ')');
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
        $('#blockListingTbl').tableDnD({
            onDrop: function(table, row) {
                updateOrder();
            },
            dragHandle: ".dragHandle",
        });
    });
</script>