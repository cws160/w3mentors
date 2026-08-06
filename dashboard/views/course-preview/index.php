<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$websiteName = FatApp::getConfig('CONF_WEBSITE_NAME_' . $siteLangId, FatUtility::VAR_STRING, '');
?>
<!-- [ HEADER ========= -->
<header class="header">
    <div class="header-primary d-sm-flex justify-content-sm-between align-items-sm-center">
        <div class="header-primary__right order-sm-2">
            <div class="d-flex justify-content-between align-items-center">
                <!-- [ COURSE PROGRESS - NOT COMPLETED ========= -->
                <div class="course-progress in-progress">
                    <a href="#course-progress" class="course-progress__trigger d-flex align-items-center trigger-js">
                        <div class="course-progress__count me-1">
                            <div class="percent">
                                <svg class="percent__progress" viewBox="0 0 300 300">
                                    <circle cx="150" cy="150" r="100"></circle>
                                    <circle cx="150" cy="150" r="100" style="--percent: 0" id="progressBarJs"></circle>
                                </svg>
                                <svg class="icon icon--trophy percent__media">
                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#trophy">
                                    </use>
                                </svg>
                            </div>
                        </div>
                        <div class="course-progress__content">
                            <h6><?php echo $label = Label::getLabel('LBL_COURSE_PROGRESS'); ?></h6>
                            <small class="progressPercent">
                                <?php
                                $progressLbl = Label::getLabel('LBL_{percent}%_COMPLETED');
                                $progressLbl = str_replace('{percent}', 0, $progressLbl);
                                echo $progressLbl;
                                ?>
                            </small>
                        </div>
                    </a>
                    <div id="course-progress" class="course-progress__target">
                        <div class="course-progress__content align-center d-block">
                            <h6 class="m-0"><?php echo $label; ?></h6>
                            <small class="progressPercent"><?php echo $progressLbl; ?></small>
                        </div>
                    </div>
                </div>
                <!-- ] -->
                <!-- [ USER ACCOUNT ========= -->
                <div class="account">
                    <a href="#accout-target" class="avtar avtar--small avtar--round account__trigger trigger-js" data-title="S">
                        <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $siteUserId, Afile::SIZE_SMALL], CONF_WEBROOT_FRONTEND) . '?' . time() ?>" alt="">
                    </a>
                    <div id="accout-target" class="account__target">
                        <nav class="menu-vertical">
                            <ul>
                                <li class="menu__item <?php echo ("Account" == $controllerName && "profileInfo" == $action) ? 'is-active' : ''; ?>">
                                    <a href="<?php echo MyUtility::makeUrl('Account', 'ProfileInfo', [], CONF_WEBROOT_DASHBOARD); ?>">
                                        <svg class="icon">
                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#settings"></use>
                                        </svg>
                                        <?php echo Label::getLabel('LBL_Settings'); ?>
                                    </a>
                                </li>
                                <li class="menu__item border-top mt-3">
                                    <a href="<?php echo MyUtility::makeUrl('Account', 'logout', [], CONF_WEBROOT_DASHBOARD); ?>">
                                        <svg class="icon" width="24" height="24" viewBox="0 0 24 24">
                                            <path d="M7.68421 19C7.30633 19 7 18.6866 7 18.3V5.7C7 5.3134 7.30633 5 7.68421 5H17.2632C17.641 5 17.9474 5.3134 17.9474 5.7V7.8H16.5789V6.4H8.36842V17.6H16.5789V16.2H17.9474V18.3C17.9474 18.6866 17.641 19 17.2632 19H7.68421ZM16.5789 14.8V12.7H11.7895V11.3H16.5789V9.2L20 12L16.5789 14.8Z" fill="black" />
                                        </svg>
                                        <?php echo Label::getLabel('LBL_Logout'); ?>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                </div>
                <!-- ] -->
            </div>
        </div>
        <div class="header-primary__left order-sm-1">
            <div class="d-sm-flex justify-content-sm-between align-items-sm-center">
                <figure class="header-logo">
                    <a href="<?php echo MyUtility::makeUrl('', '', [], CONF_WEBROOT_FRONT_URL); ?>">
                        <?php if (MyUtility::isDemoUrl()) { ?>
                            <img src="<?php echo CONF_WEBROOT_FRONTEND . 'images/logo.svg'; ?>" alt="" />
                        <?php } else { ?>
                            <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeFullUrl('Image', 'show', array(Afile::TYPE_FRONT_LOGO, 0, Afile::SIZE_LARGE), CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $websiteName; ?>">
                        <?php } ?>
                    </a>
                </figure>
                <h1 class="page-title"><a href="javascript:void(0);"><?php echo $course['course_title']; ?></a>
                </h1>
            </div>
        </div>
    </div>
</header>
<!-- ] -->
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
                                <?php if ($siteUserType == User::LEARNER) { ?>
                                    <a href="<?php echo MyUtility::makeUrl('Learner') ?>">
                                        <?php echo Label::getLabel('LBL_DASHBOARD') ?>
                                    </a>
                                <?php } else { ?>
                                    <a href="<?php echo MyUtility::makeUrl('Teacher') ?>">
                                        <?php echo Label::getLabel('LBL_DASHBOARD') ?>
                                    </a>
                                <?php } ?>
                            </li>
                            <li>
                                <a href="<?php echo MyUtility::makeUrl('Courses') ?>">
                                    <?php echo Label::getLabel('LBL_MY_COURSES'); ?>
                                </a>
                            </li>
                            <li><?php echo $course['course_title']; ?></li>
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
                                        <a href="javascript:void(0);" onclick="getNotes();">
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
                                                    0
                                                </span>
                                                <?php
                                                echo ' / ' . $section['section_lectures'] . ' | ' . CommonHelper::convertDuration($section['section_duration']);

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
                                                <?php if (isset($section['lectures']) && count($section['lectures']) > 0) {
                                                    foreach ($section['lectures'] as $lesson) { ?>
                                                        <div class="lecture" id="lectureJs<?php echo $lesson['lecture_id']; ?>">
                                                            <div class="lecture__control is-hover">
                                                                <label class="lecture-checkbox">
                                                                    <input type="checkbox" name="lecture_id" data-section="<?php echo $section['section_id']; ?>" value="<?php echo $lesson['lecture_id']; ?>">
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
                                    </div><?php
                                        }
                                    } ?>
                            <?php if (!empty($quiz)) { ?>
                                <div class="toggle-control control-group-js quizListJs" onclick="openQuiz('<?php echo $quiz['quiz_id'] ?>');">
                                    <div class="toggle-control__action control-trigger-js no-after">
                                        <h6 class="lectureName quizLectureJs">
                                            <?php
                                            echo Label::getLabel('LBL_QUIZ') . ': ';
                                            echo $quiz['quiz_title'];
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
            <script>
                var currentLectureId = "0";
                var courseId = "<?php echo $course['course_id'] ?>";
            </script>