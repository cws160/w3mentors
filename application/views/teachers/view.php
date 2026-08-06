<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$bookingDuration = '';
$disabledClass = '';
$bookNowOnClickClick = 'onclick="cart.langSlots(' . $teacher['user_id'] . ');"';
$contactClick = 'onclick="threadForm(' . $teacher['user_id'] . ',' . Thread::PRIVATE . ');"';
if ($siteUserId == $teacher['user_id']) {
    $disabledClass = 'disabled';
    $bookNowOnClickClick = '';
    $contactClick = '';
}
$totalSessions = $teacher['testat_lessons'];
if (GroupClass::isEnabled()) {
    $totalSessions =  $totalSessions + $teacher['testat_classes'];
} else {
    $classes = [];
}
$preSelectedSlot = current($teacher['user_slots']);
?>
<section class="section section--profile">
    <div class="container container--fixed">
        <div class="profile-cover">
            <div class="profile-head">
                <div class="detail-wrapper">
                    <div class="profile__media">
                        <div class="ratio ratio--3by4" data-title="<?php echo CommonHelper::getFirstChar($teacher['user_first_name']); ?>">
                            <?php
                            $img = FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['user_id'], Afile::SIZE_MEDIUM]), CONF_DEF_CACHE_TIME);
                            echo '<span><img src="' . $img . '" alt="' . $teacher['user_first_name'] . ' ' . $teacher['user_last_name'] . '" /></span>';
                            ?>
                        </div>
                        <?php if (!empty($teacher['user_online']['show'])) { ?>
                            <div class="avtar-elements">
                                <div class="me-auto">
                                    <span class="status status--<?php echo $teacher['user_online']['class']; ?> is-hover">
                                        <span class="status__badge"></span>
                                        <div class="tooltip tooltip--top tooltip--round bg-black no-wrap"><?php echo $teacher['user_online']['tooltip']; ?></div>
                                    </span>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="profile-detail">
                        <div class="profile-detail__head">
                            <div class="profile-detail__head-large">
                                <div class="tutor-name">
                                    <h1><?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name']; ?></h1>

                                    <?php if (!empty($teacher['user_featured'])) { ?>
                                        <div class="badge-secure is-hover">
                                            <svg class="icon icon--small icon--featured" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="12" height="12">
                                                <path fill-rule="evenodd" d="M15.291 4.055 12 2 8.709 4.055l-3.78.874-.874 3.78L2 12l2.055 3.291.874 3.78 3.78.874L12 22l3.291-2.055 3.78-.874.874-3.78L22 12l-2.055-3.291-.874-3.78zM9.793 15.707l.707.707.707-.707 6-6-1.414-1.414-5.293 5.293-2.293-2.293-1.414 1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                            <div class="tooltip tooltip--top tooltip--round bg-black no-wrap"><?php echo Label::getLabel('LBL_FEATURED'); ?></div>
                                        </div>
                                    <?php } ?>

                                    <div class="ratings">
                                        <svg class="icon icon--rating">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#rating'; ?>"></use>
                                        </svg>
                                        <span class="value"><?php echo $teacher['testat_ratings']; ?></span>
                                        <span class="count">(<?php echo $teacher['testat_reviewes']; ?>)</span>
                                    </div>
                                </div>
                                <?php if (!empty($teacher['offers'])) { ?>
                                    <?php $this->includeTemplate('_partial/offers.php', ['offers' => $teacher['offers']], false); ?>
                                <?php } ?>
                                <div class="info-wrapper mt-3">
                                    <div class="meta-info">
                                        <svg class="icon icon--xsmall">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#location'; ?>"></use>
                                        </svg>
                                        <span><?php echo $teacher['user_country_name']; ?></span>
                                    </div>
                                    <div class="meta-info">
                                        <span class="value"><?php echo $teacher['testat_students']; ?></span>
                                        <span><?php echo ' ' . Label::getLabel('LBL_Students') ?></span>
                                    </div>
                                    <div class="meta-info">
                                        <span class="value"><?php echo $totalSessions; ?></span>
                                        <span><?php echo ' ' . Label::getLabel('LBL_SESSIONS'); ?></span>
                                    </div>
                                    <?php if(Course::isEnabled()) { ?>
                                        <div class="meta-info">
                                            <span class="value"><?php echo $teacher['courses']; ?></span>
                                            <span><?php echo ' ' . Label::getLabel('LBL_COURSES'); ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="profile-detail__head-small">
                                <div class="detail-actions">
                                    <?php
                                    $disabledText = 'disabled';
                                    $onclick = "";
                                    if ($siteUserId != $teacher['user_id']) {
                                        $disabledText = '';
                                        $onclick = 'onclick="toggleTeacherFavorite(' . $teacher["user_id"] . ', this)"';
                                    }
                                    ?>
                                    <button <?php echo $onclick; ?> class="btn btn--bordered color-black <?php echo $disabledText; ?> <?php echo ($teacher['uft_id']) ? 'is--active' : ''; ?>" <?php echo $disabledText; ?>>
                                        <svg class="icon icon--heart icon--xsmall">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#heart'; ?>"></use>
                                        </svg>
                                    </button>
                                    <div class="social-share dropdown">
                                        <button class="btn btn-equal dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                            <svg class="icon icon--share icon--xsmall">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#share'; ?>"></use>
                                            </svg>
                                        </button>
                                        <div class="dropdown-menu mt-1">
                                            <h6><?php echo Label::getLabel('LBL_SHARE_ON'); ?></h6>
                                            <ul class="social--share mt-3">
                                                <li class="social--fb"><a class='st-custom-button' data-network="facebook" displayText='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>' title='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_01.svg" alt="<?php echo Label::getLabel('LBL_FACEBOOK'); ?>"></a></li>
                                                <li class="social--tw"><a class='st-custom-button' data-network="twitter" displayText='<?php echo Label::getLabel('LBL_X'); ?>' title='<?php echo Label::getLabel('LBL_X'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_02.svg" alt="<?php echo Label::getLabel('LBL_X'); ?>"></a></li>
                                                <li class="social--pt"><a class='st-custom-button' data-network="pinterest" displayText='<?php echo Label::getLabel('LBL_PINTEREST'); ?>' title='<?php echo Label::getLabel('LBL_PINTEREST'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_05.svg" alt="<?php echo Label::getLabel('LBL_PINTEREST'); ?>"></a></li>
                                                <li class="social--mail"><a class='st-custom-button' data-network="email" displayText='<?php echo Label::getLabel('LBL_EMAIL'); ?>' title='<?php echo Label::getLabel('LBL_EMAIL'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_06.svg" alt="<?php echo Label::getLabel('LBL_EMAIL'); ?>"></a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="profile-detail__body">
                            <div class="har-rate mb-3"><?php echo Label::getLabel('LBL_TEACHER_PRICING'); ?><b> <?php echo MyUtility::formatMoney($teacher['testat_minprice']); ?> - <?php echo MyUtility::formatMoney($teacher['testat_maxprice']); ?></b></div>
                            <div class="tutor-info">
                                <div class="info-group">
                                    <div class="info-group__head">
                                        <h5><?php echo Label::getLabel('LBL_TEACHES:'); ?></h5>
                                    </div>
                                    <div class="info-group__body">
                                        <?php echo $teacher['teacherTeachLanguageName']; ?>
                                    </div>
                                </div>
                                <div class="info-group">
                                    <div class="info-group__head">
                                        <h5><?php echo Label::getLabel('LBL_Speaks:'); ?></h5>
                                    </div>
                                    <div class="info-group__body">
                                        <?php $this->includeTemplate('teachers/_partial/SpeakLanguages.php', $teacher, false); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="profile-primary">
                <?php if (!empty($teacher['user_biography'])) { ?>
                    <div class="panel-cover">
                        <div class="panel-cover__head panel__head-trigger panel__head-trigger-js is-active">
                            <h3><?php echo Label::getLabel('LBL_About'); ?> <?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name']; ?></h3>
                        </div>
                        <div class="panel-cover__body panel__body-target panel__body-target-js" style="display:block;">
                            <div class="content__row">
                                <p><?php echo nl2br($teacher['user_biography']); ?></p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="panel-cover ">
                    <div class="panel-cover__head panel__head-trigger panel__head-trigger-js">
                        <h3><?php echo Label::getLabel('LBL_Pricing') ?></h3>
                    </div>
                    <div class="panel-cover__body panel__body-target panel__body-target-js">
                        <div class="table-md-scroll">
                            <div class="table--pricing">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th><?php echo Label::getLabel('LBL_TEACHING_LANGUAGES'); ?></th>
                                            <th>
                                                <select onchange="changeSlot(this);" name="selected_slot" id="selected_slot">
                                                    <?php foreach ($teacher['user_slots'] as $slot) { ?>
                                                        <option value="<?php echo $slot; ?>"><?php echo $slot; ?></option>
                                                    <?php } ?>
                                                </select>
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($userLangData as $row) { ?>
                                            <tr>
                                                <td> <?php echo $row['tlang_name']; ?></td>
                                                <td>
                                                    <?php foreach ($teacher['user_slots'] as $slot) { ?>
                                                        <span class="cursor-pointer trigger-checkout slot-change-<?php echo $slot; ?>" style="<?php echo ($preSelectedSlot == $slot) ? 'display: block;' : 'display: none;'; ?>" onclick="cart.bookLangSlot('<?php echo $teacher['user_id']; ?>', <?php echo $row['tlang_id'] ?>, <?php echo $slot ?>, 1, cart.prop.ordles_type, cart.prop.ordles_offline)"><?php echo MyUtility::formatMoney(MyUtility::slotPrice($row['utlang_price'] ?? 0, $slot)); ?></span>
                                                    <?php } ?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
                <?php if (count($classes) > 0) { ?>

                    <div class="panel-cover">
                        <div class="panel-cover__head panel__head-trigger panel__head-trigger-js">
                            <h3><?php echo Label::getLabel('LBL_GROUP_CLASSES'); ?></h3>
                        </div>
                        <div class="panel-cover__body mt-xl-0 panel__body-target panel__body-target-js">
                            <div class="slider slider--onehalf slider-onehalf-js slick-slider">
                                <?php
                                foreach ($classes as $class) {
                                    $classData = ['class' => $class, 'siteUserId' => $siteUserId, 'bookingBefore' => $bookingBefore, 'cardClass' => 'card-cover']; ?>
                                    <div class="slider__item">
                                        <?php $this->includeTemplate('group-classes/card.php', $classData, false); ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                <?php } ?>
                <div class="panel-cover">
                    <div class="panel-cover__head panel__head-trigger panel__head-trigger-js">
                        <h3><?php echo Label::getLabel('LBL_TEACHING_EXPERTISE'); ?></h3>
                    </div>
                    <div class="panel-accordion panel-cover__body panel__body-target panel__body-target-js">
                        <div class="accordion" id="accordion-wrapper">
                            <?php
                            $collapse = 1;
                            foreach ($preferencesType as $type => $preference) {
                                if (empty($userPreferences[$type])) {
                                    continue;
                                }
                            ?>
                                <div class="accordion-item">
                                    <div class="accordion-header accordion-button <?php echo $collapse == 1 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $collapse; ?>" aria-expanded="false">
                                        <?php echo $preference; ?>
                                    </div>
                                    <div id="collapse<?php echo $collapse; ?>" class="accordion-collapse collapse <?php echo $collapse == 1 ? 'show' : ''; ?>" data-bs-parent="#accordion-wrapper">
                                        <div class="accordion-body">
                                            <div class="tick-listing tick-listing--onethird content--tick">
                                                <ul>
                                                    <?php foreach ($userPreferences[$type] as $preference) { ?>
                                                        <li><?php echo $preference['prefer_title']; ?></li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php $collapse = $collapse + 1;
                            } ?>
                        </div>
                    </div>
                </div>
                <div class="panel-cover">
                    <div class="panel-cover__head panel__head-trigger panel__head-trigger-js">
                        <h3><?php echo Label::getLabel('LBL_TEACHING_QUALIFICATIONS'); ?></h3>
                    </div>
                    <div class="panel-cover__body panel__body-target panel__body-target-js" id="qualificationsList">
                        <div class="box-panel">
                            <div class="box-panel__head d-none d-md-block">
                                <div class="tabs js--tabs">
                                    <ul>
                                        <?php
                                        $i = 1;
                                        foreach ($qualificationType as $type => $name) {
                                            if (empty($userQualifications[$type])) {
                                                continue;
                                            }
                                            $first = true; ?>
                                            <li>
                                                <a href="#tab-0<?php echo $i; ?>" class="<?php echo $i == 1 ? 'current' : '' ?>"><?php echo $name; ?></a>
                                            </li>
                                        <?php $i++;
                                        } ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="box-panel__body">
                                <?php
                                $i = 1;
                                foreach ($qualificationType as $type => $name) {
                                    if (empty($userQualifications[$type])) {
                                        continue;
                                    }
                                    $first = true; ?>
                                    <div class="row--resume<?php echo $i == 1 ? ' visible' : '' ?>" id="tab-0<?php echo $i; ?>">
                                        <h6 class="d-md-none mb-2 text-uppercase"><?php echo $name; ?></h6>
                                        <div class="resume-wrapper">
                                            <?php foreach ($userQualifications[$type] as $qualification) { ?>
                                                <div class="resume">
                                                    <div class="resume__primary"><?php echo $qualification['uqualification_start_year']; ?> - <?php echo $qualification['uqualification_end_year']; ?></div>
                                                    <div class="resume__secondary">
                                                        <b><?php echo $qualification['uqualification_title']; ?></b>
                                                        <p class="m-0"><?php echo $qualification['uqualification_institute_name']; ?></p>
                                                        <p class="m-0"><?php echo $qualification['uqualification_institute_address']; ?></p>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                <?php $i++;
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="panel-cover" id="teacherAvailability">
                    <div class="panel-cover__head panel__head-trigger panel__head-trigger-js">
                        <h3><?php echo Label::getLabel('LBL_TEACHER_AVAILABILITY'); ?></h3>
                    </div>
                    <div class="panel-cover__body panel__body-target panel__body-target-js">
                        <div class="box-panel">
                            <div class="box-panel__body">
                                <div class="teacher-availability" id="availbility"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php if ($teacher['testat_reviewes'] > 0) { ?>
                    <div class="panel-cover">
                        <div class="panel-cover__head panel__head-trigger panel__head-trigger-js">
                            <h3><?php echo Label::getLabel('LBL_REVIEW'); ?></h3>
                        </div>
                        <?php echo $reviewFrm->getFormHtml(); ?>
                        <div class="panel-cover__body panel__body-target panel__body-target-js">
                            <div class="rating-details mb-4">
                                <div class="rating-card">
                                    <div class="rating-card__counter">
                                        <div class="rating__count">
                                            <h1><?php echo $teacher['testat_ratings']; ?></h1>
                                            <svg class="icon icon--30 icon--rating">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#rating';    ?>"></use>
                                            </svg>
                                        </div>
                                        <div class="rating__info">
                                            <b><?php echo Label::getLabel('LBL_OVERALL_RATINGS'); ?></b>
                                        </div>
                                    </div>
                                    <div class="rating-card__progressbar">
                                        <div class="progressbar-wrapper">
                                            <ul class="listing">
                                                <?php foreach ($reviewsData as $key => $review) { ?>
                                                    <li class="rating">
                                                        <span class="rating__stars"><?php echo $key; ?>
                                                            <svg class="icon icon--xsmall icon--rating">
                                                                <use xlink:href="<?php echo CONF_WEBROOT_URL ?>images/sprite.svg#rating"></use>
                                                            </svg>
                                                        </span>
                                                        <div class="rating__progressbar">
                                                            <div style="width: <?php echo $review; ?>%;" class="fill"></div>
                                                        </div>
                                                        <span class="rating__percentage"><?php echo $review; ?>%</span>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="reviews-wrapper">
                                <div class="reviews-wrapper__head mb-4">
                                    <div class="reviews-counter" id="recordToDisplay"></div>
                                    <div class="review-sorting">
                                        <select name="sorting" onchange="loadReviews('<?php echo $teacher['user_id']; ?>', 1)">
                                            <?php $sortArr = RatingReview::getSortTypes(); ?>
                                            <?php foreach ($sortArr as $key => $value) { ?>
                                                <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div id="listing-reviews" class="reviews-wrapper__body"></div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <?php if (Course::isEnabled() && $moreCourses) { ?>
                    <div class="panel-cover">
                        <div class="panel-cover__head panel__head-trigger panel__head-trigger-js">
                            <h3>
                                <?php
                                echo Label::getLabel('LBL_COURSES');
                                ?>
                            </h3>
                        </div>
                        <div class="panel-cover__body mt-xl-0 panel__body-target panel__body-target-js">
                            <?php
                            echo $this->includeTemplate('teachers/courses.php', [
                                'moreCourses' => $moreCourses,
                                'checkoutForm' => $checkoutForm,
                                'siteLangId' => $siteLangId,
                                'siteUserId' => $siteUserId,
                            ]);
                            ?>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="profile-secondary">
                <div class="right-panel">
                    <div class="box box--book">
                        <?php if (!empty(MyUtility::validateYoutubeUrl($teacher['user_video_link']))) { ?>
                            <div class="dummy-video mb-4">
                                <div class="video-media ratio ratio--16by9">
                                    <iframe width="100%" height="100%" src="<?php echo MyUtility::validateYoutubeUrl($teacher['user_video_link']); ?>" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="book__actions pt-2">
                            <button class="btn btn--primary btn--xlarge btn--block color-white <?php echo $disabledClass; ?>" <?php echo $bookNowOnClickClick; ?>><?php echo Label::getLabel('LBL_Book_Now'); ?></button>
                            <button onclick="viewFullAvailbility();" class="btn btn--primary btn--xlarge btn--block"><?php echo Label::getLabel('LBL_VIEW_FULL_AVAILBILITY'); ?></button>
                            <button <?php echo $contactClick; ?> class="btn btn--primary-bordered btn--xlarge btn--block <?php echo $disabledClass; ?>">
                                <svg class="icon icon--envelope">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#envelope'; ?>"></use>
                                </svg>
                                <?php echo Label::getLabel('LBL_CONTACT'); ?>
                            </button>
                            <?php
                            if ($freeTrialEnabled) {
                                $btnText = "LBL_YOU_ALREADY_HAVE_AVAILED_THE_TRIAL";
                                $onclick = "";
                                $btnClass = "btn-secondary";
                                $disabledText = "btn--disabled";
                                if (!$isFreeTrailAvailed) {
                                    $disabledText = "";
                                    $onclick = "onclick=\"cart.trailCalendar('" . $teacher['user_id'] . "')\"";
                                    $btnClass = 'btn-primary';
                                    $btnText = "LBL_BOOK_FREE_TRIAL";
                                }
                                if ($siteUserId == $teacher['user_id']) {
                                    $onclick = "";
                                    $disabledText = "btn--disabled";
                                }
                            ?>
                                <button <?php echo $onclick; ?> class="btn btn--secondary btn--block btn--large <?php echo $btnClass . ' ' . $disabledText; ?> " <?php echo $disabledText; ?> data-bs-toggle="tooltip" data-bs-original-title="<?php echo Label::getLabel($btnText); ?>">
                                    <span><?php echo Label::getLabel('LBL_BOOK_FREE_TRIAL'); ?></span>
                                </button>
                                <p><?php echo Label::getLabel('LBL_TRIAL_LESSON_ONE_TIME'); ?></p>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">
    $(document).ready(function() {
        viewFullAvailbility = function() {
            if ($(window).width() < 767) {
                $("#teacherAvailability").children('.panel__head-trigger-js').click();
            }
            $('html, body').animate({
                scrollTop: $("#teacherAvailability").offset().top - 200
            }, 500);
        };
        viewCalendar(<?php echo $teacher['user_id']; ?>);
        <?php if ($teacher['testat_reviewes'] > 0) { ?>
            loadReviews('<?php echo $teacher['user_id']; ?>', 1);
        <?php } ?>

    });
</script>
<?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>