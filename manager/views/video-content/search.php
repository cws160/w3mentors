<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arr_flds = [
    'dragdrop' => '<i class="ion-arrow-move icon"></i>',
    'listserial' => Label::getLabel('LBL_SRNO'),
    'biblecontent_title' => Label::getLabel('LBL_IDENTIFIER'),
    'biblecontentlang_biblecontent_title' => Label::getLabel('LBL_TITLE'),
    'biblecontent_url' => Label::getLabel('LBLVIDEO_LINK'),
    'biblecontent_active' => Label::getLabel('LBL_Status'),
    'action' => Label::getLabel('LBL_ACTION'),
];
if (!$canEdit) {
    unset($arr_flds['dragdrop']);
    unset($arr_flds['action']);
}
$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table', 'id' => 'bibleList']);
$th = $tbl->appendElement('thead')->appendElement('tr');
foreach ($arr_flds as $k => $val) {
    $attr = ($k == 'dragdrop') ? ['width' => '5%'] : [];
    $e = $th->appendElement('th', $attr, $val, true);
}
$sr_no = 0;
foreach ($arr_listing as $sn => $row) {
    $sr_no++;
    $inActive = empty($row['biblecontent_active']) ? 'inactive' : '';
    $tr = $tbl->appendElement('tr', ['class' => $inActive]);
    $tr->setAttribute("id", $row['biblecontent_id']);
    foreach ($arr_flds as $key => $val) {
        $td = $tr->appendElement('td');
        switch ($key) {
            case 'dragdrop':
                if ($row['biblecontent_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $sr_no);
                break;
            case 'biblecontent_title':
            case 'biblecontentlang_biblecontent_title':
                $heading = CommonHelper::truncateCharacters($row[$key], 30, '', '...', true);
                $td->appendElement('plaintext', [], $heading, true);
                break;
            case 'biblecontent_active':
                $active = "";
                if ($row['biblecontent_active']) {
                    $active = 'active';
                }
                $statusClass = "";
                if ($canEdit === false) {
                    $statusClass = "disabled";
                }
                $statucAct = ($canEdit === true) ? 'toggleStatus(this)' : '';
                $str = '<label id="' . $row['biblecontent_id'] . '" class="statustab ' . $active . '" onclick="' . $statucAct . '">
                      <span data-off="Inactive" data-on="Active" class="switch-labels"></span>
                      <span class="switch-handles '. $statusClass.'"></span>
                    </label>';
                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                if ($canEdit) {
                    $ul = $td->appendElement("ul", ["class" => "actions"]);
                    $li = $ul->appendElement("li");
                    $li->appendElement('a', ['href' => 'javascript:void(0)', 'class' => 'button small green', 'title' => 'Edit', "onclick" => "addForm(" . $row['biblecontent_id'] . ")"], '<svg class="svg" width="18" height="18"><use xlink:href="' . CONF_WEBROOT_BACKEND . 'images/retina/sprite-actions.svg#edit"></use></svg>', true);
                    $li = $ul->appendElement("li");
                    $li->appendElement('a', ['href' => "javascript:void(0)", 'class' => 'button small green', 'title' => 'Delete', "onclick" => "deleteRecord(" . $row['biblecontent_id'] . ")"], '<svg class="svg" width="18" height="18"><use xlink:href="' . CONF_WEBROOT_BACKEND . 'images/retina/sprite-actions.svg#delete"></use></svg>', true);
                }
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key], true);
                break;
        }
    }
}
if (count($arr_listing) == 0) {
    $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arr_flds)], 'No records found');
}
echo $tbl->getHtml();
?>
<script>
    $(document).ready(function() {
        $('#bibleList').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('VideoContent', 'updateOrder'), order, function(res) {
                    searchPages(document.frmPagesSearch);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>