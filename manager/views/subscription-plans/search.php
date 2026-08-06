<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');

$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'subplan_title' => Label::getLabel('LBL_PLAN_IDENTIFIER'),
    'name' => Label::getLabel('LBL_PLAN_Name'),
    'subplan_validity' => Label::getLabel('LBL_PLAN_VALIDITY'),
    'subplan_lesson_duration' =>  Label::getLabel('LBL_Lesson_duration'),
    'subplan_status' => Label::getLabel('LBL_PLAN_Status'),
    'subplan_created' => Label::getLabel('LBL_PLAN_Created'),
    'action' => Label::getLabel('LBL_ACTION'),
];
if ($canEdit) {
    $arr_flds = array_merge(['dragdrop' => '<i class="ion-arrow-move icon"></i>'], $arr_flds);
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered', 'id' => 'subscripiton-plans']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
$sr_no = $page == 1 ? 0 : $pageSize * ($page - 1);
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['subplan_id']);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'action':
                $ul = $td->appendElement("ul", ["class" => "actions"]);
                $li = $ul->appendElement("li");
                $li->appendElement('a', ['href' => 'javascript:void(0)', 'class' => 'button small green', 'title' => Label::getLabel('LBL_View'), "onclick" => "view(" . $row['subplan_id'] . ")"], '<svg class="svg" width="18" height="18"><use xlink:href="' . CONF_WEBROOT_BACKEND . 'images/retina/sprite-actions.svg#view"></use></svg>', true);
                if ($row['can_edit']) {
                    $li = $ul->appendElement("li");
                    $li->appendElement('a', ['href' => 'javascript:void(0)', 'class' => 'button small green', 'title' => Label::getLabel('LBL_EDIT'), "onclick" => "form(" . $row['subplan_id'] . ")"], '<svg class="svg" width="18" height="18"><use xlink:href="' . CONF_WEBROOT_BACKEND . 'images/retina/sprite-actions.svg#edit"></use></svg>', true);
                }

                break;
            case 'dragdrop':
                if ($row['subplan_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'subplan_created':
                $td->appendElement('plaintext', [], MyDate::showDate($row['subplan_created'], true));
                break;
            case 'subplan_updated':
                $td->appendElement('plaintext', [], $row['subplan_updated']);
                break;
            case 'subplan_status':
                $active = "";
                $function = '';
                if ($row['subplan_active'] == AppConstant::ACTIVE) {
                    $active = 'active';
                    $function = 'inactiveStatus(this)';
                } else {
                    $function = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['subplan_id'] . '" class="statustab status_' . $row['subplan_id'] . ' ' . $active . (($canEdit) ? '" onclick="' . $function  : "") . '">
                    <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                    <span class="switch-handles ' . $statusClass . '"></span>
                  </label>';
                $td->appendElement('plaintext', [], $str, true);
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
$postedData['page'] = $page;
echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'srchFormPaging']);
$pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'recordCount' => $recordCount];
// $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
?>
<script>
    $(document).ready(function() {
        $('#subscripiton-plans').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('subscription-plans', 'updateOrder'), order, function(res) {
                    searchSubscriptionPlans(document.srchForm);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>