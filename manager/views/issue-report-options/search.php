<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$arrFlds = [];
if ($canEdit) {
    $arrFlds['dragdrop'] = '<i class="ion-arrow-move icon"></i>';
}
$arrFlds['listserial'] = Label::getLabel('LBL_SRNO');
$arrFlds['tissueopt_identifier'] = Label::getLabel('LBL_IDENTIFIER');
$arrFlds['tissueoptlang_title'] = Label::getLabel('LBL_TITLE');
$arrFlds['tissueopt_active'] = Label::getLabel('LBL_STATUS');
if ($canEdit) {
    $arrFlds['action'] = Label::getLabel('LBL_ACTION');
}

$tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered table-dragable', 'id' => 'IssueReportOptions']);
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
    $tr->setAttribute("id", $row['tissueopt_id']);
    foreach ($arrFlds as $key => $val) {
        $td = $tr->appendElement('td');
        $tdAttr = ('action' == $key) ? ['class' => 'align-right'] : [];
        switch ($key) {
            case 'dragdrop':
                if ($row['tissueopt_active'] == AppConstant::ACTIVE) {
                    $td->appendElement('i', ['class' => 'ion-arrow-move icon']);
                    $td->setAttribute("class", 'dragHandle');
                }
                break;
            case 'listserial':
                $td->appendElement('plaintext', [], $srNo);
                break;
            case 'tissueopt_active':
                $active = "";
                $statusAct = 'activeStatus(this)';
                if ($row['tissueopt_active'] == AppConstant::YES) {
                    $active = 'active';
                    $statusAct = 'inactiveStatus(this)';
                }
                if ($row['tissueopt_active'] == AppConstant::NO) {
                    $statusAct = 'activeStatus(this)';
                }
                $statusClass = '';
                if ($canEdit === false) {
                    $statusClass = "disabled";
                    $statusAct = '';
                }
                $str = '<label id="' . $row['tissueopt_id'] . '" class="statustab status_' . $row['tissueopt_id'] . ' ' . $active . '" onclick="' . $statusAct . '">
                        <span data-off="' . $activeLabel . '" data-on="' . $inactiveLabel . '" class="switch-labels"></span>
                        <span class="switch-handles ' . $statusClass . '"></span>
                    </label>';

                $td->appendElement('plaintext', [], $str, true);
                break;
            case 'action':
                $data = [
                    'siteLangId' => $siteLangId,
                    'recordId' => $row['tissueopt_id']
                ];
                if ($canEdit) {
                    $data['editButton'] = [
                        'href' => 'javascript:void(0)',
                        'onclick' => 'form(' . $row['tissueopt_id'] . ')',
                        'title' => Label::getLabel('LBL_EDIT', $siteLangId),
                        'label' => ' <svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/sprite-actions.svg#edit">
                                                </use>
                                            </svg>'
                    ];
                    $data['otherButtons'] = [
                        [
                            'attr' => [
                                'href' => 'javascript:void(0)',
                                'onclick' => 'deleteRecord(' . $row['tissueopt_id'] . ')',
                                'title' => Label::getLabel('LBL_DELETE', $siteLangId),
                            ],
                            'label' => '<svg class="svg" width="18" height="18">
                                                <use
                                                    xlink:href="' . CONF_WEBROOT_URL . 'images/sprite-actions.svg#delete">
                                                </use>
                                         </svg>'
                        ]
                    ];
                }
                $actionItems = $this->includeTemplate('_partial/listing-action-buttons.php', $data, false,true);
                $td->appendElement('plaintext', $tdAttr, $actionItems, true);
                break;
            default:
                $td->appendElement('plaintext', [], $row[$key] ?? Label::getLabel('LBL_NA'), true);
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
        $('#IssueReportOptions').tableDnD({
            onDrop: function(table, row) {
                var order = $.tableDnD.serialize('id');
                fcom.ajax(fcom.makeUrl('IssueReportOptions', 'updateOrder'), order, function(res) {
                    search(document.frmIssueReoprtOptions);
                });
            },
            dragHandle: ".dragHandle",
        });
    });
</script>