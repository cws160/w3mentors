<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [];
if ($canEdit) {
    $arr_flds['dragdrop'] = '<i class="ion-arrow-move icon"></i>';
}
$arr_flds['listserial'] = Label::getLabel('LBL_SRNO');
$arr_flds['faqcat_identifier'] = Label::getLabel('LBL_CATEGORY_IDENTIFIER');
$arr_flds['faqcat_name'] = Label::getLabel('LBL_category_Name');
$arr_flds['faqcat_active'] = Label::getLabel('LBL_Status');

if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table', 'id' => 'faqcat']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$sr_no = 0;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    if ($row['faqcat_active'] == AppConstant::ACTIVE) {
        $tr->setAttribute("id", $row['faqcat_id']);
    }
    if ($row['faqcat_active'] != AppConstant::ACTIVE) {
        $tr->setAttribute("class", "nodrag nodrop");
    }
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['faqcat_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'select_all':
                $td->appendElement('plaintext', [], '<label class="checkbox"><input class="selectItem--js" type="checkbox" name="faqcat_ids[]" value=' . $row['faqcat_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'faqcat_identifier':
            case 'faqcat_name':
                    $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'faqcat_active':
                $active = "";
                if ($row['faqcat_active']) {
                    $active = 'checked';
                }
                $statusAct = ($canEdit === true) ? 'toggleStatus(event,this,' . AppConstant::YES . ')' : 'toggleStatus(event,this,' . AppConstant::NO . ')';
                $statusClass = ($canEdit === false) ? 'disabled' : '';
                $str = '<label class="statustab text-uppercase">
                     <input ' . $active . ' type="checkbox" id="switch' . $row['faqcat_id'] . '" value="' . $row['faqcat_id'] . '" onclick="' . $statusAct . '" class="switch-labels"/>
                    <i class="switch-handles ' . $statusClass . '"></i></label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['faqcat_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'addFaqCatForm(' . $row['faqcat_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord(' . $row['faqcat_id'] . ')');
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
$frm = new Form('frmFaqCatListing', ['id' => 'frmFaqCatListing']);
$frm->setFormTagAttribute('class', 'form last_td_nowrap');
$frm->setFormTagAttribute('onsubmit', 'formAction(this, reloadList ); return(false);');
$frm->setFormTagAttribute('action', MyUtility::makeUrl('FaqCategories', 'toggleBulkStatuses'));
$frm->addHiddenField('', 'status');
echo $frm->getFormTag();
echo $frm->getFieldHtml('status');
echo $tbl->getHtml();
?>
</form>
<?php echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']); ?>
<script>
    $(document).ready(function() {
        $('#faqcat').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('FaqCategories', 'updateOrder'), order, function(res) {
                    searchFaqCategories(document.frmSearch);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>