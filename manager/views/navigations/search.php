<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-table">
    <div class="table-responsive">
        <?php
        $arr_flds = [
            'listserial' => Label::getLabel('LBL_SRNO'),
            'nav_identifier' => Label::getLabel('LBL_IDENTIFIER'),
            'nav_name' => Label::getLabel('LBL_Title'),
            'nav_active' => Label::getLabel('LBL_Status'),
            'action' => Label::getLabel('LBL_Action'),
        ];
        $tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
        $th = $tbl->appendElement('thead')->appendElement('tr');
        foreach ($arr_flds as $val) {
            $e = $th->appendElement('th', [], $val);
        }
        $sr_no = 0;
        foreach ($arr_listing as $sn => $row) {
            $sr_no++;
            $tr = $tbl->appendElement('tr', []);
            foreach ($arr_flds as $key => $val) {
                $td = $tr->appendElement('td');
                switch ($key) {
                    case 'listserial':
                        $td->appendElement('plaintext', [], $sr_no);
                        break;
                    case 'nav_identifier':
                    case 'nav_name':
                        $td->appendElement('plaintext', [], $row[$key], true);
                        break;
                    case 'nav_active':
                        $active = "";
                        if ($row['nav_active']) {
                            $active = 'checked';
                        }
                        $statusAct = ($canEdit === true) ? 'toggleStatus(event,this,' . AppConstant::YES . ')' : 'toggleStatus(event,this,' . AppConstant::NO . ')';
                        $statusClass = ($canEdit === false) ? 'disabled' : '';
                        $str = '<label class="statustab text-uppercase">
                     <input ' . $active . ' type="checkbox" id="switch' . $row['nav_id'] . '" value="' . $row['nav_id'] . '" onclick="' . $statusAct . '" class="switch-labels"/>
                    <i class="switch-handles ' . $statusClass . '"></i> </label>';
                        $td->appendElement('plaintext', [], $str, true);
                        break;
                    case 'action':
                        $action = new Action($row['nav_id']);
                        if ($canEdit) {
                            $action->addEditBtn(Label::getLabel('LBL_EDIT'),  'addFormNew(' . $row['nav_id'] . ')');
                        }
                        $action->addOtherBtn(Label::getLabel('LBL_Pages'), 'javascript:void(0)', 'list', MyUtility::makeUrl('Navigations', 'pages', [$row['nav_id']]));
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
        ?>
    </div>
</div>