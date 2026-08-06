<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php

$arr_flds = [
    'listserial' => Label::getLabel('LBL_SRNO'),
    'testimonial_identifier' => Label::getLabel('LBL_Identifier'),
    'testimonial_user_name' => Label::getLabel('LBL_Name'),
    'testimonial_text' => Label::getLabel('LBL_Content'),
    'testimonial_active' => Label::getLabel('LBL_Status'),
];
if ($canEdit) {
    $arr_flds['action'] = Label::getLabel('LBL_Action');
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
$activeLabel = Label::getLabel('LBL_ACTIVE');
$inactiveLabel = Label::getLabel('LBL_INACTIVE');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = 0;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['testimonial_id']);
    if ($row['testimonial_active'] != AppConstant::ACTIVE) {
        /* $tr->setAttribute ("class","is"); */
    }
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'testimonial_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['testimonial_active'] == AppConstant::ACTIVE) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                }
                if ($row['testimonial_active'] == AppConstant::INACTIVE) {
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['testimonial_id'] . '" class="statustab status_' . $row['testimonial_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                        <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                        <span class="switch-handles ' . $statusClass . '"></span>
                    </label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $action = new Action($row['testimonial_id']);
                if ($canEdit) {
                    $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'editTestimonialForm(' . $row['testimonial_id'] . ')');
                    $action->addRemoveBtn(Label::getLabel('LBL_Delete'),  'deleteRecord(' . $row['testimonial_id'] . ')');
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
