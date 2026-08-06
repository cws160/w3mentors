<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="card">
            <div class="card-table">
                <?php
                $flds = [
                    'srno' => Label::getLabel('LBL_SRNO'),
                    'app_package' => Label::getLabel('LBL_PACKAGE'),
                    'app_type' => Label::getLabel('LBL_TYPE'),
                    'app_version' => Label::getLabel('LBL_VERSION'),
                    'app_critical' => Label::getLabel('LBL_CRITICAL'),
                    'app_description' => Label::getLabel('LBL_DESCRIPTION'),
                    'app_updated' => Label::getLabel('LBL_UPDATED'),
                ];
                $tbl = new HtmlElement('table', ['width' => '100%', 'class' => 'table table--hovered']);
                $th = $tbl->appendElement('thead')->appendElement('tr');
                foreach ($flds as $val) {
                    $e = $th->appendElement('th', [], $val);
                }
                $srno = 1;
                foreach ($rows as $row) {
                    $tr = $tbl->appendElement('tr');
                    foreach ($flds as $key => $val) {
                        $td = $tr->appendElement('td');
                        switch ($key) {
                            case 'srno':
                                $td->appendElement('plaintext', [], $srno++);
                                break;
                            case 'app_type':
                                $td->appendElement('plaintext', [], AppConstant::getAppTypes($row[$key]));
                                break;
                            case 'app_critical':
                                $td->appendElement('plaintext', [], AppConstant::getYesNoArr($row[$key]));
                                break;
                            case 'app_updated':
                                $td->appendElement('plaintext', [], MyDate::showDate($row[$key], true));
                                break;
                            default:
                                $td->appendElement('plaintext', [], nl2br($row[$key]), true);
                                break;
                        }
                    }
                }
                echo $tbl->getHtml();
                ?>

            </div>
        </div>
    </div>
</main>