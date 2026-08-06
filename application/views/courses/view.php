<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$levels = Course::getCourseLevels();
?>
<title><?php echo $course['course_title']; ?></title>
<!-- [ MAIN BODY ========= -->
<section class="section bg-gradiant section--page-header">
    <div class="container container--narrow">
        <div class="breadcrumbs mb-4 p-sm-0 px-2">
            <ul>
                <li><a href="<?php echo MyUtility::makeUrl(); ?>"><?php echo Label::getLabel('LBL_Home'); ?></a></li>
                <li><a href="<?php echo MyUtility::makeUrl('Courses'); ?>"><?php echo Label::getLabel('LBL_Courses'); ?></a></li>
                <li><?php echo $course['course_title']; ?></li>
            </ul>
        </div>
        <div class="details-view p-sm-0 px-2">
            <div class="details-view__media">
                <div class="course-preview">
                    <div class="course-preview__media ratio ratio--16by9">
                        <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_COURSE_IMAGE, $course['course_id'], 'LARGE', $siteLangId], CONF_WEBROOT_FRONT_URL) . '?=' . time(); ?>" alt="<?php echo $course['course_title']; ?>">
                    </div>
                    <?php if (!empty($course['course_preview_video'])) { ?>
                        <a href="javascript:void(0);" onclick="showPreviewVideo('<?php echo $course['course_id']; ?>');" class="course-preview__action">
                            <span></span>
                        </a>
                    <?php } ?>
                </div>
            </div>
            <div class="details-view__content">
                <hgroup>
                    <div class="rating mb-3">
                        <svg class="rating__media">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#rating'; ?>"></use>
                        </svg>
                        <span class="rating__value"><?php echo $course['course_ratings']; ?></span>
                        <span class="rating__count">(<?php echo $course['course_reviews'] ?>)</span>
                    </div>
                    <span class="course-card__label mb-3">
                        <a href="<?php echo MyUtility::generateUrl('Courses', 'index') . '?catg=' . $course['course_cate_id'] ?>"><?php echo $course['cate_name']; ?></a>
                        <?php
                        if (!empty($course['subcate_name'])) {
                            echo ' / ';
                        ?>
                            <a href="<?php echo MyUtility::generateUrl('Courses', 'index') . '?catg=' . $course['course_subcate_id'] ?>"><?php echo $course['subcate_name']; ?></a>
                        <?php } ?>
                    </span>
                    <h1 class="page-heading"><?php echo $course['course_title']; ?></h1>
                    <h4 class="page-subheading mt-3"><?php echo $course['course_subtitle']; ?></h4>
                </hgroup>
                <div class="course-counts pt-4">
                    <div class="course-counts__item">
                        <?php
                        $teacherProfileUrl = 'javascript:void(0);';
                        if ($isProfileComplete[$course['teacher_id']] == true) {
                            $teacherProfileUrl = MyUtility::makeUrl('teachers', 'view', [$course['teacher_username']]);
                        }
                        ?>
                        <a href="<?php echo $teacherProfileUrl; ?>" class="profile-meta d-flex align-items-center gap-3">
                            <div class="profile-meta__media">
                                <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($course['teacher_first_name']); ?>">
                                    <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $course['teacher_id'], Afile::SIZE_MEDIUM]), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo ucfirst($course['teacher_first_name']) . ' ' . ucfirst($course['teacher_last_name']) ?>">
                                </span>
                            </div>
                            <div class="profile-meta__details">
                                <span class="color-black bold-600">
                                    <?php echo ucfirst($course['teacher_first_name']) . ' ' . ucfirst($course['teacher_last_name']) ?>
                                </span>
                            </div>
                        </a>
                    </div>
                    <div class="course-counts__item">
                        <div class="course-info">
                            <div class="course-info__media">
                                <svg class="icon icon--18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-cap'; ?>"></use>
                                </svg>
                            </div>
                            <div class="course-info__title">
                                <strong><?php echo $course['course_students']; ?></strong>
                                <?php echo Label::getLabel('LBL_STUDENTS_ENROLLED'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="course-counts__item">
                        <div class="course-info">
                            <div class="course-info__media">
                                <svg class="icon icon--18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-expert'; ?>"></use>
                                </svg>
                            </div>
                            <div class="course-info__title">
                                <?php echo Course::getCourseLevels($course['course_level']); ?>
                            </div>
                        </div>
                    </div>
                    <div class="course-counts__item" title="Course language">
                        <div class="course-info">
                            <div class="course-info__media">
                                <svg class="icon icon--18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-globe'; ?>"></use>
                                </svg>
                            </div>
                            <div class="course-info__title"><?php echo $course['course_clang_name']; ?></div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
<section class="section">
    <div class="container container--narrow">
        <div class="page-flex">
            <!-- ] -->
            <!-- [ PANEL SMALL ========= -->
            <div class="page-flex__small">
                <div class="page-flex__sticky scrolling" id="STICKY">
                    <div class="page-box">
                        <div class="page-box__head">
                            <h5><?php echo Label::getLabel('LBL_THIS_COURSE_INCLUDES:'); ?></h5>
                        </div>
                        <div class="page-box__body">
                            <div class="course-options">
                                <ul>
                                    <?php if ($course['course_duration'] > 0) { ?>
                                        <li class="course-options__item">
                                            <span class="course-options__item-media">
                                                <svg class="icon icon--small">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-course-video'; ?>">
                                                    </use>
                                                </svg>
                                            </span>
                                            <span class="course-options__item-label"><?php echo CommonHelper::convertDuration($course['course_duration']); ?></span>
                                        </li>
                                    <?php } ?>
                                    <li class="course-options__item">
                                        <span class="course-options__item-media">
                                            <svg class="icon icon--small">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-course-lecture'; ?>">
                                                </use>
                                            </svg>
                                        </span>
                                        <span class="course-options__item-label">
                                            <strong><?php echo $course['course_lectures']; ?></strong>
                                            <?php echo Label::getLabel("LBL_LECTURES") ?>
                                        </span>
                                    </li>
                                    <?php if ($totalResources > 0) { ?>
                                        <li class="course-options__item">
                                            <span class="course-options__item-media">
                                                <svg class="icon icon--small">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-course-assets'; ?>">
                                                    </use>
                                                </svg>
                                            </span>
                                            <span class="course-options__item-label">
                                                <strong><?php echo $totalResources; ?></strong>
                                                <?php echo Label::getLabel("LBL_DOWNLOADABLE_ASSETS") ?>
                                            </span>
                                        </li>
                                    <?php } ?>
                                    <li class="course-options__item">
                                        <span class="course-options__item-media">
                                            <svg class="icon icon--small">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#icon-course-access">
                                                </use>
                                            </svg>
                                        </span>
                                        <span class="course-options__item-label"><?php echo Label::getLabel('LBL_FULL_LIFETIME_ACCESS'); ?></span>
                                    </li>
                                    <li class="course-options__item">
                                        <span class="course-options__item-media">
                                            <svg class="icon icon--small">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#icon-course-tv">
                                                </use>
                                            </svg>
                                        </span>
                                        <span class="course-options__item-label"><?php echo Label::getLabel('LBL__ACCESS_ON_MOBILE_AND_TV'); ?></span>
                                    </li>
                                    <?php if ($course['course_certificate'] == AppConstant::YES) { ?>
                                        <li class="course-options__item">
                                            <span class="course-options__item-media">
                                                <svg class="icon icon--small">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#icon-course-certificate">
                                                    </use>
                                                </svg>
                                            </span>
                                            <span class="course-options__item-label"><?php echo Label::getLabel('LBL_CERTIFICATE_ON_COMPLETION'); ?></span>
                                        </li>
                                    <?php } ?>
                                    <?php if ($course['course_quilin_id'] > 0) { ?>
                                        <li class="course-options__item">
                                            <span class="course-options__item-media">
                                                <svg width="16px" height="16px" xmlns="http://www.w3.org/2000/svg" version="1.1" viewBox="0 0 18.3 20.2">
                                                    <defs>
                                                        <style>
                                                            .svg-quize {
                                                                fill: none;
                                                                stroke: #000;
                                                                stroke-linecap: round;
                                                                stroke-linejoin: round;
                                                                stroke-width: 1.6px;
                                                            }
                                                        </style>
                                                    </defs>
                                                    <path class="svg-quize" d="M9.1,11.6c0-2,2-1.5,2-3.4s-3.9-2.6-3.9,0M9.1,14v-.5" />
                                                    <path class="svg-quize" strok="currentColor" stroke-width="1px" d="M17.5,13.8v-7.4c0-.7-.4-1.3-.9-1.6l-6.5-3.7c-.6-.3-1.3-.3-1.8,0L1.7,4.8c-.6.3-.9.9-.9,1.6v7.4c0,.7.4,1.3.9,1.6l6.5,3.7c.6.3,1.3.3,1.8,0l6.5-3.7c.6-.3.9-.9.9-1.6" />
                                                </svg>
                                            </span>
                                            <span class="course-options__item-label">
                                                <?php echo Label::getLabel('LBL_QUIZ_FOR_EVALUATION'); ?>
                                            </span>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </div>
                        <div class="page-box__footer">
                            <div class="course-pricing mb-3">
                                <div class="course-pricing__head text-center mb-3">
                                    <?php if ($course['course_type'] == Course::TYPE_FREE) { ?>
                                        <h3 class="free-text color-red">
                                            <?php echo Label::getLabel('LBL_FREE'); ?>
                                        </h3>
                                    <?php } ?>
                                    <?php if ($course['course_type'] != Course::TYPE_FREE) { ?>
                                        <span class="course-pricing__price">
                                            <?php echo CourseUtility::formatMoney($course['course_price']); ?>
                                        </span>
                                    <?php } ?>
                                </div>
                                <div class="course-pricing__body">
                                    <?php if (!$course['is_purchased']) { ?>
                                        <?php if ($course['course_type'] != Course::TYPE_FREE) { ?>
                                            <button onclick="cart.addCourse(<?php echo $course['course_id']; ?>)" class="btn btn--block btn--primary btn--large">
                                                <?php echo Label::getLabel("LBL_ENROLL_NOW"); ?>
                                            </button>
                                        <?php } else { ?>
                                            <button onclick="cart.addFreeCourse(<?php echo $course['course_id']; ?>)" class="btn btn--block btn--primary btn--large">
                                                <?php echo Label::getLabel("LBL_ENROLL_NOW"); ?>
                                            </button>
                                            <?php
                                            $checkoutForm->setFormTagAttribute('class', 'd-none');
                                            $checkoutForm->setFormTagAttribute('name', 'frmCheckout');
                                            $checkoutForm->setFormTagAttribute('id', 'frmCheckout');
                                            echo $checkoutForm->getFormHtml();
                                            ?>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <a href="<?php echo MyUtility::makeUrl('Tutorials', 'start', [$course['ordcrs_id']], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--block btn--primary btn--large">
                                            <?php echo Label::getLabel("LBL_GO_TO_COURSE"); ?>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                            <button onclick="toggleCourseFavorite('<?php echo $course['course_id'] ?>', this)" class="btn btn--primary-bordered btn--favorite btn--block <?php echo ($course['is_favorite'] == AppConstant::YES) ? 'is-active' : ''; ?>" data-status="<?php echo $course['is_favorite']; ?>" tabindex="0">
                                <svg class="icon icon--heart fav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 25.32 25.32">
                                    <g>
                                        <path class="cls-1" d="M17.16,3.41c3.04,0,5.5,2.5,5.5,6,0,7-7.5,11-10,12.5-2.5-1.5-10-5.5-10-12.5,0-3.5,2.5-6,5.5-6,1.86,0,3.5,1,4.5,2,1-1,2.64-2,4.5-2Z"></path>
                                    </g>
                                </svg>

                                <?php echo Label::getLabel("LBL_FAVORITE"); ?>
                            </button>
                            <div class="sharing-view align-center mt-4 p-0 m-0">
                                <h6><?php echo Label::getLabel('LBL_SHARE_THIS_COURSE'); ?></h6>
                                <ul class="social--share pt-3">
                                    <li class="social--fb">
                                        <a class="st-custom-button" data-network="facebook" displayText='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>' title='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>' st_processed="yes">
                                            <img alt="<?php echo Label::getLabel('LBL_FACEBOOK'); ?>" src="<?php echo CONF_WEBROOT_URL; ?>images/social_01.svg">
                                        </a>
                                    </li>
                                    <li class="social--tw">
                                        <a class="st-custom-button" data-network="twitter" displayText='<?php echo Label::getLabel('LBL_X'); ?>' title='<?php echo Label::getLabel('LBL_X'); ?>' st_processed="yes">
                                            <img alt="<?php echo Label::getLabel('LBL_X'); ?>" src="<?php echo CONF_WEBROOT_URL; ?>images/social_02.svg">
                                        </a>
                                    </li>
                                    <li class="social--pt">
                                        <a class="st-custom-button" data-network="pinterest" displayText='<?php echo Label::getLabel('LBL_PINTEREST'); ?>' title='<?php echo Label::getLabel('LBL_PINTEREST'); ?>' st_processed="yes">
                                            <img alt="<?php echo Label::getLabel('LBL_PINTEREST'); ?>" src="<?php echo CONF_WEBROOT_URL; ?>images/social_05.svg">
                                        </a>
                                    </li>
                                    <li class="social--mail">
                                        <a class="st-custom-button" data-network="email" displayText='<?php echo Label::getLabel('LBL_EMAIL'); ?>' title='<?php echo Label::getLabel('LBL_EMAIL'); ?>' st_processed="yes">
                                            <img alt="<?php echo Label::getLabel('LBL_EMAIL'); ?>" src="<?php echo CONF_WEBROOT_URL; ?>images/social_06.svg">
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ] -->
            <!-- [ PANEL LARGE 2 ========= -->
            <div class="page-flex__large">
                <nav class="page-nav tabs page-nav-js" id="TAB-STICKY">
                    <ul>
                        <li class="is-active" data-id="panel-content-1">
                            <a href="#panel-content-1"><?php echo Label::getLabel('LBL_OVERVIEW'); ?></a>
                        </li>
                        <li data-id="panel-content-2">
                            <a href="#panel-content-2"><?php echo Label::getLabel('LBL_COURSE_CONTENT'); ?></a>
                        </li>
                        <li data-id="panel-content-3">
                            <a href="#panel-content-3"><?php echo Label::getLabel('LBL_ABOUT_TUTOR'); ?></a>
                        </li>
                        <?php if ($course['course_reviews'] > 0) { ?>
                            <li data-id="panel-content-4">
                                <a href="#panel-content-4"><?php echo Label::getLabel('LBL_REVIEWS'); ?> (<?php echo $course['course_reviews'] ?>)</a>
                            </li>
                        <?php } ?>
                    </ul>
                </nav>
                <div class="panels-container panels-container-js">
                    <!-- [ COURSE OVERVIEW ========= -->
                    <div data-id="panel-content-1" class="panel-content panel-content-js">
                        <div class="panel-content__head d-sm-none d-block panel-trigger-js">
                            <h3><?php echo Label::getLabel('LBL_OVERVIEW'); ?></h3>
                        </div>
                        <div class="panel-content__body panel-target-js">
                            <?php $types = IntendedLearner::getTypes(); ?>
                            <?php if (isset($intendedLearners[IntendedLEarner::TYPE_LEARNING])) { ?>
                                <div class="content-group">
                                    <h5 class="mb-4"> <?php echo $types[IntendedLEarner::TYPE_LEARNING]; ?></h5>
                                    <div class="border p-4 rounded-4">
                                        <div class="check-list check-list--half">
                                            <ul id="more-check-list-1" class="check-listing">
                                                <?php foreach ($intendedLearners[IntendedLEarner::TYPE_LEARNING] as $learner) { ?>
                                                    <li>
                                                        <?php echo $learner['coinle_response'] ?>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                            <?php if (count($intendedLearners[IntendedLEarner::TYPE_LEARNING]) > 4) { ?>
                                                <button href="#more-check-list-1" onclick="toggleShowMore(this);" class="link-expand"><?php echo Label::getLabel('LBL_Show_More'); ?></button>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (isset($intendedLearners[IntendedLEarner::TYPE_REQUIREMENTS])) { ?>
                                <div class="content-group">
                                    <h5 class="mb-4"> <?php echo $types[IntendedLEarner::TYPE_REQUIREMENTS]; ?></h5>
                                    <div class="border p-4 rounded-4">
                                        <div class="check-list check-list--half">
                                            <ul id="more-check-list-2" class="check-listing">
                                                <?php foreach ($intendedLearners[IntendedLEarner::TYPE_REQUIREMENTS] as $learner) { ?>
                                                    <li>
                                                        <?php echo $learner['coinle_response'] ?>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                            <?php if (count($intendedLearners[IntendedLEarner::TYPE_REQUIREMENTS]) > 4) { ?>
                                                <button href="#more-check-list-2" onclick="toggleShowMore(this);" class="link-expand"><?php echo Label::getLabel('LBL_Show_More'); ?></button>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <?php if (isset($intendedLearners[IntendedLEarner::TYPE_LEARNERS])) { ?>
                                <div class="content-group">
                                    <h5 class="mb-4"> <?php echo $types[IntendedLEarner::TYPE_LEARNERS]; ?></h5>
                                    <div class="border p-4 rounded-4">
                                        <div class="check-list check-list--half">
                                            <ul id="more-check-list-3" class="check-listing">
                                                <?php foreach ($intendedLearners[IntendedLEarner::TYPE_LEARNERS] as $learner) { ?>
                                                    <li>
                                                        <?php echo $learner['coinle_response'] ?>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                            <?php if (count($intendedLearners[IntendedLEarner::TYPE_LEARNERS]) > 4) { ?>
                                                <button href="#more-check-list-3" onclick="toggleShowMore(this);" class="link-expand"><?php echo Label::getLabel('LBL_Show_More'); ?></button>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                            <div class="content-group">
                                <h5 class="mb-4"><?php echo Label::getLabel('LBL_DESCRIPTION'); ?></h5>
                                <div class="check-list check-list--half editor-content iframe-content" style="height: 560px;">
                                    <iframe onload="resetDeviceIframe(this);" src="<?php echo MyUtility::makeUrl('Courses', 'frame', [$course['course_id']]); ?>" style="border: none; width: 100%; height: 0px;"></iframe>
                                </div>
                            </div>
                            <?php if (count($course['course_tags']) > 0) { ?>
                                <div class="content-group">
                                    <h5 class="mb-4">
                                        <?php echo Label::getLabel('LBL_COURSE_TAGS'); ?>
                                    </h5>
                                    <div class="tags">
                                        <?php foreach ($course['course_tags'] as $tag) { ?>
                                            <button class="tags__item badge badge--curve"><?php echo $tag; ?></button>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <!-- ] -->
                    <!-- [ COURSE CONTENT ========= -->
                    <div data-id="panel-content-2" class="panel-content panel-content-js">
                        <div class="panel-content__head  panel-trigger-js">
                            <h3><?php echo Label::getLabel('LBL_COURSE_CONTENT'); ?></h3>
                        </div>
                        <div class="panel-content__body  panel-target-js" id="accordionParent">
                            <div class="inline-list mt-4 mt-sm-2">
                                <ul>
                                    <li>
                                        <?php echo $course['course_sections'] . ' ' . Label::getLabel('LBL_SECTIONS'); ?>
                                    </li>
                                    <li>
                                        <?php echo $course['course_lectures']; ?>
                                        <?php echo Label::getLabel("LBL_LECTURES") ?>
                                    </li>
                                    <?php if ($course['course_duration'] > 0) { ?>
                                        <li>
                                            <?php
                                            echo CommonHelper::convertDuration($course['course_duration']) . ' ' . Label::getLabel("LBL_TOTAL_LENGTH");
                                            ?>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>
                            <?php
                            if (count($sections) > 0) {
                                $i = 1;
                                foreach ($sections as $section) {
                                    $lectures = ($section['lectures']) ?? [];
                                    if (count($lectures) > 0) {
                            ?>
                                        <div class="course-layout">
                                            <div class="course-layout__head <?php echo $i != 1 ? 'collapsed' : ''; ?>" data-bs-toggle="collapse" data-bs-target="#course-<?php echo $i; ?>" aria-expanded="<?php echo $i == 1 ? 'true' : 'false'; ?>">
                                                <div class="course-content">
                                                    <h5> <?php echo $section['section_title'] ?> </h5>
                                                    <div class="course-counts">
                                                        <div class="course-counts__item">
                                                            <?php echo CommonHelper::convertDuration($section['section_duration']); ?>
                                                        </div>
                                                        <div class="course-counts__item">
                                                            <?php echo $section['section_lectures'] . ' ' . Label::getLabel("LBL_LECTURES"); ?>
                                                        </div>
                                                    </div>
                                                    <p><?php echo nl2br($section['section_details']); ?></p>
                                                </div>
                                            </div>
                                            <div class="course-layout__body collapse <?php echo $i == 1 ? 'show' : ''; ?>" id="course-<?php echo $i; ?>" data-bs-parent="#accordionParent">
                                                <div class="course-layout-inner">
                                                    <div class="course-topic-list">
                                                        <?php
                                                        foreach ($section['lectures'] as $lesson) {
                                                            $showPreview = false;

                                                            $courseId = $lesson['lecture_course_id'];
                                                            $rsrcId = array_search($lesson['lecture_id'], $videos);
                                                            if ($rsrcId && $lesson['lecture_is_trial']) {
                                                                $showPreview = true;
                                                            }
                                                        ?>
                                                            <div class="course-topic">
                                                                <?php if ($showPreview) { ?>
                                                                    <a href="javascript:void(0);" onclick="openMedia('<?php echo $rsrcId; ?>');" class="course-topic__action">
                                                                    <?php } ?>
                                                                    <div class="course-topic__title">
                                                                        <svg class="icon icon--play icon--18">
                                                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#icon-play">
                                                                            </use>
                                                                        </svg>
                                                                        <span class="course-topic__name">
                                                                            <?php echo $lesson['lecture_title'] ?>
                                                                        </span>
                                                                    </div>
                                                                    <div class="course-topic__content">
                                                                        <?php if ($showPreview) { ?>
                                                                            <span class="course-topic__preview">
                                                                                <?php echo Label::getLabel('LBL_PREVIEW'); ?>
                                                                            </span>
                                                                        <?php } ?>
                                                                        <span class="course-topic__time">
                                                                            <?php echo $duration = CommonHelper::convertDuration($lesson['lecture_duration'], true, false); ?>
                                                                        </span>
                                                                    </div>
                                                                    <?php if ($showPreview) { ?>
                                                                    </a>
                                                                <?php } ?>
                                                            </div>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                            <?php
                                    }
                                    $i++;
                                }
                            }
                            ?>
                        </div>
                    </div>
                    <!-- ] -->
                    <!-- [ COURSE TUTOR ========= -->
                    <div data-id="panel-content-3" class="panel-content panel-content-js">
                        <div class="panel-content__head  panel-trigger-js">
                            <h3><?php echo Label::getLabel('LBL_ABOUT_TUTOR'); ?></h3>
                        </div>
                        <div class="panel-content__body  panel-target-js">
                            <div class="author-box mt-3">
                                <div class="author-box__media">
                                    <div class="media ratio ratio--3by4" data-title="<?php echo CommonHelper::getFirstChar($course['teacher_first_name']); ?>">
                                        <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $course['teacher_id'], Afile::SIZE_MEDIUM]), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo ucfirst($course['teacher_first_name']) . ' ' . ucfirst($course['teacher_last_name']) ?>">
                                    </div>
                                    <div class="rating mt-3 d-inline-flex">
                                        <svg class="rating__media">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#rating">
                                            </use>
                                        </svg>
                                        <span class="rating__value"><?php echo $course['testat_ratings'] ?> </span>
                                        <span class="rating__count">(<?php echo $course['testat_reviewes'] ?>)</span>
                                    </div>
                                </div>
                                <div class="author-box__content">
                                    <h4 class="author-name mb-2">
                                        <a href="<?php echo $teacherProfileUrl; ?>">
                                            <?php echo ucfirst($course['teacher_first_name']) . ' ' . ucfirst($course['teacher_last_name']) ?>
                                        </a>
                                    </h4>
                                    <div class="course-counts mb-2">
                                        <div class="course-counts__item">
                                            <div class="course-info">
                                                <div class="course-info__media">
                                                    <svg class="icon icon--level">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#icon-lecture">
                                                        </use>
                                                    </svg>
                                                </div>
                                                <div class="course-info__title">
                                                    <?php echo Label::getLabel('LBL_COURSES'); ?> <strong><?php echo $course['teacher_courses'] ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php if (!empty($course['user_biography'])) { ?>
                                        <div class="author-bio mb-3">
                                            <p><?php echo nl2br($course['user_biography']) ?></p>
                                        </div>
                                    <?php } ?>
                                    <?php if ($isProfileComplete[$course['teacher_id']] == true) { ?>
                                        <a href="<?php echo $teacherProfileUrl; ?>" class="btn btn--primary-bordered">
                                            <?php echo Label::getLabel('LBL_VIEW_PROFILE'); ?>
                                        </a>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- ] -->
                    <?php if ($course['course_reviews'] > 0) { ?>
                        <div data-id="panel-content-4" class="panel-content panel-content-js">
                            <div class="panel-content__head  panel-trigger-js">
                                <h3><?php echo Label::getLabel('LBL_RATINGS_&_REVIEWS'); ?></h3>
                            </div>
                            <div class="panel-content__body  panel-target-js">
                                <div class="reviews-section mt-5">
                                    <div class="reviews-section__head">
                                        <div class="rating-details mb-4">
                                            <div class="rating-card">
                                                <div class="rating-card__counter">
                                                    <div class="rating__count">
                                                        <h1><?php echo $course['course_ratings'] ?></h1>
                                                        <svg class="icon icon--30 icon--rating">
                                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#rating"></use>
                                                        </svg>
                                                    </div>
                                                    <div class="rating__info">
                                                        <b><?php echo Label::getLabel('LBL_Overall_Ratings'); ?></b>
                                                    </div>
                                                </div>
                                                <div class="rating-card__progressbar">
                                                    <div class="progressbar-wrapper">
                                                        <ul class="listing">
                                                            <?php foreach ($reviews as $review) { ?>
                                                                <li class="rating">
                                                                    <span class="rating__stars"> <?php echo $review['rating'] ?>
                                                                        <svg class="icon icon--xsmall icon--rating">
                                                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#rating"></use>
                                                                        </svg>
                                                                    </span>
                                                                    <div class="rating__progressbar">
                                                                        <?php if ($review['percent'] > 0) { ?>
                                                                            <div style="width: <?php echo $review['percent'] ?>%;" class="fill"></div>
                                                                        <?php } ?>
                                                                    </div>
                                                                    <span class="rating__percentage"><?php echo $review['percent'] ?>%</span>
                                                                </li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-3 col-md-4 col-xl-3">
                                        <?php if ($canRate) { ?>
                                            <div class="reviews-submission">
                                                <p class="mb-3 mt-3 align-center">
                                                    <?php echo Label::getLabel('LBL_HAVE_YOU_USED_THIS_COURSE?') ?>
                                                </p>
                                                <button onclick="feedbackForm('<?php echo $course['ordcrs_id']; ?>')" class="btn color-primary btn--bordered btn--block">
                                                    <?php echo Label::getLabel('LBL_RATE_IT_NOW') ?>
                                                </button>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <?php echo $frm->getFormHtml(); ?>
                                    <div class="reviews-wrapper">
                                        <div class="reviews-wrapper__head mb-4">
                                            <div class="reviews-counter" id="recordToDisplay"></div>
                                            <div class="review-sorting">
                                                <select name="sorting" onchange="loadReviews('<?php echo $course['course_id']; ?>', 1)">
                                                    <?php $sortArr = RatingReview::getSortTypes(); ?>
                                                    <?php foreach ($sortArr as $key => $value) { ?>
                                                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="reviews-list reviews-wrapper__body" id="reviewsListingJs"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <!-- ] -->
        </div>
    </div>
</section>
<?php if ($moreCourses) { ?>
    <section class="section">
        <div class="container container--narrow">
            <div class="section__head d-flex justify-content-between align-items-center">
                <h3>
                    <?php
                    $label = Label::getLabel('LBL_MORE_COURSES_FROM_{teacher-name}');
                    echo str_replace('{teacher-name}', '<strong class="bold-700">' . ucfirst($course['teacher_first_name']) . '</strong>', $label);
                    ?>
                </h3>
            </div>
            <div class="section__body">
                <?php
                echo $this->includeTemplate('courses/more-courses.php', [
                    'moreCourses' => $moreCourses,
                    'siteLangId' => $siteLangId,
                    'siteUserId' => $siteUserId,
                ]);
                ?>
            </div>
        </div>
    </section>
<?php } ?>
<?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>
<script>
    function updateStickyPosition() {
        if ($(window).width() >= 576) {
            var headerHeight = $(".header").outerHeight();
            $("#TAB-STICKY").css("top", `${headerHeight}px`);
        } else {
            $("#TAB-STICKY").css("top", "");
        }
    }
</script>