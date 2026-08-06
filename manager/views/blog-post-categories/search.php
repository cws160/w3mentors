<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'dragdrop' => '<i class="ion-arrow-move icon"></i>',
    'listserial' => Label::getLabel('LBL_SRNO'),
    'bpcategory_identifier' => Label::getLabel('LBL_CATEGORY_IDENTIFIER'),
    'bpcategory_name' => Label::getLabel('LBL_Category_Name'),
];
if (empty($parentData)) {
    $arr_flds['child_count'] = Label::getLabel('LBL_Subcategories');
}
$arr_flds['bpcategory_active'] = Label::getLabel('LBL_Status');

if (!$canEdit) {
    unset($arr_flds['dragdrop']);
} else {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'bpcategory']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$sr_no = 0;
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    if ($row['bpcategory_active'] == AppConstant::ACTIVE) {
        $tr->setAttribute("id", $row['bpcategory_id']);
    }
    if ($row['bpcategory_active'] != AppConstant::ACTIVE) {
        $tr->setAttribute("class", " nodrag nodrop");
    }
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['bpcategory_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'bpcategory_identifier':
            case 'bpcategory_name':
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
            case 'child_count':
                if ($row[$key] == 0) {
                    $td->appendElement('plaintext', [], $row[$key], true);
                } else {
                    $td->appendElement('a', ['href' => MyUtility::makeUrl('BlogPostCategories', 'index', [$row['bpcategory_id']]), 'title' => Label::getLabel('LBL_View_Categories'), 'class' => "link-text link-underline"], $row[$key]);
                }
                break;
            case 'bpcategory_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['bpcategory_active'] == AppConstant::ACTIVE) {
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
                $str = '<label id="' . $row['bpcategory_id'] . '" class="statustab status_' . $row['bpcategory_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                <span class="switch-handles ' . $statusClass . '"></span>
              </label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['bpcategory_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'addCategoryForm(' . $row['bpcategory_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'), 'deleteRecord(' . $row['bpcategory_id'] . ')');
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
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
?>
<script>
    $(document).ready(function() {
        var pcat_id = $('#bpcategory_parent').val();
        $('#bpcategory').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                order += '&pcat_id=' + pcat_id;
                fcom.ajax(fcom.makeUrl('BlogPostCategories', 'updateOrder'), order, function (res) {
                    searchBlogPostCategories(document.srchForm);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>