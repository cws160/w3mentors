<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'certpl_id' => Label::getLabel('LBL_SR_NO.'),
    'certpl_name' => Label::getLabel('LBL_NAME'),
    'certpl_status' => Label::getLabel('LBL_STATUS'),
    'action' => Label::getLabel('LBL_ACTION')
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$srNo = 0; 
foreach ($arrListing as $row) {
    $srNo++;
    $tr = $tbl->appendElement('tr');
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'certpl_id':
                $td->appendElement('plaintext', [], $srNo, true);
                break;
            case 'action':
                $action = new Action($row['certpl_code']);
                if ($canEdit) {
                    $action->addOtherBtn(Label::getLabel('LBL_Edit'), 'javascript:void(0)', 'edit', MyUtility::makeUrl('Certificates', 'form', [$row['certpl_code'], $row['certpl_lang_id']]));
                }
                $action->addOtherBtn(Label::getLabel('LBL_Preview'), 'javascript:void(0)', 'preview', MyUtility::makeUrl('Certificates', 'generate', [$row['certpl_id']]).'?time='.time(),'_blank');
                $td->appendElement('plaintext', ['class' => 'align-right'], $action->renderHtml(), true);
                break;
                break;
            case 'certpl_status':
                $active = "active";
                if ($row['certpl_status'] == AppConstant::NO) {
                    $active = 'inactive';
                }
                $statusClass = "";
                if ($canEdit === false) {
                    $statusClass = "disabled";
                }
                $str = '<label class="statustab ' . $active . '" ' . (($canEdit) ? 'onclick="updateStatus(\'' . $row['certpl_code'] . '\', \'' . $row['certpl_status'] . '\')"' : "") . '>
				  <span data-off="' . Label::getLabel('LBL_Active') . '" data-on="' . Label::getLabel('LBL_Inactive') . '" class="switch-labels "></span>
				  <span class="switch-handles '. $statusClass.'"></span>
				</label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}

echo $tbl->getHtml();
