<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$username = ucwords($user['user_first_name'] . ' ' . $user['user_last_name']);
$showStats = true;
?>
<div class="page-body">
    <div class="container container--narrow">
        <div class="message-display no-skin">
            <?php if ($data['quizat_evaluation'] == QuizAttempt::EVALUATION_PENDING) { ?>
                <div class="message-display__media">
                    <img src="<?php echo CONF_WEBROOT_DASHBOARD ?>images/700x400.svg" alt="">
                </div>
                <h3 class="mb-2">
                    <?php
                    $label = Label::getLabel('LBL_QUIZ_PASS_MSG_HEADING');
                    echo str_replace('{username}', '<strong class="bold-700">' . $username . '</strong>', $label);
                    ?>
                </h3>
                <p class="mb-2">
                    <?php echo Label::getLabel('LBL_MANUAL_QUIZ_COMPLETED_MESSAGE'); ?>
                </p>
                <div class="inline-meta my-4">
                    <span class="inline-meta__item">
                        <?php echo Label::getLabel('LBL_PROGRESS:'); ?>
                        <strong><?php echo MyUtility::formatPercent($data['quizat_progress']); ?></strong>
                    </span>
                    <span class="inline-meta__item">
                        <?php echo Label::getLabel('LBL_TIME_SPENT:'); ?>
                        <strong>
                            <?php
                            echo CommonHelper::convertDuration(strtotime($data['quizat_updated']) - strtotime($data['quizat_started']), true, true);
                            ?>
                        </strong>
                    </span>
                </div>
                <?php $showStats = false; ?>
            <?php } elseif ($data['quizat_evaluation'] == QuizAttempt::EVALUATION_PASSED) { ?>
                <div class="message-display__media">
                    <img src="<?php echo CONF_WEBROOT_DASHBOARD ?>images/700x400.svg" alt="">
                </div>
                <h3 class="mb-2">
                    <?php
                    $label = Label::getLabel('LBL_QUIZ_PASS_MSG_HEADING');
                    echo str_replace('{username}', '<strong class="bold-700">' . $username . '</strong>', $label);
                    ?>
                </h3>
                <p class="mb-2">
                    <?php echo CommonHelper::renderHtml(nl2br($data['quilin_passmsg'])); ?>
                </p>
            <?php } else { ?>
                <div class="message-display__media">
                    <img src="<?php echo CONF_WEBROOT_DASHBOARD ?>images/quiz-fail.svg" alt="">
                </div>
                <h3 class="mb-2">
                    <?php
                    $label = Label::getLabel('LBL_QUIZ_FAIL_MSG_HEADING');
                    echo str_replace('{username}', '<strong class="bold-700">' . $username . '</strong>', $label);
                    ?>
                </h3>
                <p class="mb-2">
                    <?php echo CommonHelper::renderHtml(nl2br($data['quilin_failmsg'])); ?>
                </p>
            <?php } ?>

            <?php if ($showStats == true) { ?>
                <div class="inline-meta my-4">
                    <span class="inline-meta__item">
                        <?php echo Label::getLabel('LBL_SCORE:'); ?>
                        <strong>
                            <?php
                            $label = Label::getLabel('LBL_{score}_OF_{total}');
                            echo str_replace(['{score}', '{total}'], [floatval($data['quizat_marks']), floatval($data['quilin_marks'])], $label);
                            ?>
                        </strong>
                    </span>
                    <span class="inline-meta__item">
                        <?php echo Label::getLabel('LBL_ACHIEVED_PERCENT:'); ?>
                        <strong><?php echo MyUtility::formatPercent($data['quizat_scored']); ?></strong>
                    </span>
                    <span class="inline-meta__item">
                        <?php echo Label::getLabel('LBL_TIME_SPENT:'); ?>
                        <strong>
                            <?php
                            echo CommonHelper::convertDuration(strtotime($data['quizat_updated']) - strtotime($data['quizat_started']), true, true);
                            ?>
                        </strong>
                    </span>
                </div>
            <?php } ?>
            <div class="d-sm-flex justify-content-center mt-3">
                <?php if ($canRetake == true) { ?>
                    <a href="javascript:void(0);" class="btn btn--primary-bordered m-1 btn--sm-block" onclick="retakeQuiz('<?php echo $data['quizat_id'] ?>');">
                        <svg class="icon icon--png icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#retake"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_RETAKE_QUIZ'); ?>
                    </a>
                <?php } ?>
                <?php if ($data['quizat_evaluation'] != QuizAttempt::EVALUATION_PENDING) { ?>
                    <a href="<?php echo MyUtility::makeUrl('QuizReview', 'index', [$data['quizat_id']]) ?>" class="btn btn--primary m-1 btn--sm-block">
                        <?php echo Label::getLabel('LBL_CHECK_ANSWERS'); ?>
                    </a>
                <?php } ?>
                <?php if ($data['quilin_record_type'] == AppConstant::COURSE) { ?>
                    <?php if ($courseStatus == true && $data['quizat_evaluation'] == QuizAttempt::EVALUATION_PASSED) { ?>
                        <a href="javascript:void(0);" onclick="parent.finishQuiz();" class="btn btn--primary-bordered m-1 btn--sm-block">
                            <?php echo Label::getLabel('LBL_PROCEED'); ?>
                        </a>
                    <?php } ?>
                <?php } else { ?>
                    <?php $controller = ($data['quilin_record_type'] == AppConstant::LESSON) ? 'Lessons' : 'Classes'; ?>
                    <a href="<?php echo MyUtility::makeUrl($controller); ?>" class="btn btn--primary-bordered m-1 btn--sm-block">
                        <svg class="icon icon--png icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#arrow-back"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_GO_TO_QUIZZES'); ?>
                    </a>
                <?php } ?>
                <?php if ($canDownloadCertificate == true) { ?>
                    <a href="<?php echo MyUtility::makeUrl('UserQuiz', 'downloadCertificate', [$data['quizat_id']], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary m-1 btn--sm-block">
                        <svg class="icon icon--png icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#download-icon"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_DOWNLOAD_CERTIFICATE'); ?>
                    </a>
                <?php } ?>
            </div>
            <?php if ($canRetake == true && $canDownloadCertificate == true) { ?>
                <div class="option-hint">
                    <span class="d-inline-flex align-items-center">
                        <span class="option-hint__title d-inline-flex align-items-center me-1">
                            <strong class="d-inline-flex align-items-center">
                                <svg class="icon icon--dashboard me-2 icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#hint'; ?>"></use>
                                </svg>
                                <?php echo Label::getLabel('LBL_NOTE:'); ?>
                            </strong>
                        </span>
                        <span class="option-hint__content">
                            <?php echo Label::getLabel('LBL_RETAKE_WILL_NOT_BE_ALLOWED_ONCE_THE_CERTIFICATE_IS_DOWNLOADED.'); ?>
                        </span>
                    </span>
                </div>
            <?php } ?>
        </div>
    </div>
</div>