<?php
$arrFlds = [
    'check' => '',
    'notifi_title' => Label::getLabel('LBL_Notification_title'),
    'notifi_added' => Label::getLabel('LBL_Notification_Sent_ON'),
];
$user_timezone = MyUtility::getSiteTimezone();
$tbl = new HtmlElement('table', ['class' => 'table-listing']);
foreach ($notifications as $sn => $notifi) {
    $notiTrcls = (is_null($notifi['notifi_read'])) ? '' : 'is-read';
    $tr = $tbl->appendElement('tr', ['class' => $notiTrcls]);
    $notificationUrl = MyUtility::makeUrl('notifications', 'readNotification', [$notifi['notifi_id']]);
    foreach ($arrFlds as $key => $val) {
        $tdClass = ($key == 'check') ? 'td--check' : (($key == 'profile') ? 'td--avtar' : '');
        $td = $tr->appendElement('td', ['class' => $tdClass]);
        switch ($key) {
            case 'check':
                $td->appendElement('plaintext', ['class' => 'td--check'], '<label class="checkbox"><input type="checkbox" class="check-record" rel=' . $notifi['notifi_id'] . '><i class="input-helper"></i></label>', true);
                break;
            case 'notifi_title':
                $txt = '<div class="listing__desc"><a href="' . $notificationUrl . '"><strong>' . $notifi[$key] . '</strong>';
                $txt .= '<br>' . CommonHelper::renderHtml($notifi['notifi_desc']) . '</a></div>';
                $td->appendElement('plaintext', [], $txt, true);
                break;
            case 'notifi_added':
                $txt = '<span class="date"> ' . MyDate::showDate($notifi['notifi_added'], true) . '</span>';
                $td->appendElement('plaintext', [], $txt, true);
                break;
            default:
                $td->appendElement('plaintext', [], '<span class="caption--td">' . $val . '</span>' . $notifi[$key], true);
                break;
        }
    }
}
if (empty($notifications)) {
    $this->includeTemplate('_partial/no-record-found.php');
} else {
?>
    <!-- [ PAGE CONTROLS ========= -->
    <div class="page-controls">
        <?php echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmNotificationSrchPaging']); ?>
        <div class="row justify-content-between g-4">
            <aside class="col-md-auto col-sm-7">
                <div class="d-flex gap-4 flex-wrap align-items-center">
                    <ul class="controls m-0">
                        <li>
                            <span>
                                <label class="checkbox">
                                    <input type="checkbox" class="check-all"><i class="input-helper"></i>
                                </label>
                            </span>
                        </li>
                    </ul>
                    <ul class="controls m-0">
                        <li>
                            <a href="javascript:void(0);" onclick="deleteRecords();" class="btn btn--bordered is-hover">
                                <span class="svg-icon">
                                    <svg class="icon icon--messaging">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#trash'; ?>"></use>
                                    </svg>
                                </span>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_Delete'); ?></div>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" onclick="searchNotification(document.frmNotificationSrch);" class="btn btn--bordered is-hover">
                                <span class="svg-icon"><svg class="icon icon--messaging">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#refresh'; ?>"></use>
                                    </svg></span>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_refresh'); ?></div>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" onclick="changeStatus(0);" class="btn btn--bordered is-hover">
                                <span class="svg-icon"><svg class="icon icon--messaging">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#closed-envelope'; ?>"></use>
                                    </svg></span>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_Mark_as_Unread'); ?></div>
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0);" onclick="changeStatus(1);" class="btn btn--bordered is-hover">
                                <span class="svg-icon">
                                    <svg class="icon icon--messaging">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#open-envelope'; ?>"></use>
                                    </svg>
                                </span>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_Mark_as_Read'); ?></div>
                            </a>
                        </li>
                    </ul>
                </div>
            </aside>
            <?php
            $this->includeTemplate('_partial/notification-pagination.php', [
                'pageCount' => $pageCount,
                'recordCount' => $recordCount,
                'pageno' => $post['pageno'],
                'pagesize' => $post['pagesize'],
                'callBackJsFunc' => 'goToSearchPage'
            ]);
            ?>
        </div>
    </div>
    <!-- ] ========= -->
    <!-- [ NOTIFICATONS ========= -->
    <?php echo $tbl->getHtml(); ?>
<?php } ?>
<script>
    $(".check-all").on('click', function() {
        if ($(this).prop('checked') == true) {
            $('.check-record').prop('checked', true);
        } else {
            $('.check-record').prop('checked', false);
        }
    });
</script>