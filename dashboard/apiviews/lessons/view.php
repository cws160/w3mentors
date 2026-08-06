<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$startTimer = false;
if ($lesson['ordles_type'] == Lesson::TYPE_FTRAIL) {
    $lesson['ordles_tlang_id'] = '-1';
}
$endTimer = false;
if (
        $lesson['ordles_status'] == Lesson::SCHEDULED &&
        $lesson['ordles_endtime_unix'] > $lesson['ordles_currenttime_unix'] &&
        $lesson['ordles_starttime_unix'] < $lesson['ordles_currenttime_unix']
) {
    $endTimer = true;
}
?>
<script>
    const TOKEN = '<?php echo $token; ?>';
    const SCHEDULED = <?php echo Lesson::SCHEDULED ?>;
    const CANCELLED = <?php echo Lesson::CANCELLED ?>;
    const COMPLETED = <?php echo Lesson::COMPLETED ?>;
    const USER_TYPE = <?php echo FatUtility::int($siteUserType); ?>;
    var lessonStatus = <?php echo FatUtility::int($lesson['ordles_status']); ?>;
    var lessonId = <?php echo FatUtility::int($lesson['ordles_id']); ?>;
    var ordles_currenttime_unix = <?php echo FatUtility::int($lesson['ordles_currenttime_unix']); ?>;
    var ordles_starttime_unix = <?php echo FatUtility::int($lesson['ordles_starttime_unix']); ?>;
    var ordles_endtime_unix = <?php echo FatUtility::int($lesson['ordles_endtime_unix']); ?>;
    var joinTime = '<?php echo $lesson['ordles_student_starttime']; ?>';
    var canJoin = <?php echo FatUtility::int($lesson['canJoin']); ?>;
    var eneTimeMsg = "<?php echo CommonHelper::htmlEntitiesDecode(Label::getLabel('LBL_LESSON_ENDTIME_MSG')); ?>";
    var endLessonConfirmMsg = "<?php echo CommonHelper::htmlEntitiesDecode(Label::getLabel('LBL_END_LESSON_CONFIRM_MSG')); ?>";
</script>
<!-- [ PAGE ========= -->
<div class="session">
    <div class="session__head">
        <div class="session-infobar">
            <div class="row justify-content-between align-items-center">
                <div class="col-xl-8 col-lg-8 col-sm-12">
                    <div class="session-infobar__top">
                        <h4><?php echo $lesson['lessonTitle'] . ' ' . '<span class="color-primary">' . Lesson::getStatuses($lesson['ordles_status']) . '</span>' . ' ' . Label::getLabel('LBL_WITH'); ?> <?php echo $lesson['first_name'] . ' ' . $lesson['last_name']; ?></h4>
                    </div>
                    <div class="session-infobar__bottom">
                        <?php if (!empty($lesson['ordles_starttime_unix'])) { ?>
                            <div class="session-time">
                                <p>
                                    <?php echo date('H:i', $lesson['ordles_starttime_unix']) . ' - ' . date('H:i', $lesson['ordles_endtime_unix']); ?>,
                                    <?php echo date('Y-m-d', $lesson['ordles_starttime_unix']); ?>
                                </p>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-sm-12">
                    <div class="session-infobar__action">
                        <?php if ($endTimer) { ?>
                            <span class="btn btn--live" id="lessonEndTimer" timestamp="<?php echo $lesson['ordles_lesson_endtime_utc'] ?>"> 00:00:00:00 </span>
                        <?php } ?>
                        <button class="btn bg-red end_lesson_now <?php echo (!$lesson['canEnd']) ? 'd-none' : ''; ?> " id="endLesson" onclick="endLessonApp(<?php echo $lesson['ordles_id']; ?>);"><?php echo Label::getLabel('LBL_End_Lesson'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="session__body">
        <div class="sesson-window" style="background-image:url(<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_LESSON_PAGE_IMAGE, 0, Afile::SIZE_LARGE], CONF_WEBROOT_FRONT_URL) ?>)">
            <div class="sesson-window__content lessonBox" id="lessonBox" >
                <!-- session-window__frame -->
                <div class="session-status">
                    <?php if (!is_null($lesson['user_deleted'])) { ?>
                        <p><?php echo Label::getLabel('LBL_USER_NO_MORE_EXISTS'); ?></p>
                    <?php } elseif ($lesson['ordles_status'] != Lesson::SCHEDULED || $lesson['ordles_endtime_unix'] < $lesson['ordles_currenttime_unix']) { ?>
                        <div class="status_media">
                            <svg class="icon">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#clock'; ?>"></use>
                            </svg>
                        </div>
                        <p><?php echo $lesson['statusInfoLabel'] ?? ''; ?></p>
                    <?php } elseif ($lesson['canJoin']) { ?>
                        <div class="join-btns join_lesson_now">
                            <?php if ($joinFromApp) { ?>
                                <a href="javascript:void(0);" class="btn btn--primary btn--large" onclick="joinLessonApp('<?php echo $lesson['ordles_id']; ?>', false);"><?php echo Label::getLabel('LBL_JOIN_LESSON'); ?></a>
                                <div class="-gap-10"></div>
                                <!-- <a href="javascript:void(0);" class="btn btn--secondary btn--large" onclick="joinLessonApp('<?php echo $lesson['ordles_id']; ?>', true);"><?php echo Label::getLabel('LBL_JOIN_FROM_APP'); ?></a>-->
                            <?php } else { ?>
                                <a href="javascript:void(0);" class="btn btn--secondary btn--large" onclick="joinLessonApp('<?php echo $lesson['ordles_id']; ?>', false);"><?php echo Label::getLabel('LBL_JOIN_LESSON'); ?></a>
                            <?php } ?>

                        </div>
                    <?php } elseif ($lesson['ordles_status'] == Lesson::SCHEDULED) { ?>
                        <?php $startTimer = true; ?>
                        <div class="start-lesson-timer timer">
                            <h5 class="timer-title"><?php echo Label::getLabel('LBL_STARTS_IN'); ?></h5>
                            <div class="countdown-timer size_lg" id="lessonStartTimer" timestamp="<?php echo $lesson['ordles_lesson_starttime_utc']; ?>">00:00:00:00</div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ] -->
<script>
    $(document).ready(function () {
<?php if ($startTimer) { ?>
            $("#lessonStartTimer").w3mentorsTimer({
                recordId: lessonId,
                recordType: 'LESSON',
                callback: function () {
                    window.location.href = fcom.makeUrl('Lessons', 'view', [lessonId]) + "?token=" + TOKEN;
                }
            });
<?php } ?>
<?php if ($endTimer) { ?>
            $("#lessonEndTimer").w3mentorsTimer({
                recordId: lessonId,
                recordType: 'LESSON',
                callback: function () {
                    $(".join-btns").addClass('d-none');
                }
            });
            checkStatusApp(lessonId);
<?php } ?>
    });
</script>