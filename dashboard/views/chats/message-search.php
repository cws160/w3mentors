<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if ($page == 1) {
    $frm->setFormTagAttribute('class', 'form');
    $frm->developerTags['colClassPrefix'] = 'col-md-';
    $frm->developerTags['fld_default_col'] = 12;
    $messageBox = $frm->getField('message');
    $messageBox->addFieldTagAttribute('placeholder', Label::getLabel('LBL_TYPE_A_MESSAGE_HERE'));
    $messageBox->setFieldTagAttribute('id', 'message');
    $file = $frm->getField('upload');
    $file->setFieldTagAttribute('id', 'upload');
    $file->setFieldTagAttribute('onchange', 'selectFile(this);');
    $fld = $frm->getField('btn_submit');
    $fld->setFieldTagAttribute('onclick', 'sendThreadMessage(document.frmMessage); return false;');
}
$threadId = $frm->getField('thread_id')->value;
$nextPage = $page + 1;
$userTimeZone = MyUtility::getSiteTimezone();
$threadImage = $heading['image'];
$threadName = $heading['title'];
$type = $heading['type'];
if ($page == 1) {
?>
    <div class="chat-room">
        <div class="chat-room__head">
            <div class="row justify-content-between align-items-center">
                <div class="col-9">
                    <div class="msg-list align-items-center">
                        <div class="msg-list__left">
                            <div class="avtar avtar--xsmall avtar--round avtar--group">
                                <?php
                                if ($type == Thread::PRIVATE) {
                                    echo '<img src="' . $threadImage . '" alt="' . $threadName . '" />';
                                } else {
                                    echo '<img src="' . MyUtility::makeFullUrl('Images', 'group.png', [], CONF_WEBROOT_FRONT_URL) . '" alt="' . $threadName . '" />';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="msg-list__right">
                            <h6><?php echo $threadName; ?></h6>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <a href="javascript:void(0);" onclick='closethread();' class="close msg-close-js"></a>
                </div>
            </div>
        </div>
        <div class="chat-room__body">
            <div class="chat-list mt-auto">
            <?php } ?>
            <?php if ($nextPage <= ceil($recordCount / AppConstant::PAGESIZE)) { ?>
                <div class="load-more-js chat chat--info ">
                    <a id="loadMoreBtn" href="javascript:void(0)" onClick="getThread(<?php echo $threadId . ', ' . $nextPage; ?>);" class="loadmore btn btn--small btn--primary" title="<?php echo Label::getLabel('LBL_Load_Previous'); ?>"><i class="fa fa-history"></i>&nbsp;<?php echo Label::getLabel('LBL_Load_Previous'); ?></a>
                </div>
            <?php } ?>
            <?php
            $date = '';
            foreach ($records as $row) {
                $fromMe = ($row['user_id'] == $siteUserId);
                $msgDate = $row['msg_created'];
                $msgDateUnix = strtotime($msgDate);
                if (empty($date) || ($date != date('Ymd', $msgDateUnix))) {
                    $date = date('Ymd', $msgDateUnix);
                }
                if (!isset($row['file_id']) && empty($row['msg_text'])) {
                    continue;
                }
            ?>
                <div class="chat <?php echo (!$fromMe) ? 'chat--incoming' : 'chat--outgoing'; ?>" id="msgRow<?php echo $row['msg_id'] ?>">
                    <?php if (!$fromMe) { ?>
                        <div class="chat__media">
                            <div class="avtar avtar--small avtar--round" data-title="<?php echo CommonHelper::getFirstChar($row['user_name']); ?>">
                                <?php echo '<img src="' . MyUtility::makeUrl('Image', 'show', array(Afile::TYPE_USER_PROFILE_IMAGE, $row['user_id'], Afile::SIZE_SMALL), CONF_WEBROOT_FRONT_URL) . '?t=' . time() . '" alt="' . $threadName . '" />' ?>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="chat__content">
                        <div class="chat__message">
                            <?php if (!$fromMe) { ?>
                                <span style="color:<?php echo $row['user_color'] ?> !important" class="chat__user bold-600 mb-2 d-block">
                                    <?php echo $row['user_name']; ?>
                                </span>
                            <?php } ?>
                            <?php if (!empty($row['msg_text'])) { ?>
                                <div class="chat-text"><?php echo nl2br($row['msg_text']); ?></div>
                            <?php } ?>
                            <?php if (isset($row['file_id'])) { ?>
                                <div class="chat-attachment">
                                    <div class="chat-attachment__item">
                                        <div class="chat-attachment__media">
                                            <?php
                                            echo Common::getFileTypeIcon($row['file_name']);
                                            ?>
                                        </div>
                                        <div class="chat-attachment__content">
                                            <?php echo $row['file_name']; ?>
                                        </div>
                                        <div class="chat-attachment__actions">
                                            <a target="_blank" href="<?php echo MyUtility::makeUrl('Chats', 'downloadAttachment', [$row['msg_id']]); ?>" class="btn btn--small btn--transparent btn--equal color-black chat-attachment__trigger">
                                                <svg class="icon icon--download icon--small" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                    <path d="M13 10h5l-6 6-6-6h5V3h2v7zm-9 9h16v-7h2v8a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-8h2v7z"></path>
                                                </svg>
                                            </a>
                                            <?php
                                            $msgTime = new DateTime($row['msg_created'], new DateTimeZone($userTimeZone));
                                            $currentTime = new DateTime('now', new DateTimeZone($userTimeZone));
                                            $difference = $msgTime->diff($currentTime);
                                            $minutes = $difference->format('%i');
                                            if ($fromMe && $minutes < $deleteDuration) {
                                            ?>
                                                <a href="javascript:void(0)" class="btn btn--small btn--transparent btn--equal color-black chat-attachment__trigger" onclick="deleteAttachment('<?php echo $row['msg_id'] ?>')">
                                                    <svg class="icon icon--close icon--small" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                                        <path d="M12 10.586l4.95-4.95 1.414 1.414-4.95 4.95 4.95 4.95-1.414 1.414-4.95-4.95-4.95 4.95-1.414-1.414 4.95-4.95-4.95-4.95L7.05 5.636z"></path>
                                                    </svg>
                                                </a>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="chat__meta font-xsmall color-light mt-3">
                                <time class="chat__time"><?php echo MyDate::showDate($row['msg_created'], true); ?></time>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
            if ($page == 1) { ?>
            </div>
        </div>
        <div class="chat-room__footer">
            <?php echo $frm->getFormTag(); ?>
            <div class="chat-form">
                <div class="chat-form__item">
                    <?php echo $messageBox->getHTML(); ?>
                    <div id="selectedFilesList"></div>
                </div>
                <div class="chat-form__actions">
                    <div class="attach-button">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="icon icon--attachment color-black" title="<?php echo Label::getLabel('LBL_Send_Message'); ?>">
                            <path d="M14.828 7.757l-5.656 5.657a1 1 0 1 0 1.414 1.414l5.657-5.656A3 3 0 1 0 12 4.929l-5.657 5.657a5 5 0 1 0 7.071 7.07L19.071 12l1.414 1.414-5.657 5.657a7 7 0 1 1-9.9-9.9l5.658-5.656a5 5 0 0 1 7.07 7.07L12 16.244A3 3 0 1 1 7.757 12l5.657-5.657 1.414 1.414z" />
                        </svg>
                        <?php echo $frm->getFieldHtml('upload'); ?>
                    </div>
                    <div class="send-button">
                        <svg class="icon icon--arrow icon--small color-white" title="<?php echo Label::getLabel('LBL_SEND_MESSAGE'); ?>">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#up-arrow'; ?>"></use>
                        </svg>
                        <?php echo $frm->getFieldHtml('thread_id'); ?>
                        <?php echo $frm->getFieldHtml('btn_submit'); ?>
                    </div>
                </div>
            </div>
            </form>
            <?php echo $frm->getExternalJS(); ?>
            <small class="style-italic mt-3 d-flex">
                <svg class="icon icon--arrow icon--small me-2" title="Send Message" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                    <path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16zM11 7h2v2h-2V7zm0 4h2v6h-2v-6z"></path>
                </svg>
                <strong class="me-1"><?php echo Label::getLabel('LBL_Note:'); ?></strong>
                <?php
                $sizeLabel = Label::getLabel('LBL_FILE_SIZE_SHOULD_BE_LESS_THAN_{FILE-SIZE}_MB');
                $sizeLabel = str_replace('{file-size}', MyUtility::convertBitesToMb(Afile::getAllowedUploadSize(Afile::TYPE_MESSAGE_ATTACHMENT)), $sizeLabel);
                $formatsLabel = Label::getLabel('LBL_SUPPORTED_FILE_FORMATS_ARE_{file-formats}');
                $formatsLabel = str_replace('{file-formats}', implode(', ', Afile::getAllowedExts(Afile::TYPE_MESSAGE_ATTACHMENT)), $formatsLabel);
                echo $sizeLabel . ' & ' . $formatsLabel;
                ?>
            </small>
        </div>
    </div>
    <script>
        threadId = "<?php echo $threadId ?>";
        var heightLimit = 115; /* Maximum height: 115px or 5 rows */
        var textarea = document.getElementById("message");
        $(document).ready(function() {
            $('textarea[name=message]').keydown(function(event) {
                is_enter = localStorage.getItem('is_enter');
                if (event.keyCode == 13 && !event.shiftKey) {
                    $('#frm_fat_id_frmMessage').submit();
                }
            });
            $('body').on('input', '#message', function() {
                textarea.style.height = '';

                var h = Math.min(textarea.scrollHeight, heightLimit);
                textarea.style.height = h + "px";
                textarea.style.overflow = (h < heightLimit) ? 'hidden' : 'auto';
            });
        })
    </script>
<?php } ?>