<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>


<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION_HISTORY'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <div class="table-responsive">
        <?php
        $arrFlds = [
            'listserial' => Label::getLabel('LBL_srNo'),
            'user_id' => Label::getLabel('LBL_USER'),           
            'afcomhis_commission' => Label::getLabel('LBL_COMMISSION_[%]'),
            'afcomhis_created' => Label::getLabel('LBL_ADDED_ON')
        ];
        $tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table']);
        $th = $tbl->appendElement('thead')->appendElement('tr');
        foreach ($arrFlds as $val) {
            $e = $th->appendElement('th', [], $val);
        }
        $srNo = 0;
        foreach ($arrListing as $sn => $row) {
            $srNo++;
            $tr = $tbl->appendElement('tr');
            foreach ($arrFlds as $key => $val) {
                $td = $tr->appendElement('td');
                switch ($key) {
                    case 'listserial':
                        $td->appendElement('plaintext', [], $srNo);
                        break;
                    case 'user_id':
                        $str = "<span class='label label-success'>" . Label::getLabel('LBL_GLOBAL_COMMISSION') . "</span>";
                        if (!empty($row['user_id'])) {
                            $str = $row['user_first_name'] . ' ' . $row['user_last_name'];
                        }
                        $td->appendElement('plaintext', [], $str, true);
                        break;
                    case 'afcomhis_created':
                        $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                        break;
                    default:
                        $td->appendElement('plaintext', [], $row[$key]);
                        break;
                }
            }
        }
        if (count($arrListing) == 0) {
            $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORD_FOUND'));
        }
        echo $tbl->getHtml();
        ?>
    </div>
</div>