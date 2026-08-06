<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArray = AppConstant::getYesNoArr();
$arrFlds = [];
if ($canEdit) {
    $arrFlds['dragdrop'] = '<i class="ion-arrow-move icon"></i>';
}
$arrFlds['listserial'] = Label::getLabel('LBL_SRNO');
$arrFlds['tlang_identifier'] = Label::getLabel('LBL_LANGUAGE_IDENTIFIER');
$arrFlds['tlang_name'] = Label::getLabel('LBL_Teach_Language_name');

if (($level+1) < TeachLanguage::MAX_LEVEL) {
    $arrFlds['tlang_subcategories'] = Label::getLabel('LBL_SUB_LANGUAGES');
}
if (!$adminManagePrice) {
    $arrFlds['tlang_min_price'] = Label::getLabel('LBL_MIN_PRICE/HOUR');
    $arrFlds['tlang_max_price'] = Label::getLabel('LBL_MAX_PRICE/HOUR');
} else {
    $arrFlds['tlang_hourly_price'] = Label::getLabel('LBL_PRICE/HOUR');
}

if ($parentId < 1) {
    $arrFlds['tlang_featured'] = Label::getLabel('LBL_FEATURED_TLANG');
}
$arrFlds['tlang_active'] = Label::getLabel('LBL_STATUS');

if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered table-dragable', 'id' => 'teachingLangages']);
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
    $tr->setAttribute("id", $row['tlang_id']);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        switch ($key) {
            case 'dragdrop':
                if ($row['tlang_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'tlang_max_price':
            case 'tlang_min_price':
            case 'tlang_hourly_price':
                $formatMoney = MyUtility::formatMoney($row[$key]);
                if($row['tlang_subcategories'] > 0){
                    $formatMoney = "-";
                }
                $td->appendElement('plaintext', [], $formatMoney, true);
                break;
            case 'tlang_featured':
                $td->appendElement('plaintext', [], $yesNoArray[$row['tlang_featured']]);
                break;
            case 'tlang_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['tlang_active'] == AppConstant::YES) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                }
                if ($row['tlang_active'] == AppConstant::NO) {
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['tlang_id'] . '" class="statustab status_' . $row['tlang_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                        <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                        <span class="switch-handles ' . $statusClass . '"></span>
                    </label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'tlang_subcategories':
                if($row['tlang_subcategories'] > 0){
                    $td->appendElement('a', ['href' => MyUtility::generateUrl('TeachLanguage', 'index', [$row['tlang_id']]), 'class' => 'link-text link-underline'], $row['tlang_subcategories'], true);
                }else{
                    $td->appendElement('plaintext', [], "-", true);
                }
                break;
            case 'action':
               $action = new Action($row['tlang_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'form(' . $row['tlang_id'] . ', ' . $row['tlang_parent'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'),  'deleteRecord(' . $row['tlang_id'] . ')');
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
        $('#teachingLangages').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('TeachLanguage', 'updateOrder'), order, function(res) {
                    search(document.srchForm, parentId);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>