<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (empty($threads)) {
    $this->includeTemplate('_partial/no-record-found.php');
} else {
    foreach ($threads as $sn => $row) {
        $id = $row['thread_record_id'];
        $title = $row['thread_title'];
        $liClass = ($row['thread_read'] == AppConstant::YES) ? 'is-read' : '';
?>
        <div class="msg-list msg-list--hovered <?php echo 'msg-list-' . $row['thread_id']; ?> <?php echo $liClass; ?> <?php echo ($threadId == $row['thread_id']) ? 'is-active' : '' ?>">
            <div class="msg-list__left">
                <div class="avtar avtar--xsmall avtar--round avtar--group">
                    <?php
                    if ($row['thread_type'] == Thread::PRIVATE) {
                        echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $id, Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '" alt="' . $title . '" />';
                    } else {
                        echo '<img src="' . MyUtility::makeFullUrl('Images', 'group.png', [], CONF_WEBROOT_FRONT_URL) . '" alt="' . $title . '" />';
                    }
                    ?>
                </div>
            </div>
            <div class="msg-list__right">
                <div class="msg-meta d-flex align-items-center msg-meta--avtar">
                    <date class="msg-date">
                        <?php
                        $date = $row['msg_created'] ?? $row['thread_updated'];
                        echo MyDate::showDate($date, true);
                        ?>
                    </date>
                    <h6><?php echo $title; ?></h6>
                </div>
                <div class="msg-meta d-flex align-items-center justify-content-between">
                    <?php if (isset($row['msg_text'])) { ?>
                        <div class="user-info">
                            <?php if ($row['msg_user_id'] != $siteUserId &&  $row['thread_type'] != Thread::PRIVATE) { ?>
                                <p style="color:<?php echo $row['msg_user_color'] ?> !important">
                                    <strong><?php echo CommonHelper::truncateCharacters($row['msg_user_name'], 280); ?></strong>
                                </p>
                            <?php } ?>
                            <p><?php echo CommonHelper::truncateCharacters($row['msg_text'], 280); ?></p>
                        </div>
                    <?php } ?>
                    <?php if ($row['thread_unread'] > AppConstant::NO) { ?>
                        <span class="msg-count" id="unread-thread-<?php echo $row['thread_id']; ?>"><?php echo $row['thread_unread']; ?></span>
                    <?php } ?>
                </div>
            </div>
            <a title="<?php echo ($row['thread_type'] != Thread::PRIVATE ? $title : ''); ?>" href="javascript:void(0);" onclick="getThread(<?php echo $row['thread_id']; ?>, 1);" class="msg-list__action msg-list__action-js"></a>
        </div>
<?php
    }
}
