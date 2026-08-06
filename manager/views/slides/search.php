<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'dragdrop' => '<i class="ion-arrow-move icon"></i>',
    'listserial' => Label::getLabel('LBL_SRNO'),
    'slide_identifier' => Label::getLabel('LBL_Title'),
    'slide_active' => Label::getLabel('LBL_Status'),
];
if (!$canEdit) {
    unset($arr_flds['dragdrop']);
} else {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'slideList']);
$th = $tbl->appendElement('thead')->appendElement('tr');
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
foreach ($arr_flds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$sr_no = 0;
foreach ($arrListing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr', []);
    $tr->setAttribute("id", $row['slide_id']);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['slide_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'slide_identifier':
                $td->appendElement('plaintext', [], $row['slide_identifier'], true);
                break;
            case 'slide_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['slide_active'] == AppConstant::YES) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                }
                if ($row['slide_active'] == AppConstant::NO) {
                    $active = 'unchecked';
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['slide_id'] . '" class="statustab status_' . $row['slide_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                        <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                        <span class="switch-handles ' . $statusClass . '"></span>
                    </label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['slide_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'addSlideForm(' . $row['slide_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord(' . $row['slide_id'] . ')');
                }
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($arrListing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], Label::getLabel('LBL_No_Records_Found'));
}
echo $tbl->getHtml();
?>
<script>
    $(document).ready(function() {
        $('#slideList').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('Slides', 'updateOrder'), order, function(res) {
                    searchSlides(document.frmSlideSearch);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>