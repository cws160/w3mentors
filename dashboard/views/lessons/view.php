<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$joinTime = ($siteUserType == User::LEARNER) ? $lesson['ordles_student_starttime'] : $lesson['ordles_teacher_starttime'];
$startTimer = false;
$endTimer = false;
if ($lesson['ordles_type'] == Lesson::TYPE_FTRAIL) {
    $lesson['ordles_tlang_id'] = '-1';
}
?>
<script>
    <?php if (!empty($flashcardEnabled)) { ?>
        const FLASHCARD_VIEW = '<?php echo Flashcard::VIEW_SHORT; ?>';
        const FLASHCARD_TYPE = '<?php echo Flashcard::TYPE_LESSON; ?>';
        const FLASHCARD_TYPE_ID = '<?php echo $lesson['ordles_id']; ?>';
        const FLASHCARD_TLANG_ID = '<?php echo $lesson['ordles_tlang_id']; ?>';
    <?php } ?>
    const TOKEN = '';
    const SCHEDULED = <?php echo Lesson::SCHEDULED ?>;
    const CANCELLED = <?php echo Lesson::CANCELLED ?>;
    const COMPLETED = <?php echo Lesson::COMPLETED ?>;
    const USER_TYPE = <?php echo FatUtility::int($siteUserType); ?>;
    var lessonStatus = <?php echo FatUtility::int($lesson['ordles_status']); ?>;
    var lessonId = <?php echo FatUtility::int($lesson['ordles_id']); ?>;
    var ordles_currenttime_unix = <?php echo FatUtility::int($lesson['ordles_currenttime_unix']); ?>;
    var ordles_starttime_unix = <?php echo FatUtility::int($lesson['ordles_lesson_starttime_utc'] ?? 0); ?>;
    var ordles_endtime_unix = <?php echo FatUtility::int($lesson['ordles_lesson_endtime_utc'] ?? 0); ?>;
    var joinTime = '<?php echo $joinTime; ?>';
    var canJoin = <?php echo FatUtility::int($lesson['canJoin']); ?>;
    var endLessonConfirmMsg = "<?php echo CommonHelper::htmlEntitiesDecode(Label::getLabel('LBL_END_LESSON_CONFIRM_MSG')); ?>";
