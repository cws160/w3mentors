<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [];
if ($canEdit) {
    $arrFlds = ['dragdrop' => '<i class="ion-arrow-move icon"></i>'];
}
$arrFlds['listserial'] = Label::getLabel('LBL_SRNO');
$arrFlds['slang_identifier'] = Label::getLabel('LBL_SPEAK_LANGUAGE_IDENTIFIER');
$arrFlds['slang_name'] = Label::getLabel('LBL_SPEAK_LANGUAGE_NAME');
$arrFlds['slang_active'] = Label::getLabel('LBL_STATUS');
if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'spokenLangages']);
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
    $tr->setAttribute("id", $row['slang_id']);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        switch ($key) {
            case 'dragdrop':
                if ($row['slang_active'] == AppConstant::YES) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'slang_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['slang_active'] == AppConstant::YES) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                }
                if ($row['slang_active'] == AppConstant::NO) {
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['slang_id'] . '" class="statustab status_' . $row['slang_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                        <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                        <span class="switch-handles ' . $statusClass . '"></span>
                    </label>';

                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['slang_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'form(' . $row['slang_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'),  'deleteRecord(' . $row['slang_id'] . ')');
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
        $('#spokenLangages').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('SpeakLanguage', 'updateOrder'), order, function(res) {
                    search(document.frmSpokenLanguageSearch);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>