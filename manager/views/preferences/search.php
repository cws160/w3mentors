<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
if ($canEdit) {
    $arrFlds = ['dragdrop' => '<i class="ion-arrow-move icon"></i>'];
}
$arrFlds['listserial'] = Label::getLabel('LBL_SRNO');
$arrFlds['prefer_identifier'] = Label::getLabel('LBL_PREFERENCE_IDENTIFIER');
$arrFlds['prefer_title'] = Label::getLabel('LBL_PREFERENCE_TITLE');
if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'preferences']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arrFlds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$srNo = 0;
foreach ($arrListing as $sn => $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['prefer_id']);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                $td->setAttribute("class", 'dragHandle');
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'action':
                $action = new Action($row['prefer_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'preferenceForm(' . $row['prefer_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord(' . $row['prefer_id'] . ')');
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
        $('#preferences').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('Preferences', 'updateOrder'), order, function(res) {
                    search(document.srchForm);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>