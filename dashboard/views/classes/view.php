<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<script>
    <?php if (isset($flashcardEnabled) && $flashcardEnabled) { ?>
        const FLASHCARD_VIEW = '<?php echo Flashcard::VIEW_SHORT; ?>';
        const FLASHCARD_TYPE = '<?php echo Flashcard::TYPE_GCLASS; ?>';
        const FLASHCARD_TYPE_ID = '<?php echo $class['ordcls_id']; ?>';
        const FLASHCARD_TLANG_ID = '<?php echo $class['grpcls_tlang_id']; ?>';
    <?php } ?>
    const PUBLISHED = <?php echo GroupClass::SCHEDULED; ?>;
    const SCHEDULED = <?php echo OrderClass::SCHEDULED; ?>;
    const CANCELLED = <?php echo OrderClass::CANCELLED; ?>;
    const COMPLETED = <?php echo OrderClass::COMPLETED; ?>;
    var grpcls_currenttime_unix = <?php echo FatUtility::int($class['grpcls_currenttime_unix']); ?>;
    var grpcls_starttime_unix = <?php echo FatUtility::int($class['grpcls_starttime_unix']); ?>;
    var grpClsStatus = <?php echo FatUtility::int($class['grpcls_status']); ?>;
    var ordClsStatus = <?php echo FatUtility::int($class['ordcls_status']); ?>;
    var grpcls_endtime_unix = <?php echo FatUtility::int($class['grpcls_endtime_unix']); ?>;
    var joinTime = "<?php echo $class['joinTime']; ?>";
    var userType = <?php echo FatUtility::int($siteUserType); ?>;
    var classId = <?php echo FatUtility::int($classId); ?>;
    var grpclsId = <?php echo FatUtility::int($class['grpcls_id']); ?>;
    var ordclsId = <?php echo FatUtility::int($class['ordcls_id']); ?>;
    var canJoin = <?php echo FatUtility::int($class['canJoin']); ?>;
    var eneTimeMsg = "<?php echo CommonHelper::htmlEntitiesDecode(Label::getLabel('LBL_LESSON_ENDTIME_MSG')); ?>";
    var endClassConfirmMsg = "<?php echo CommonHelper::htmlEntitiesDecode(Label::getLabel('LBL_END_CLASS_CONFIRM_MSG')); ?>";
