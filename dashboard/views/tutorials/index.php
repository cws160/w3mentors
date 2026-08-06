<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
echo $this->includeTemplate('tutorials/head-section.php', [
    'progress' => $progress,
    'progressId' => $progressId,
    'siteLangId' => $siteLangId,
    'siteUserId' => $siteUserId,
    'siteUserType' => $siteUserType,
    'course' => $course,
    'controllerName' => $controllerName,
    'canDownloadCertificate' => $canDownloadCertificate,
    'action' => $actionName,
    'editRequestStatus' => $editRequestStatus,
    'requestDate' => $requestDate,
]);
?>
<!-- [ BODY ========= -->
<div class="body">
    <!-- [ BODY PANEL ========= -->

    <div class="body-panel">
        <div class="section-intro videoContentJs">
            <div class="course-video ratio ratio--2by1 " style="display: none;">
                <?php if (FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_VIDEO_CIPHER) { ?>
                    <script src="https://player.vdocipher.com/v2/api.js"></script>
                    <iframe data-id="vdocipher" src="" style="border:0;width:100%;height:100%" allow="encrypted-media" allowfullscreen></iframe>
                    <script>
                        var iframe = document.querySelector("iframe");
                        var player = VdoPlayer.getInstance(iframe);

                        var playvideo = () => {
                            player.video.play();
                        };
                        var endedHandler = () => {
                            getLecture(1, 1);
                        };
                        player.video.addEventListener("play", playvideo);

                        player.video.addEventListener("ended", endedHandler);
                    </script>
                <?php } else { ?>
                    <script src="https://cdn.jsdelivr.net/npm/@mux/mux-player"></script>
                    <mux-player playback-id="" metadata-video-title="Placeholder (optional)" metadata-viewer-user-id="Placeholder (optional)" accent-color="#FF0000"></mux-player>
                    <script>
                        var muxPlayer = document.querySelector("mux-player");
                        var playvideo = () => {
                            muxPlayer.play();
                        };
                        var endedHandler = () => {
                            getLecture(1, 1);
                        };
                        muxPlayer.addEventListener("play", playvideo);
                        muxPlayer.addEventListener("ended", endedHandler);
                    </script>
                <?php } ?>
            </div>
            <div class="course-video-error ratio ratio--2by1 heading-4 color-danger" style="display: none;">
                <div class="d-flex justify-content-center align-items-center direction-column">
                    <svg fill="var(--color-danger)">
                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#issue"></use>
                    </svg>
                    <span></span>
                </div>
            </div>
            <div class="course-quiz ratio ratio--2by1 " style="display: none;">
            </div>
            <div class="directions">
                <a href="javascript:void(0)" class="directions-prev getPrevJs" style="display:none;">
                    <span class="directions-title directionTitleJs"></span>
                    <span href="javascript:void(0)" class="directions-prev__control"></span>
                </a>
                <a href="javascript:void(0)" class="directions-next getNextJs" style="display:none;">
                    <span class="directions-title directionTitleJs"></span>
                    <span href="javascript:void(0)" class="directions-next__control"></span>
                </a>
            </div>
        </div>
        <div class="section-layout">
            <div class="section-layout__head">
                <div class="container">
                    <div class="breadcrumbs">
                        <ul>
                            <li>
                                <a href="<?php echo MyUtility::makeUrl('Learner') ?>">
                                    <?php echo Label::getLabel('LBL_DASHBOARD') ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo MyUtility::makeUrl('Courses') ?>">
                                    <?php echo Label::getLabel('LBL_MY_COURSES'); ?>
                                </a>
                            </li>
                            <li><?php
                                echo $course['course_title'];
                                ?>
                            </li>

                        </ul>
                    </div>
                    <h2 class="page-subtitle mb-4 lectureTitleJs"></h2>
                    <div class="section-links">
                        <div class="section-links__left">
                            <nav class="tabs tabs--line border-bottom-0 tabs-scrollable-js tutorialTabsJs">
                                <ul>
                                    <li class="d-xl-none d-block responsive-toggle-js">
                                        <a href="javascript:void(0);">
                                            <?php echo Label::getLabel('LBL_COURSE_LECTURES'); ?>
                                        </a>
                                    </li>
                                    <li class="is-active">
                                        <a href="javascript:void(0);" class="crsDetailTabJs lecTitleJs" onclick="loadLecture(0);">
                                            <?php echo Label::getLabel('LBL_LECTURE_DETAIL'); ?>
                                        </a>
                                        <a href="javascript:void(0);" class="crsDetailTabJs quizTitleJs" onclick="openQuiz('<?php echo $course['course_quilin_id'] ?>');">
                                            <span class="quizTitleJs" style="display:none;">
                                                <?php echo Label::getLabel('LBL_QUIZ_DETAIL'); ?>
                                            </span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" onclick="getNotes('<?php echo $progress['crspro_ordcrs_id']; ?>');">
                                            <?php echo Label::getLabel('LBL_NOTES'); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" onclick="getReviews();">
                                            <?php echo Label::getLabel('LBL_REVIEWS') . ' (' . $course['course_reviews'] . ')'; ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="javascript:void(0);" onclick="getTutorInfo();">
                                            <?php echo stripslashes(Label::getLabel("LBL_TUTORS_INFO")); ?>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <div class="section-links__right">
                        </div>
                    </div>
                </div>
            </div>
            <div class="section-layout__body">
                <div class="container">
                    <!-- [ BODY RIGHT PANEL ========= -->
                    <sidebar class="body-side">
                        <div class="toggle-control-list responsive-target-js sidebarPanelJs">
                            <?php
                            if ($sections) {
                                $i = 1;
                                foreach ($sections as $section) { ?>
                                    <div class="toggle-control control-group-js sectionListJs">
                                        <div class="toggle-control__action control-trigger-js">
                                            <h6>
                                                <?php
                                                echo Label::getLabel('LBL_SECTION') . ' ' . $section['section_order'] . ': ';
                                                echo $section['section_title'];
                                                ?>
                                            </h6>
                                            <p>
                                                <span class="completedLecture<?php echo $section['section_id'] ?>">
                                                    <?php echo isset($lectureStats[$section['section_id']]) ? count($lectureStats[$section['section_id']]) : 0; ?>
                                                </span>
                                                <?php
                                                echo ' / ' . $section['section_lectures'];
                                                $duration = CommonHelper::convertDuration($section['section_duration']);
                                                echo !empty($duration) ? ' | ' . $duration : '';
                                                ?>
                                                <?php
                                                if (CourseUtility::isContentUpdated($course['course_status'], $section['section_updated'])) { ?>
                                                    <span class="card-landscape__status badge color-success badge--curve badge--small ms-0"><?php echo Label::getLabel('LBL_UPDATED'); ?></span>
                                                <?php }
                                                ?>
                                            </p>
                                        </div>
                                        <div class="toggle-control__target control-target-js">
                                            <div class="lecture-list lecturesListJs">
                                                <!-- [ LECTURE ========= -->
                                                <?php
                                                if (isset($section['lectures']) && count($section['lectures']) > 0) {
                                                    foreach ($section['lectures'] as $lesson) {
                                                        $isCovered = (in_array($lesson['lecture_id'], $lectureStats[$section['section_id']])) ? true : false;
                                                        $isActive = ($progress['crspro_lecture_id'] == $lesson['lecture_id']) ? 'is-active' : '';
                                                ?>
                                                        <div class="lecture <?php echo $isActive; ?>" id="lectureJs<?php echo $lesson['lecture_id']; ?>">
                                                            <div class="lecture__control is-hover">
                                                                <label class="lecture-checkbox">
                                                                    <input type="checkbox" name="lecture_id" data-section="<?php echo $section['section_id']; ?>" value="<?php echo $lesson['lecture_id']; ?>" <?php echo ($isCovered) ? 'checked="checked"' : ''; ?>>
                                                                    <i class="lecture-checkbox__view"></i>
                                                                </label>
                                                                <div class="tooltip tooltip--right bg-black">
                                                                    <?php echo Label::getLabel('LBL_MARK_READ'); ?>
                                                                </div>
                                                            </div>
                                                            <div class="lecture__content" onclick="loadLecture('<?php echo $lesson['lecture_id']; ?>');">
                                                                <p class="lectureName">
                                                                    <?php echo $lesson['lecture_order'] . '. ' . $lesson['lecture_title'];
                                                                    ?>
                                                                </p>
                                                                <div class="lecture-meta">
                                                                    <div class="lecture-meta__item d-flex align-items-center">
                                                                        <svg class="icon icon--play icon--xsmall me-1">
                                                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#icon-play">
                                                                            </use>
                                                                        </svg>
                                                                        <span>
                                                                            <?php echo CommonHelper::convertDuration($lesson['lecture_duration']); ?>
                                                                        </span>
                                                                        <?php
                                                                        if (CourseUtility::isContentUpdated($course['course_status'], $lesson['lecture_updated'])) { ?>
                                                                            <span class="card-landscape__status badge color-success badge--curve badge--small ms-1"><?php echo Label::getLabel('LBL_UPDATED'); ?></span>
                                                                        <?php }
                                                                        ?>
                                                                    </div>
                                                                    <?php
                                                                    if (isset($lesson['resources']) && count($lesson['resources']) > 0) { ?>
                                                                        <div class="lecture-meta__item d-flex align-items-center">
                                                                            <svg class="icon icon--attachment">
                                                                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#icon-attachments">
                                                                                </use>
                                                                            </svg>
                                                                            <span>
                                                                                <?php echo count($lesson['resources']); ?>
                                                                                <?php echo Label::getLabel('LBL_RESOURCES'); ?>
                                                                            </span>
                                                                        </div>
                                                                    <?php
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>
                                                        </div><?php
                                                                $i++;
                                                            }
                                                        }
                                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                            <?php } ?>
                            <?php if (!empty($quiz)) { ?>
                                <div class="toggle-control control-group-js quizListJs" onclick="openQuiz('<?php echo $course['course_quilin_id'] ?>');">
                                    <div class="toggle-control__action control-trigger-js no-after">
                                        <h6 class="lectureName quizLectureJs">
                                            <?php
                                            echo Label::getLabel('LBL_QUIZ') . ': ';
                                            echo $quiz['quilin_title'];
                                            ?>
                                        </h6>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (!empty($archivedLectures)) { ?>
                                <div class="toggle-control control-group-js sectionListJs">
                                    <div class="toggle-control__action control-trigger-js">
                                        <h6>
                                            <?php
                                            echo Label::getLabel('LBL_ARCHIVED_LECTURES') . ': ';
                                            ?>
                                        </h6>
                                    </div>
                                    <div class="toggle-control__target control-target-js">
                                        <div class="lecture-list lecturesListJs">
                                            <?php foreach ($archivedLectures as $lecture) { ?>
                                                <!-- [ LECTURE ========= -->
                                                <div class="lecture" id="lectureJs<?php echo $lecture['lecture_id']; ?>">
                                                    <div class="lecture__control is-hover">
                                                        <label class="lecture-checkbox">
                                                            <input type="checkbox" name="lecture_id" disabled>
                                                            <i class="lecture-checkbox__view"></i>
                                                        </label>
                                                    </div>
                                                    <div class="lecture__content" onclick="loadArchivedLecture('<?php echo $lecture['lecture_id']; ?>');">
                                                        <p class="lectureName">
                                                            <?php echo $lecture['section_order'] . '. ' . $lecture['lecture_order'] . '. ' . $lecture['lecture_title'] ?> </p>
                                                        <div class="lecture-meta">
                                                            <div class="lecture-meta__item d-flex align-items-center">
                                                                <svg class="icon icon--play icon--xsmall me-1">
                                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#icon-play">
                                                                    </use>
                                                                </svg>
                                                                <span>
                                                                    <?php echo CommonHelper::convertDuration($lecture['lecture_duration']); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <!-- [ LECTURE ========= -->
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </sidebar>
                    <!-- ] -->
                    <!-- [ TAB CONTENT PANEL ========= -->
                    <div class="content-area responsive-target-js tabsPanelJs">
                        <div class="lectureDetailJs" style="display: none;">
                        </div>
                        <div class="row justify-content-center notesJs" style="display: none;"></div>
                        <div class="row justify-content-center reviewsJs" style="display: none;"></div>
                        <div class="row justify-content-center tutorInfoJs" style="display: none;"></div>
                    </div>
                    <!-- ] -->
                </div>
            </div>

            <?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>
            <input type="hidden" id="progressId" name="progress_id" value="<?php echo $progressId; ?>">
            <script>
                var currentLectureId = "<?php echo $progress['crspro_lecture_id'] ?>";
                var courseId = "<?php echo $course['course_id'] ?>";
            </script>