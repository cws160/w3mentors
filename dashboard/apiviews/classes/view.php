<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<script>
    const TOKEN = '<?php echo $token; ?>';
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
            <div class="row justify-content-between align-items-center">
                <div class="col-xl-8 col-lg-8 col-sm-12">
                    <div class="session-infobar__top">
                        <h4>
                            <?php echo $class['grpcls_title'] . ' <span class="color-primary">' . $class['statusText'] . '</span> ' . Label::getLabel('LBL_WITH'); ?>
                            <?php echo $class['first_name'] . ' ' . $class['last_name']; ?>
                        </h4>
                        <?php if (count($learners) > 1) { ?>
                            <div class="more-dropdown">
                                <a class="menu__item-trigger trigger-js color-secondary" href="#more-stud">&nbsp;<?php echo ' +' . (count($learners) - 1) . ' ' . Label::getLabel('LBL_More'); ?></a>
                                <ul class="menu__dropdown more--dropdown" id="more-stud">
                                    <?php
                                    foreach ($learners as $learner) {
                                        if ($learner['user_id'] == $class['order_user_id']) {
                                            continue;
                                        }
                                    ?>
                                        <li>
                                            <div class="profile-meta">
                                                <div class="profile-meta__media">
                                                    <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($learner['learner_first_name']); ?>">
                                                        <?php echo '<img src="' . FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $learner['order_user_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME) . '" />'; ?>
                                                    </span>
                                                </div>
                                                <div class="profile-meta__details">
                                                    <h4 class="bold-600"><?php echo $learner['learner_first_name'] . ' ' . $learner['learner_last_name']; ?></h4>
                                                </div>
                                            </div>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="session-infobar__bottom">
                        <?php if (!empty($class['grpcls_starttime_unix'])) { ?>
                            <div class="session-time">
                                <p>
                                    <span><?php echo date('H:i', $class['grpcls_starttime_unix']) . ' - ' . date('H:i', $class['grpcls_endtime_unix']); ?>,</span>
                                    <?php echo date('Y-m-d', $class['grpcls_starttime_unix']); ?>
                                </p>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-4 col-sm-12">
                    <div class="session-infobar__action">
                        <?php if ($class['showEndTimer']) { ?>
                            <span class="btn btn--live" id="classEndTimer" timestamp="<?php echo $class['grpcls_end_datetime_utc']; ?>"> 00:00:00:00 </span>
                        <?php } ?>
                        <button class="btn bg-red end_lesson_now <?php echo (!$class['canEnd']) ? 'd-none' : ''; ?>" id="endClass" onclick="endMeetingApp(<?php echo $classId; ?>);"><?php echo Label::getLabel('LBL_END_CLASS'); ?></button>
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
                        <p><?php echo Label::getLabel('LBL_USER_NO_MORE_EXISTS'); ?></p>
                    <?php
                    } elseif (
                        (($siteUserType == User::TEACHER && $class['grpcls_status'] != GroupClass::SCHEDULED) ||
                            ($siteUserType == User::LEARNER && $class['ordcls_status'] != OrderClass::SCHEDULED)) ||
                        $class['grpcls_endtime_unix'] < $class['grpcls_currenttime_unix']
                    ) {
                    ?>
                        <div class="status_media">
                            <svg class="icon">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#clock'; ?>"></use>
                            </svg>
                        </div>
                        <?php echo empty($class['statusInfoLabel']) ? '' : '<p>' . $class['statusInfoLabel'] . '</p>'; ?>
                    <?php } elseif ($class['canJoin']) { ?>
                        <div class="join-btns">
                            <?php if ($joinFromApp) { ?>
                                <a href="javascript:void(0);" class="btn btn--primary btn--large" onclick="joinMeetingApp('<?php echo $classId; ?>', false);"><?php echo Label::getLabel('LBL_JOIN_CLASS'); ?></a>
                                <div class="-gap-10"></div>
                                <!--                                <a href="javascript:void(0);" class="btn btn--secondary btn--large" onclick="joinMeetingApp('<?php echo $classId; ?>', true);"><?php echo Label::getLabel('LBL_JOIN_FROM_APP'); ?></a>-->
                            <?php } else { ?>
                                <a href="javascript:void(0);" class="btn btn--secondary btn--large" onclick="joinMeetingApp('<?php echo $classId; ?>', false);"><?php echo Label::getLabel('LBL_JOIN_CLASS'); ?></a>
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
<script>
    $(document).ready(function() {
        <?php if ($class['showTimer']) { ?>
            $("#classStartTimer").w3mentorsTimer({
                recordId: ordclsId,
                recordType: 'CLASS',
                callback: function() {
                    window.location.href = fcom.makeUrl('Classes', 'view', [ordclsId]) + "?token=" + TOKEN;
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
            checkStatusApp(classId);
        <?php } ?>
    });
</script>