</script>
<!-- [ PAGE ========= -->
<div class="session">
    <div class="session__head">
        <div class="session-infobar">
            <div class="d-block d-sm-none">
                <a href="<?php echo MyUtility::makeUrl('Classes'); ?>" class="page-back d-inline-block pt-1 pb-3 m-0">
                    <svg class="icon icon--back me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M7.828,11H20v2H7.828l5.364,5.364-1.414,1.414L4,12l7.778-7.778,1.414,1.414Z"></path>
                    </svg>
                    <?php echo Label::getLabel('LBL_Back_to_My_Classes'); ?></a>
            </div>
            <div class="row justify-content-between align-items-center">
                <div class="col-xl-8 col-lg-8 col-sm-12">
                    <div class="session-infobar-flex">
                        <div class="session-infobar__top">
                            <?php if (!empty($class['grpcls_starttime_unix'])) { ?>
                                <div class="session-time">
                                    <p>
                                        <span><?php echo date(MyDate::getFormatTime(), $class['grpcls_starttime_unix']) . ' - ' . date(MyDate::getFormatTime(), $class['grpcls_endtime_unix']); ?>,</span>
                                        <?php echo date('M d, Y', $class['grpcls_starttime_unix']); ?>
                                    </p>
                                </div>
                            <?php } ?>
                            <?php if (!$class['isClassCanceled']) { ?>
                                <?php if ($class['plan']['plan_id'] > 0) { ?>
                                    <div class="session-attachments">
                                        <div class="session-resource">
                                            <a href="javascript:void(0);" onclick="viewAssignedPlan('<?php echo $class['plan']['plan_id']; ?>','<?php echo $class['plan']['plancls_id']; ?>', '<?php echo AppConstant::GCLASS; ?>')" class="attachment-file">
                                                <svg class="icon icon--issue icon--attachement icon--xsmall color-black">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#attach'; ?>"></use>
                                                </svg>
                                                <?php echo $class['plan']['plan_title']; ?>
                                            </a>
                                        </div>
                                        <?php if ($siteUserType == User::TEACHER && ($class['grpcls_starttime_unix'] > $class['grpcls_currenttime_unix'] || $class['grpcls_endtime_unix'] < $class['grpcls_currenttime_unix'])) { ?>
                                            <a href="javascript:void(0);" onclick="listLessonPlans('<?php echo $class['grpcls_id']; ?>', '<?php echo Plan::PLAN_TYPE_CLASSES; ?>');" class="underline color-black"><?php echo Label::getLabel('LBL_CHANGE'); ?></a>
                                            <a href="javascript:void(0);" onclick="removeAssignedPlan('<?php echo $class['grpcls_id']; ?>', '<?php echo Plan::PLAN_TYPE_CLASSES; ?>');" class="underline color-black"><?php echo Label::getLabel('LBL_REMOVE'); ?></a>
                                        <?php } ?>
                                    </div>
                                <?php
                                } elseif ($siteUserType == User::TEACHER && ($class['grpcls_starttime_unix'] > $class['grpcls_currenttime_unix'] || $class['grpcls_endtime_unix'] < $class['grpcls_currenttime_unix'])) {
                                ?>
                                    <div class="session-resource">
                                        <a href="javascript:void(0);" onclick="listLessonPlans('<?php echo $class['grpcls_id']; ?>', '<?php echo Plan::PLAN_TYPE_CLASSES; ?>');" class="btn btn--transparent btn--addition color-black"><?php echo Label::getLabel('LBL_ATTACH_LESSON_PLAN'); ?></a>
                                    </div>
                                <?php } ?>
                                <?php if ($class['quiz_count'] > 0) { ?>
                                    <div class="session-resource">
                                        <a href="javascript:void(0);" onclick="viewQuizzes('<?php echo $class['grpcls_id']; ?>', '<?php echo AppConstant::GCLASS; ?>')" class="attachment-file p-2">
                                            <svg class="icon icon--issue icon--attachement icon--xsmall color-black">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#attach'; ?>"></use>
                                            </svg>
                                            <?php
                                            $lbl = Label::getLabel('LBL_{quiz-count}_QUIZ(ZES)_ATTACHED');
                                            echo str_replace('{quiz-count}', $class['quiz_count'], $lbl);
                                            ?>
                                        </a>
                                        <?php if ($siteUserType == User::TEACHER && (($class['grpcls_starttime_unix'] - $class['grpcls_currenttime_unix']) > 0 || ($class['grpcls_currenttime_unix'] - $class['grpcls_endtime_unix']) > 0)) { ?>
                                            <a href="javascript:void(0);" onclick="quizListing('<?php echo $class['grpcls_id']; ?>', '<?php echo AppConstant::GCLASS; ?>')" class="underline  attachment-file p-2"><?php echo Label::getLabel('LBL_ATTACH'); ?></a>
                                        <?php } ?>
                                    </div>
                                <?php } elseif ($siteUserType == User::TEACHER && (($class['grpcls_starttime_unix'] - $class['grpcls_currenttime_unix']) > 0 || ($class['grpcls_currenttime_unix'] - $class['grpcls_endtime_unix']) > 0)) { ?>
                                    <div class="session-resource">
                                        <a href="javascript:void(0);" onclick="quizListing('<?php echo $class['grpcls_id']; ?>', '<?php echo AppConstant::GCLASS; ?>')" class="btn btn--transparent btn--addition color-black p-2"><?php echo Label::getLabel('LBL_ATTACH_QUIZ'); ?></a>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                            <?php if ($class['canMessage']) { ?>
                                <a href="javascript:void(0);" onClick="threadForm(<?php echo $class['grpcls_id']; ?>, <?php echo Thread::GROUP ?>);" class="btn btn--transparent color-primary btn--message">
                                    <svg class="icon icon--messaging">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#message'; ?>"></use>
                                    </svg>
                                    <span><?php echo Label::getLabel('LBL_SEND_MESSAGE'); ?></span>
                                </a>
                            <?php  } ?>
                        </div>
                        <div class="session-infobar__middle">
                            <h4>
                                <?php
                                echo $class['grpcls_title'];
                                echo ' <span class="color-primary badge badge--round badge--small m-0">' . $class['statusText'] . '</span> ';
                                ?>
                            </h4>
                        </div>
                        <div class="session-infobar__bottom gap-2">
                            <div class="avatar-group">
                                <?php if ($siteUserType == User::LEARNER) { ?>
                                    <div class="avatar-group-item is-hover">
                                        <figure class="avatar avatar--48 avtar--round" data-title="<?php echo CommonHelper::getFirstChar($class['first_name']); ?>">
                                            <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $class['user_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '" />'; ?>
                                        </figure>
                                        <span class="tooltip tooltip--top bg-black">
                                            <?php echo $class['first_name'] . ' ' . $class['last_name']; ?>
                                        </span>
                                    </div>
                                <?php } ?>
                                <?php
                                $userCount = 0;
                                $userList = $learners;
                                if (count($learners) > 0) {
                                    foreach ($learners as $key => $learner) {
                                        unset($userList[$key]);
                                ?>
                                        <div class="avatar-group-item is-hover">
                                            <figure class="avatar avatar--48 avtar--round" data-title="<?php echo CommonHelper::getFirstChar($learner['learner_first_name']); ?>">
                                                <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $learner['order_user_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '" />'; ?>
                                            </figure>
                                            <span class="tooltip tooltip--top bg-black">
                                                <?php echo $learner['learner_first_name'] . ' ' . $learner['learner_last_name']; ?>
                                            </span>
                                        </div>
                                        <?php
                                        $userCount++;
                                        if ($userCount == 3) {
                                            break;
                                        }
                                        ?>
                                    <?php } ?>
                                <?php } ?>
                                <?php if (count($userList) > 0) { ?>
                                    <div class="avatar-group-item is-hover">
                                        <a class="btn btn-equal btn-round btn-brand-alpha" href="javascript:void(0);" onclick="showUsers();">
                                            <svg class="icon" viewBox="0 0 24 24" fill="currentColor" style="flex: 0 0 24px;">
                                                <path d="M5 10C3.9 10 3 10.9 3 12C3 13.1 3.9 14 5 14C6.1 14 7 13.1 7 12C7 10.9 6.1 10 5 10ZM19 10C17.9 10 17 10.9 17 12C17 13.1 17.9 14 19 14C20.1 14 21 13.1 21 12C21 10.9 20.1 10 19 10ZM12 10C10.9 10 10 10.9 10 12C10 13.1 10.9 14 12 14C13.1 14 14 13.1 14 12C14 10.9 13.1 10 12 10Z"></path>
                                            </svg>
                                        </a>
                                        <span class="tooltip tooltip--top bg-black">
                                            <?php echo Label::getLabel('LBL_MORE_USERS'); ?>
                                        </span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-sm-12">
                    <div class="session-infobar__action">
                        <?php if ($class['showEndTimer']) { ?>
                            <span class="btn btn--live" id="classEndTimer" timestamp="<?php echo $class['grpcls_end_datetime_utc']; ?>"> 00:00:00:00 </span>
                        <?php } ?>
                        <button class="btn bg-red end_lesson_now <?php echo (!$class['canEnd']) ? 'd-none' : ''; ?> " id="endClass" onclick="endMeeting(<?php echo $classId; ?>);"><?php echo Label::getLabel('LBL_END_LESSON'); ?></button>
                        <?php if ($class['canCancelClass']) { ?>
                            <button onclick="cancelForm('<?php echo $classId; ?>');" class="btn btn--bordered color-third cancel-lesson--js"><?php echo Label::getLabel('LBL_CANCEL'); ?></button>
                        <?php }
                        if (!empty($class['canPlaback'])) { ?>
                            <button onclick="playbackClass('<?php echo $classId; ?>');" class="btn btn--third"><?php echo Label::getLabel('LBL_PLAYBACK'); ?></button>
                        <?php } ?>
                        <?php
                        if ($class['repiss_id'] > 0) {
                            $issueReportBtn = '<a href="javascript:void(0);" onclick="viewIssue(' . $class['repiss_id'] . ');" class="btn btn--bordered color-third ">' . Label::getLabel('LBL_VIEW_ISSUE_DETAIL') . '</a>';
                            if ($siteUserType == User::TEACHER) {
                                $issueReportBtn = '<a href="' . MyUtility::makeUrl('issues', 'index', [$class['grpcls_id']]) . '" target="_blank" class="btn btn--bordered color-third ">' . Label::getLabel('LBL_VIEW_ISSUE_DETAIL') . '</a>';
                            }
                            echo $issueReportBtn;
                        ?>
                        <?php
                        }
                        if ($class['canReportClass']) {
                        ?>
                            <button onclick="issueForm('<?php echo $classId; ?>', '<?php echo AppConstant::GCLASS; ?>');" class="btn btn--third"><?php echo Label::getLabel('LBL_REPORT_ISSUE'); ?></button>
                        <?php } ?>
                        <?php if ($class['canRateClass']) { ?>
                            <button onclick="feedbackForm('<?php echo $classId; ?>');" class="btn btn--bordered color-third "><?php echo Label::getLabel('LBL_RATE'); ?></button>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="session__body">
        <div class="sesson-window" style="background-image:url(<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_LESSON_PAGE_IMAGE, 0, Afile::SIZE_LARGE], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) ?>);">
            <div class="sesson-window__content classBox" id="classBox">
                <!-- session-window__frame -->
                <div class="session-status">
                    <?php if (!is_null($class['teacher_deleted'])) { ?>
                        <?php $link = MyUtility::makeUrl('Contact', 'index', [], CONF_WEBROOT_FRONTEND); ?>
                        <p><?php echo Label::getLabel('LBL_USER_NO_MORE_EXISTS'); ?></p>
                        <a class="btn btn--secondary" href="<?php echo $link; ?>"><?php echo Label::getLabel('LBL_CONTACT_US'); ?></a>
                    <?php } elseif ((($siteUserType == User::TEACHER && $class['grpcls_status'] != GroupClass::SCHEDULED) || ($siteUserType == User::LEARNER && $class['ordcls_status'] != OrderClass::SCHEDULED)) || $class['grpcls_endtime_unix'] < $class['grpcls_currenttime_unix']) {
                    ?>
                        <div class="status_media">
                            <svg class="icon">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#clock'; ?>"></use>
                            </svg>
                        </div>
                        <?php echo empty($class['statusInfoLabel']) ? '' : '<p>' . $class['statusInfoLabel'] . '</p>'; ?>
                        <a href="<?php echo MyUtility::makeUrl('Classes'); ?>" class="btn btn--primary btn--large"><?php echo Label::getLabel('LBL_GO_TO_CLASSES'); ?></a>
                    <?php } elseif ($class['canJoin']) { ?>
                        <div class="join-btns">
                            <?php if ($joinFromApp) { ?>
                                <a href="javascript:void(0);" class="btn btn--primary btn--large" onclick="joinMeeting('<?php echo $classId; ?>', false);"><?php echo Label::getLabel('LBL_JOIN_FROM_BROWSER'); ?></a>
                                <div class="-gap-10"></div>
                                <a href="javascript:void(0);" class="btn btn--secondary btn--large" onclick="joinMeeting('<?php echo $classId; ?>', true);"><?php echo Label::getLabel('LBL_JOIN_FROM_APP'); ?></a>
                            <?php } else { ?>
                                <a href="javascript:void(0);" class="btn btn--secondary btn--large" onclick="joinMeeting('<?php echo $classId; ?>', false);"><?php echo Label::getLabel('LBL_JOIN_CLASS'); ?></a>
                            <?php } ?>
                        </div>
                    <?php } elseif ($class['showTimer']) { ?>
                        <div class="start-lesson-timer timer">
                            <h5 class="timer-title"><?php echo Label::getLabel('LBL_STARTS_IN'); ?></h5>
                            <div class="countdown-timer size_lg" id="classStartTimer" timestamp="<?php echo $class['grpcls_start_datetime_utc']; ?>">00:00:00:00</div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="usersContentJs" style="display:none;">
    <div class="modal-header">
        <h5><?php echo Label::getLabel('LBL_USERS'); ?></h5>
        <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
    </div>
    <div class="modal-body">
        <div class="session-userlisting">
            <div class="avatar-group flex-column gap-1">
                <?php if (count($userList) > 0) { ?>
                    <?php
                    foreach ($userList as $key => $learner) {
                    ?>
                        <div class="avatar-group-item">
                            <div class="avatarprofile">
                                <figure class="avatar avatar--48 avtar--round" data-title="<?php echo CommonHelper::getFirstChar($learner['learner_first_name']); ?>">
                                    <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $learner['order_user_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '" />'; ?>
                                </figure>
                                <h6>
                                    <?php echo $learner['learner_first_name'] . ' ' . $learner['learner_last_name']; ?>
                                </h6>
                            </div>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>
<script>
    function showUsers() {
        $.w3mentorsmodal($('.usersContentJs').html());
    }
    $(document).ready(function() {
        <?php if ($class['showTimer']) { ?>
            $("#classStartTimer").w3mentorsTimer({
                recordId: ordclsId,
                recordType: 'CLASS',
                callback: function() {
                    window.location.reload();
                }
            });
        <?php } ?>
        <?php if ($class['showEndTimer']) { ?>
            $("#classEndTimer").w3mentorsTimer({
                recordId: ordclsId,
                recordType: 'CLASS',
                callback: function() {
                    $(".join-btns").addClass('d-none');
                }
            });
            checkStatus(ordclsId);
        <?php } ?>
        <?php if ($play == AppConstant::YES) { ?>
            playbackClass('<?php echo $classId; ?>');
        <?php } ?>
    });
</script>