</script>
<!-- [ PAGE ========= -->
<div class="session">
    <div class="session__head">
        <div class="session-infobar">

            <div class="d-block d-sm-none">
                <a href="<?php echo MyUtility::makeUrl('Lessons'); ?>" class="page-back d-inline-block pt-1 pb-3 m-0">
                    <svg class="icon icon--back me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M7.828,11H20v2H7.828l5.364,5.364-1.414,1.414L4,12l7.778-7.778,1.414,1.414Z"></path>
                    </svg>
                    <?php echo Label::getLabel('LBL_Back_to_My_Lessons'); ?></a>
            </div>

            <div class="row justify-content-between align-items-center">
                <div class="col-xl-8 col-lg-8 col-sm-12">
                    <div class="session-infobar-flex">
                        <div class="session-infobar__top">
                            <?php if (!empty($lesson['ordles_starttime_unix'])) { ?>
                                <div class="session-time">
                                    <p>
                                        <?php $tFmt = MyDate::getFormatTime() ?>
                                        <span><?php echo date($tFmt, $lesson['ordles_starttime_unix']) . ' - ' . date($tFmt, $lesson['ordles_endtime_unix']); ?>,</span>
                                        <?php echo date('M d, Y', $lesson['ordles_starttime_unix']); ?>
                                    </p>
                                </div>
                            <?php } ?>
                            <?php if ($lesson['ordles_status'] != Lesson::CANCELLED) { ?>
                                <?php if ($lesson['plan']['plan_id'] > 0) { ?>
                                    <div class="session-resource">
                                        <a href="javascript:void(0);" onclick="viewAssignedPlan('<?php echo $lesson['plan']['plan_id']; ?>', '<?php echo $lesson['plan']['planles_id']; ?>', '<?php echo AppConstant::LESSON; ?>')" class="attachment-file">
                                            <svg class="icon icon--issue icon--attachement icon--xsmall color-black">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#attach'; ?>"></use>
                                            </svg>
                                            <?php echo $lesson['plan']['plan_title'] ?>
                                        </a>
                                        <?php if ($siteUserType == User::TEACHER && ($lesson['ordles_starttime_unix'] > $lesson['ordles_currenttime_unix'] || $lesson['ordles_endtime_unix'] < $lesson['ordles_currenttime_unix'])) { ?>
                                            <a href="javascript:void(0);" onclick="listLessonPlans('<?php echo $lesson['ordles_id']; ?>', '<?php echo Plan::PLAN_TYPE_LESSONS; ?>');" class="underline attachment-file"><?php echo Label::getLabel('LBL_CHANGE'); ?></a>
                                            <a href="javascript:void(0);" onclick="removeAssignedPlan('<?php echo $lesson['ordles_id']; ?>', '<?php echo Plan::PLAN_TYPE_LESSONS; ?>');" class="underline attachment-file"><?php echo Label::getLabel('LBL_REMOVE'); ?></a>
                                        <?php } ?>
                                    </div>
                                <?php } elseif ($siteUserType == User::TEACHER && $lesson['ordles_status'] != Lesson::CANCELLED && ($lesson['ordles_starttime_unix'] > $lesson['ordles_currenttime_unix'] || $lesson['ordles_endtime_unix'] < $lesson['ordles_currenttime_unix'])) { ?>
                                    <div class="session-resource">
                                        <a href="javascript:void(0);" onclick="listLessonPlans('<?php echo $lesson['ordles_id']; ?>', '<?php echo Plan::PLAN_TYPE_LESSONS; ?>');" class="btn btn--transparent btn--addition color-black"><?php echo Label::getLabel('LBL_ATTACH_LESSON_PLAN'); ?></a>
                                    </div>
                                <?php } ?>
                                <?php if ($lesson['quiz_count'] > 0) { ?>
                                    <div class="session-resource">
                                        <a href="javascript:void(0);" onclick="viewQuizzes('<?php echo $lesson['ordles_id']; ?>', '<?php echo AppConstant::LESSON; ?>')" class="attachment-file p-2">
                                            <svg class="icon icon--issue icon--attachement icon--xsmall color-black">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#attach'; ?>"></use>
                                            </svg>
                                            <?php
                                            $lbl = Label::getLabel('LBL_{quiz-count}_QUIZ(ZES)_ATTACHED');
                                            echo str_replace('{quiz-count}', $lesson['quiz_count'], $lbl);
                                            ?>
                                        </a>
                                        <?php if ($siteUserType == User::TEACHER && ($lesson['ordles_starttime_unix'] > $lesson['ordles_currenttime_unix'] || $lesson['ordles_endtime_unix'] < $lesson['ordles_currenttime_unix'])) { ?>
                                            <a href="javascript:void(0);" onclick="quizListing('<?php echo $lesson['ordles_id']; ?>', '<?php echo AppConstant::LESSON; ?>')" class="underline attachment-file p-2"><?php echo Label::getLabel('LBL_ATTACH'); ?></a>
                                        <?php } ?>
                                    </div>
                                <?php } elseif ($siteUserType == User::TEACHER && ($lesson['ordles_starttime_unix'] > $lesson['ordles_currenttime_unix'] || $lesson['ordles_endtime_unix'] < $lesson['ordles_currenttime_unix'])) { ?>
                                    <div class="session-resource">
                                        <a href="javascript:void(0);" onclick="quizListing('<?php echo $lesson['ordles_id']; ?>', '<?php echo AppConstant::LESSON; ?>');" class="btn btn--transparent btn--addition color-black p-2"><?php echo Label::getLabel('LBL_ATTACH_QUIZ'); ?></a>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                        </div>
                        <div class="session-infobar__middle">
                            <h4><?php echo $lesson['lessonTitle'] . ' ' . '<span class="color-primary badge badge--round badge--small m-0">' . Lesson::getStatuses($lesson['ordles_status']) . '</span>'; ?></h4>
                        </div>
                        <div class="session-infobar__bottom">
                            <div class="avatar-group">
                                <div class="avatar-group-item is-hover">
                                    <figure class="avatar avatar--48 avtar--round" data-title="<?php echo CommonHelper::getFirstChar($lesson['first_name']); ?>">
                                        <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $lesson['user_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '" />'; ?>
                                    </figure>
                                    <span class="tooltip tooltip--top bg-black">
                                        <?php echo $lesson['first_name'] . ' ' . $lesson['last_name']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-sm-12">
                    <div class="session-infobar__action">
                        <?php if ($lesson['ordles_status'] == Lesson::SCHEDULED && $lesson['ordles_endtime_unix'] > $lesson['ordles_currenttime_unix'] && $lesson['ordles_starttime_unix'] <= $lesson['ordles_currenttime_unix']) { ?>
                            <?php $endTimer = true; ?>
                            <span class="btn btn--live" id="lessonEndTimer" timestamp="<?php echo $lesson['ordles_lesson_endtime_utc'] ?>"> 00:00:00:00 </span>
                        <?php } ?>
                        <button class="btn bg-red end_lesson_now <?php echo (!$lesson['canEnd']) ? 'd-none' : ''; ?> " id="endLesson" onclick="endLesson(<?php echo $lesson['ordles_id']; ?>);"><?php echo Label::getLabel('LBL_End_Lesson'); ?></button>
                        <?php if ($lesson['canCancelLesson']) { ?>
                            <button onclick="cancelForm('<?php echo $lesson['ordles_id']; ?>');" class="btn btn--bordered color-third cancel-lesson--js"><?php echo Label::getLabel('LBL_Cancel'); ?></button>
                        <?php } ?>
                        <?php if ($lesson['canRescheduleLesson']) { ?>
                            <button onclick="rescheduleForm('<?php echo $lesson['ordles_id']; ?>');" class="btn btn--third reschedule-lesson--js"><?php echo Label::getLabel('LBL_RESCHEDULE'); ?></button>
                        <?php } ?>
                        <?php if ($lesson['canScheduleLesson']) { ?>
                            <button onclick="scheduleForm('<?php echo $lesson['ordles_id']; ?>');" class="btn btn--third"><?php echo Label::getLabel('LBL_SCHEDULE'); ?></button>
                        <?php } ?>
                        <?php if ($lesson['repiss_id'] > 0) { ?>
                            <button onclick="viewIssue('<?php echo $lesson['repiss_id']; ?>');" class="btn btn--bordered color-third"> <?php echo Label::getLabel('LBL_Issue'); ?> </button>
                        <?php }
                        if ($lesson['canPlaback']) { ?>
                            <button onclick="playbackLesson('<?php echo $lesson['ordles_id']; ?>');" class="btn btn--third"> <?php echo Label::getLabel('LBL_PLAYBACK'); ?> </button>
                        <?php }
                        if ($lesson['canReportIssue']) { ?>
                            <button onclick="issueForm('<?php echo $lesson['ordles_id']; ?>', '<?php echo AppConstant::LESSON; ?>');" class="btn btn--third"> <?php echo Label::getLabel('LBL_REPORT'); ?> </button>
                        <?php }
                        if ($lesson['canRateLesson']) { ?>
                            <button onclick="feedbackForm('<?php echo $lesson['ordles_id']; ?>');" class="btn btn--third"> <?php echo Label::getLabel('LBL_Rate'); ?> </button>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="session__body">
        <div class="sesson-window" style="background-image:url(<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_LESSON_PAGE_IMAGE, 0, Afile::SIZE_LARGE], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) ?>)">
            <div class="sesson-window__content lessonBox" id="lessonBox">
                <!-- session-window__frame -->
                <div class="session-status">
                    <?php if (!is_null($lesson['user_deleted'])) { ?>
                        <?php $link = MyUtility::makeUrl('Contact', 'index', [], CONF_WEBROOT_FRONTEND); ?>
                        <p><?php echo Label::getLabel('LBL_USER_NO_MORE_EXISTS'); ?></p>
                        <a class="btn btn--secondary" href="<?php echo $link; ?>"><?php echo Label::getLabel('LBL_CONTACT_US'); ?></a>
                    <?php } elseif ($lesson['ordles_status'] != Lesson::SCHEDULED || $lesson['ordles_endtime_unix'] < $lesson['ordles_currenttime_unix']) { ?>
                        <div class="status_media">
                            <svg class="icon">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#clock'; ?>"></use>
                            </svg>
                        </div>
                        <?php echo empty($lesson['statusInfoLabel']) ? '' : '<p>' . $lesson['statusInfoLabel'] . '</p>'; ?>
                        <a href="<?php echo MyUtility::makeUrl('Lessons'); ?>" class="btn btn--primary btn--large"><?php echo Label::getLabel('LBL_GO_TO_LESSONS'); ?></a>
                    <?php } elseif ($lesson['canJoin']) { ?>
                        <div class="join-btns join_lesson_now">
                            <?php if ($joinFromApp) { ?>
                                <a href="javascript:void(0);" class="btn btn--primary btn--large" onclick="joinLesson('<?php echo $lesson['ordles_id']; ?>', false);"><?php echo Label::getLabel('LBL_JOIN_FROM_BROWSER'); ?></a>
                                <div class="-gap-10"></div>
                                <a href="javascript:void(0);" class="btn btn--secondary btn--large" onclick="joinLesson('<?php echo $lesson['ordles_id']; ?>', true);"><?php echo Label::getLabel('LBL_JOIN_FROM_APP'); ?></a>
                            <?php } else { ?>
                                <a href="javascript:void(0);" class="btn btn--secondary btn--large" onclick="joinLesson('<?php echo $lesson['ordles_id']; ?>', false);"><?php echo Label::getLabel('LBL_JOIN_LESSON'); ?></a>
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
    $(document).ready(function() {
        <?php if ($startTimer) { ?>
            $("#lessonStartTimer").w3mentorsTimer({
                recordId: lessonId,
                recordType: 'LESSON',
                callback: function() {
                    window.location.reload();
                }
            });
        <?php } ?>
        <?php if ($endTimer) { ?>
            $("#lessonEndTimer").w3mentorsTimer({
                recordId: lessonId,
                recordType: 'LESSON',
                callback: function() {
                    $(".join-btns").addClass('d-none');
                }
            });
            checkStatus(lessonId);
        <?php } ?>
        <?php if ($play == AppConstant::YES) { ?>
            playbackLesson('<?php echo $lesson['ordles_id']; ?>');
        <?php } ?>
    });
</script>