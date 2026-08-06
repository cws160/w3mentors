<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [];
if ($canEdit) {
    $arrFlds['dragdrop'] = '<i class="ion-arrow-move icon"></i>';
}
$arrFlds['listserial'] = Label::getLabel('LBL_srNo');
$arrFlds['frireason_identifier'] = Label::getLabel('LBL_IDENTIFIER');
$arrFlds['reasonLabel'] = Label::getLabel('LBL_title');
$arrFlds['frireason_active'] = Label::getLabel('LBL_STATUS');
if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered table-dragable', 'id' => 'ForumReportIssueReasons']);
$th = $tbl->appendElement('thead')->appendElement('tr');
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
foreach ($arrFlds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$srNo = 0;
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['frireason_id']);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['frireason_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'frireason_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['frireason_active'] == AppConstant::YES) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                }
                if ($row['frireason_active'] == AppConstant::NO) {
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['frireason_id'] . '" class="statustab status_' . $row['frireason_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                        <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                        <span class="switch-handles ' . $statusClass . '"></span>
                    </label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['frireason_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'form(' . $row['frireason_id'] . ')');
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
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
}
echo $tbl->getHtml();
?>
<script>
    $(document).ready(function() {
        $('#ForumReportIssueReasons').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('ForumReportIssueReasons', 'updateOrder'), order, function(res) {
                    search(document.srchFrmfrireasons);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>