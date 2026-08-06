<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (!$langPage) {
    $sorting = AppConstant::getSortbyArr();
}
$hourStringLabel = Label::getLabel('LBL_{hourstring}_HRS');
$offerPriceLabel = Label::getLabel('LBL_{percentages}%_OFF_ON_{duration}_MINUTES_SESSION');
$isCourseEnabled = Course::isEnabled();
$colorClass = [1 => 'cell-green-40', 2 => 'cell-green-60', 3 => 'cell-green-80', 4 => 'cell-green-100'];
?>
<?php if (count($teachers)) { ?>
    <div class="box-wrapper">
        <?php foreach ($teachers as $teacher) {
            $totalSessions = $teacher['testat_lessons'];
            if (GroupClass::isEnabled()) {
                $totalSessions =  $totalSessions + $teacher['testat_classes'];
            }
        ?>
            <div class="profile-card">
                <div class="profile-card__body">
                    <div class="profile-card__media">
                        <div class="avtar avtar--centered" data-title="<?php echo CommonHelper::getFirstChar($teacher['user_first_name']); ?>">
                            <a href="<?php echo MyUtility::makeUrl('teachers', 'view', [$teacher['user_username']]) ?>">
                                <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['user_id'], Afile::SIZE_MEDIUM]), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name']; ?>">
                            </a>
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
                    <div class="profile-card__content">
                        <div class="profile-detail">
                            <div class="profile-info mb-2">
                                <a href="<?php echo MyUtility::makeUrl('teachers', 'view', [$teacher['user_username']]) ?>" class="tutor-name">
                                    <h4><?php echo $teacher['user_first_name'] . ' ' . $teacher['user_last_name']; ?></h4>
                                </a>

                                <?php if (!empty($teacher['user_featured'])) { ?>
                                    <div class="badge-secure is-hover">
                                        <svg class="icon icon--small icon--featured" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="12" height="12">
                                            <path fill-rule="evenodd" d="M15.291 4.055 12 2 8.709 4.055l-3.78.874-.874 3.78L2 12l2.055 3.291.874 3.78 3.78.874L12 22l3.291-2.055 3.78-.874.874-3.78L22 12l-2.055-3.291-.874-3.78zM9.793 15.707l.707.707.707-.707 6-6-1.414-1.414-5.293 5.293-2.293-2.293-1.414 1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                        <div class="tooltip tooltip--top tooltip--round bg-black no-wrap"><?php echo Label::getLabel('LBL_FEATURED'); ?></div>
                                    </div>
                                <?php } ?>


                                <div class="info-tag ratings">
                                    <svg class="icon icon--rating">
                                        <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND . 'images/sprite.svg#rating'; ?>"></use>
                                    </svg>
                                    <span class="value"><?php echo $teacher['testat_ratings']; ?></span>
                                    <span class="count">(<?php echo $teacher['testat_reviewes']; ?>)</span>
                                </div>
                            </div>
                            <div class="info-wrapper mb-3">
                                <div class="meta-info"><span class="value"><?php echo $teacher['testat_students']; ?></span><?php echo ' ' . Label::getLabel('LBL_Students'); ?></div>
                                <div class="meta-info"><span class="value"><?php echo $totalSessions; ?></span><?php echo ' ' . Label::getLabel('LBL_Sessions'); ?></div>
                                <?php if ($isCourseEnabled) { ?>
                                    <div class="meta-info"><span class="value"><?php echo $teacher['courses']; ?></span><?php echo ' ' . Label::getLabel('LBL_COURSES'); ?></div>
                                <?php } ?>
                            </div>
                            <?php if (!empty($teacher['offers'])) { ?>
                                <?php $this->includeTemplate('_partial/offers.php', ['offers' => $teacher['offers'], 'offerPriceLabel' => $offerPriceLabel], false); ?>
                            <?php } ?>

                            <div class="profile-card-price mb-3"><?php echo MyUtility::formatMoney($teacher['testat_minprice']) . ' - ' . MyUtility::formatMoney($teacher['testat_maxprice']); ?></div>

                            <div class="info-group">
                                <h6><?php echo Label::getLabel('LBL_Teaches'); ?>:</h6>
                                <span><?php echo $teacher['teacherTeachLanguageName'] ?></span>
                            </div>
                            <div class="info-group">
                                <h6><?php echo Label::getLabel('LBL_Speaks'); ?>:</h6>
                                <span><?php echo $teacher['spoken_language_names']; ?></span>
                            </div>
                        </div>
                        <?php if ($teacher['user_biography'] != '') { ?>
                            <div class="profile-detail">
                                <div class="info-group">
                                    <div class="info-group__head">
                                        <h5><?php echo LABEL::getLabel('LBL_About'); ?></h5>
                                    </div>
                                    <div class="info-group__body">
                                        <p><?php echo nl2br($teacher['user_biography']) ?></p>
                                        <a class="txt-link" href="<?php echo MyUtility::makeUrl('teachers', 'view', [$teacher['user_username']]) ?>"><?php echo Label::getLabel('LBL_View_Profile') ?></a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <div class="profile-card__foot">
                    <div class="profile-card__actions">
                        <ul class="action-list">
                            <?php if ($siteUserId != $teacher['user_id']) { ?>
                                <li>
                                    <button class="btn btn-equal is-hover <?php echo ($teacher['uft_id']) ? 'is--active' : ''; ?>" onClick="toggleTeacherFavorite(<?php echo $teacher['user_id']; ?>, this)">
                                        <svg class="icon icon--small icon--heart">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#heart'; ?>"></use>
                                        </svg>
                                    </button>
                                </li>
                            <?php } ?>
                            <li>
                                <div class="social-share dropdown">
                                    <button class="btn btn-equal dropdown-toggle" onclick="fetchTeacherMeta('<?php echo $teacher['user_id']; ?>')" data-bs-toggle="dropdown" aria-expanded="false">
                                        <svg class="icon icon--small icon--share">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#share'; ?>"></use>
                                        </svg>
                                    </button>
                                    <div class="dropdown-menu">
                                        <h6><?php echo Label::getLabel('LBL_SHARE_ON'); ?></h6>
                                        <ul class="social--share mt-2 social-share-set-<?php echo $teacher['user_id']; ?>">
                                            <li class="social--fb"><a data-title data-url="<?php echo MyUtility::generateFullUrl('Teachers', 'view', [$teacher['user_username']]); ?>" class="st-custom-button share-teacher-js-<?php echo $teacher['user_id']; ?>" data-network="facebook" displayText='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>' title='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_01.svg" alt="<?php echo Label::getLabel('LBL_FACEBOOK'); ?>"></a></li>
                                            <li class="social--tw"><a data-title data-url="<?php echo MyUtility::generateFullUrl('Teachers', 'view', [$teacher['user_username']]); ?>" class="st-custom-button share-teacher-js-<?php echo $teacher['user_id']; ?>" data-network="twitter" displayText='<?php echo Label::getLabel('LBL_X'); ?>' title='<?php echo Label::getLabel('LBL_X'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_02.svg" alt="<?php echo Label::getLabel('LBL_X'); ?>"></a></li>
                                            <li class="social--pt"><a data-title="" data-url="<?php echo MyUtility::generateFullUrl('Teachers', 'view', [$teacher['user_username']]); ?>" class="st-custom-button share-teacher-js-<?php echo $teacher['user_id']; ?>" data-network="pinterest" displayText='<?php echo Label::getLabel('LBL_PINTEREST'); ?>' title='<?php echo Label::getLabel('LBL_PINTEREST'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_05.svg" alt="<?php echo Label::getLabel('LBL_PINTEREST'); ?>"></a></li>
                                            <li class="social--mail"><a data-title data-url="<?php echo MyUtility::generateFullUrl('Teachers', 'view', [$teacher['user_username']]); ?>" class="st-custom-button share-teacher-js-<?php echo $teacher['user_id']; ?>" data-network="email" displayText='<?php echo Label::getLabel('LBL_EMAIL'); ?>' title='<?php echo Label::getLabel('LBL_EMAIL'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_06.svg" alt="<?php echo Label::getLabel('LBL_EMAIL'); ?>"></a></li>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                            <?php if (!empty($teacher['user_video_link'])) { ?>
                                <li>
                                    <a class="btn btn-equal is-hover link-trigger trigger-js video-js" id="video-id-<?php echo $teacher['user_id']; ?>" href="#VIDEO-POPUP<?php echo $teacher['user_id']; ?>">
                                        <svg class="icon icon--small" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M3 3.9934C3 3.44476 3.44495 3 3.9934 3H20.0066C20.5552 3 21 3.44495 21 3.9934V20.0066C21 20.5552 20.5551 21 20.0066 21H3.9934C3.44476 21 3 20.5551 3 20.0066V3.9934ZM5 5V19H19V5H5ZM10.6219 8.41459L15.5008 11.6672C15.6846 11.7897 15.7343 12.0381 15.6117 12.2219C15.5824 12.2658 15.5447 12.3035 15.5008 12.3328L10.6219 15.5854C10.4381 15.708 10.1897 15.6583 10.0672 15.4745C10.0234 15.4088 10 15.3316 10 15.2526V8.74741C10 8.52649 10.1791 8.34741 10.4 8.34741C10.479 8.34741 10.5562 8.37078 10.6219 8.41459Z"></path>
                                        </svg>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                        <div class="action-buttons">
                            <?php if ($siteUserId != $teacher['user_id']) { ?>
                                <button onclick="cart.langSlots('<?php echo $teacher['user_id']; ?>')" class="btn btn--primary"><?php echo Label::getLabel('LBL_Book_Now'); ?></button>
                                <button onclick="threadForm(<?php echo $teacher['user_id']; ?>,<?php echo Thread::PRIVATE ?>);" class="btn btn--bordered color-primary">
                                    <?php echo Label::getLabel('LBL_Contact'); ?>
                                </button>

                                <?php
                                if ($teacher['freeTrialEnabled']) {
                                    $btnText = "LBL_YOU_ALREADY_HAVE_AVAILED_THE_TRIAL";
                                    $onclick = "";
                                    $btnClass = "btn-secondary";
                                    $disabledText = "btn--disabled";
                                    $tooltip = 'data-bs-toggle="tooltip"';
                                    if (!$teacher['isFreeTrailAvailed']) {
                                        $disabledText = "";
                                        $onclick = "onclick=\"cart.trailCalendar('" . $teacher['user_id'] . "')\"";
                                        $btnClass = 'btn-primary';
                                        $btnText = "LBL_BOOK_FREE_TRIAL";
                                        $tooltip = '';
                                    }
                                    if ($siteUserId == $teacher['user_id']) {
                                        $onclick = "";
                                        $disabledText = "disabled";
                                    }
                                ?>

                                    <button <?php echo $onclick; ?> type="button" class="btn btn--secondary <?php echo $btnClass . ' ' . $disabledText; ?>" <?php echo $tooltip; ?> title="<?php echo Label::getLabel($btnText); ?>" <?php echo $disabledText; ?>>
                                        <?php echo Label::getLabel('LBL_BOOK_FREE_TRIAL'); ?>
                                    </button>
                                <?php } ?>
                            <?php } else { ?>

                                <button class="btn btn--primary disabled"><?php echo Label::getLabel('LBL_Book_Now'); ?></button>
                                <button class="btn btn--bordered color-primary disabled">
                                    <?php echo Label::getLabel('LBL_Contact'); ?>
                                </button>
                            <?php } ?>
                        </div>
                        <a href="javascript:void(0);" onclick="viewCalendar(<?php echo $teacher['user_id']; ?>, 'paid')" class="txt-link color-primary"><?php echo Label::getLabel('LBL_View_availability'); ?></a>
                    </div>
                </div>
                <?php if (!empty($teacher['user_video_link'])) { ?>
                    <div class="profile-card__popup" id="VIDEO-POPUP<?php echo $teacher['user_id']; ?>">
                        <a href="javascript:void(0);" onclick="showIntroVideo(<?php echo $teacher['user_id']; ?>)" class="btn-close video-close"></a>
                        <div class="video-wrapper">
                            <div class="intro-video video-src" data-src="<?php echo $teacher['user_video_link']; ?>">
                                <iframe width="100%" height="100%" src="" frameborder="0" allowfullscreen></iframe>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        <?php } ?>
        <div class="show-more">
            <?php
            echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
            $pagingArr = ['page' => $post['pageno'], 'pageCount' => $pageCount, 'recordCount' => $recordCount, 'callBackJsFunc' => 'gotoPage'];
            $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
            ?>
        </div>
    </div>
<?php } else { ?>
    <div class="page-listing__body">
        <div class="box -padding-30" style="margin-bottom: 30px;">
            <div class="message-display">
                <div class="message-display__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 408">
                        <path d="M488.468,408H23.532A23.565,23.565,0,0,1,0,384.455v-16.04a15.537,15.537,0,0,1,15.517-15.524h8.532V31.566A31.592,31.592,0,0,1,55.6,0H456.4a31.592,31.592,0,0,1,31.548,31.565V352.89h8.532A15.539,15.539,0,0,1,512,368.415v16.04A23.565,23.565,0,0,1,488.468,408ZM472.952,31.566A16.571,16.571,0,0,0,456.4,15.008H55.6A16.571,16.571,0,0,0,39.049,31.566V352.891h433.9V31.566ZM497,368.415a0.517,0.517,0,0,0-.517-0.517H287.524c0.012,0.172.026,0.343,0.026,0.517a7.5,7.5,0,0,1-7.5,7.5h-48.1a7.5,7.5,0,0,1-7.5-7.5c0-.175.014-0.346,0.026-0.517H15.517a0.517,0.517,0,0,0-.517.517v16.04a8.543,8.543,0,0,0,8.532,8.537H488.468A8.543,8.543,0,0,0,497,384.455h0v-16.04ZM63.613,32.081H448.387a7.5,7.5,0,0,1,0,15.008H63.613A7.5,7.5,0,0,1,63.613,32.081ZM305.938,216.138l43.334,43.331a16.121,16.121,0,0,1-22.8,22.8l-43.335-43.318a16.186,16.186,0,0,1-4.359-8.086,76.3,76.3,0,1,1,19.079-19.071A16,16,0,0,1,305.938,216.138Zm-30.4-88.16a56.971,56.971,0,1,0,0,80.565A57.044,57.044,0,0,0,275.535,127.978ZM63.613,320.81H448.387a7.5,7.5,0,0,1,0,15.007H63.613A7.5,7.5,0,0,1,63.613,320.81Z"></path>
                    </svg>
                </div>
                <h5><?php echo Label::getLabel('LBL_NO_RESULT_FOUND!'); ?></h5>
            </div>
        </div>
    </div>
<?php } ?>

<script>
    TEACHER_COUNT = '<?php echo $recordCount; ?>';
    COUNT_LABEL_TXT = '<?php echo Label::getLabel('LBL_FOUND_THE_BEST_{recordcount}_TEACHERS_FOR_YOU'); ?>';
    COUNT_LABEL_TXT = COUNT_LABEL_TXT.replace('{recordcount}', TEACHER_COUNT);
    $('.record-count-header').text(COUNT_LABEL_TXT);

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>