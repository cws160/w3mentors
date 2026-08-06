<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="page-body">
    <div class="container container--narrow">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="box-view box-view--space">
                    <hgroup class="mb-3">
                        <h4 class="mb-2">
                            <?php echo CommonHelper::renderHtml($data['quilin_title']); ?>
                        </h4>
                    </hgroup>
                    <div class="check-list mb-5 iframe-content">
                        <iframe onload="resetIframe(this);" src="<?php echo MyUtility::makeUrl('QuizReview', 'frame', [$data['quilin_id']]); ?>" style="border:none; width:100%; height:30px;"></iframe>
                    </div>
                    <div class="repeat-items mb-5">
                        <?php if ($data['quilin_type'] == Quiz::TYPE_AUTO_GRADED || $data['quizat_evaluation'] != QuizAttempt::EVALUATION_PENDING) { ?>
                            <div class="repeat-element">
                                <div class="repeat-element__title">
                                    <?php echo Label::getLabel('LBL_QUIZ_SCORE'); ?>
                                </div>
                                <div class="repeat-element__content">
                                    <?php
                                    $label = Label::getLabel('LBL_{score}_OF_{total}');
                                    echo str_replace(['{score}', '{total}'], [floatval($data['quizat_marks']), floatval($data['quilin_marks'])], $label);
                                    ?>
                                </div>
                            </div>
                            <div class="repeat-element">
                                <div class="repeat-element__title">
                                    <?php echo Label::getLabel('LBL_ACHIEVED_PERCENT') ?>
                                </div>
                                <div class="repeat-element__content">
                                    <?php echo MyUtility::formatPercent($data['quizat_scored']); ?>
                                </div>
                            </div>
                        <?php } ?>
                        <div class="repeat-element">
                            <div class="repeat-element__title">
                                <?php echo Label::getLabel('LBL_TIME_SPENT'); ?>
                            </div>
                            <div class="repeat-element__content">
                                <?php
                                echo CommonHelper::convertDuration(strtotime($data['quizat_updated']) - strtotime($data['quizat_started']), true, true);
                                ?>
                            </div>
                        </div>
                        <div class="repeat-element">
                            <div class="repeat-element__title">
                                <?php echo Label::getLabel('LBL_ATTEMPTS') ?>
                            </div>
                            <div class="repeat-element__content">
                                <?php
                                $label = Label::getLabel('LBL_{attempts}/{total}');
                                echo str_replace(
                                    ['{attempts}', '{total}'],
                                    [$attempts, $data['quilin_attempts']],
                                    $label
                                );
                                ?>
                            </div>
                        </div>
                        <div class="repeat-element">
                            <div class="repeat-element__title">
                                <?php echo Label::getLabel('LBL_EVALUATION_STATUS'); ?>
                            </div>
                            <div class="repeat-element__content">
                                <?php echo QuizAttempt::getEvaluationStatuses($data['quizat_evaluation']) ?>
                            </div>
                        </div>
                    </div>
                    <a href="javascript:void(0);" onclick="start('<?php echo $data['quizat_id'] ?>')" class="btn btn--primary btn--wide">
                        <?php echo Label::getLabel('LBL_REVIEW'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>