<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [];
if ($canEdit) {
    $arrFlds = ['dragdrop' => '<i class="ion-arrow-move icon"></i>'];
}
$arrFlds['listserial'] = Label::getLabel('LBL_SRNO');
$arrFlds['clang_identifier'] = Label::getLabel('LBL_COURSE_LANGUAGE_IDENTIFIER');
$arrFlds['clang_name'] = Label::getLabel('LBL_COURSE_LANGUAGE_NAME');
$arrFlds['clang_active'] = Label::getLabel('LBL_STATUS');
if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'courseLanguages']);
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
    $tr->setAttribute("id", $row['clang_id']);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['clang_active'] == AppConstant::YES) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'clang_active':
                $active = "";
                $activeId = AppConstant::YES;
                if ($row['clang_active'] == AppConstant::YES) {
                    $active = 'active';
                    $activeId = AppConstant::NO;
                }
                $statusClass = '';
                $statusAct = 'changeStatus(this, \'' . $row['clang_id'] . '\')';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label data-status="' . $activeId . '" class="statustab ' . $active . '" onclick="' . $statusAct . '">
                        <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                        <span class="switch-handles ' . $statusClass . '"></span>
                    </label>';

                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $langId = !empty($row['clang_id']) ? $row['clang_id'] : 0;
                $action = new Action($row['clang_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'form(' . $row['clang_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord("' . $row['clang_id'] . '")');
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
<?php if ($canEdit) { ?>
    <script>
        $(document).ready(function() {
            $('#courseLanguages').tableDnD({
                onDrop: function(table, row) {
                    var order = $.tableDnD.serialize('id');
                    fcom.updateWithAjax(fcom.makeUrl('CourseLanguages', 'updateOrder'), order, function(res) {
                        search();
                    });
                },
                dragHandle: ".dragHandle",
            });
        });
    </script>
<?php } ?>