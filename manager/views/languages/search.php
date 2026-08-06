<?php

defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'listserial' => Label::getLabel('LBL_Sr._No'),
    'language_code' => Label::getLabel('LBL_Language_Code'),
    'language_name' => Label::getLabel('LBL_Language_Name'),
    'language_active' => Label::getLabel('LBL_Status'),
    'action' => Label::getLabel('LBL_Action'),
];
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $val) {
    $e = $th->appendElement('th', [], $val);
}
$sr_no = 1;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $tr = $tbl->appendElement('tr');
    $tr->setAttribute("id", $row['language_id']);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'action':
                $ul = $td->appendElement("ul", ["class" => "actions"]);
                if ($canEdit) {
                    $li = $ul->appendElement("li");
                    $li->appendElement(
                        'a',
                        [
                            'href' => 'javascript:void(0)', 'class' => 'button small green',
                            'title' => Label::getLabel('LBL_Edit'), "onclick" => "editLanguageForm(" . $row['language_id'] . ")"
                        ],
                        '<svg class="svg" width="18" height="18"><use xlink:href="' . CONF_WEBROOT_BACKEND . 'images/retina/sprite-actions.svg#edit"></use></svg>',
                        true
                    );
                }
                break;
            case 'language_active':
                $active = "active";
                if (!$row['language_active']) {
                    $active = '';
                }
                $statucAct = ($canEdit === true) ? 'toggleStatus(event,this)' : '';
                $str = '<label id="' . $row['language_id'] . '" class="statustab ' . $active . '" onclick="' . $statucAct . '">
					  <span data-off="' . Label::getLabel('LBL_Active') . '" data-on="' . Label::getLabel('LBL_Inactive') . '" class="switch-labels"></span>
					  <span class="switch-handles"></span>
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
