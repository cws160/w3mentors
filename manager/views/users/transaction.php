<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_USER_TRANSACTIONS'); ?></h3>
    </div>
</div>
<div class="form-edit-head">
    <nav class="tab tab-inline">
        <ul class="tabs-nav">
            <li><a class="active" href="javascript:void(0);"><?php echo Label::getLabel('LBL_TRANSACTIONS'); ?></a></li>
            <?php if ($canEdit) { ?>
                <li><a href="javascript:void(0);" onclick="transactionForm(<?php echo $userId ?>);"><?php echo Label::getLabel('LBL_ADD_NEW'); ?></a></li>
            <?php } ?>
        </ul>
    </nav>
</div>
<div class="form-edit-body ps-0 pe-0">
    <?php
    $arrFlds = [
        'usrtxn_id' => Label::getLabel('LBL_TRANSACTION_ID'),
        'usrtxn_datetime' => Label::getLabel('LBL_USERTXN_DATE_TIME'),
        'usrtxn_amount' => Label::getLabel('LBL_USERTXN_AMOUNT'),
        'usrtxn_comment' => Label::getLabel('LBL_DESCRIPTION')
    ];
    $tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table fixed-layout']);
    $th = $tbl->appendElement('thead')->appendElement('tr');
    foreach ($arrFlds as $key => $val) {
        $e = $th->appendElement('th', [], $val, true);
    }
    $srNo = 0;
    foreach ($arrListing as $sn => $row) {
        $srNo++;
        $tr = $tbl->appendElement('tr');
        foreach ($arrFlds as $key => $val) {
            $td = $tr->appendElement('td');
            switch ($key) {
                case 'usrtxn_id':
                    $td->appendElement('plaintext', [], Transaction::formatTxnId($row[$key]));
                    break;
                case 'usrtxn_datetime':
                    $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                    break;
                case 'usrtxn_amount':
                    $td->appendElement('plaintext', [], MyUtility::formatMoney($row[$key]), true);
                    break;
                case 'usrtxn_comment':
                    $td->appendElement('plaintext', [], strip_tags($row[$key]), true);
                    break;
                default:
                    $td->appendElement('plaintext', [], $row[$key], true);
                    break;
            }
        }
    }
    if (count($arrListing) == 0) {
        $tbl->appendElement('tr')->appendElement('td', ['colspan' => count($arrFlds)], Label::getLabel('LBL_NO_RECORDS_FOUND'));
    }
    echo $tbl->getHtml();
    $postedData['page'] = $page;
    echo FatUtility::createHiddenFormFromData($postedData, ['name' => 'transactionPaging']);
    $pagingArr = ['pageCount' => $pageCount, 'page' => $page, 'pageSize' => $pageSize, 'recordCount' => $recordCount, 'callBackJsFunc' => 'goToTransactionPage'];
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
    ?>
</div>
<script>
    var userId = '<?php echo $userId; ?>';
</script>