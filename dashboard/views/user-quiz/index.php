<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="page-body">
    <div class="container container--narrow">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="box-view box-view--space">
                    <hgroup class="mb-3">
                        <h4 class="mb-2">
                            <?php echo Label::getLabel('LBL_QUIZ_SOLVING_INSTRUCTIONS_HEADING'); ?>
                        </h4>
                    </hgroup>
                    <div class="check-list mb-5 iframe-content">
                        <iframe onload="resetIframe(this);" src="<?php echo MyUtility::makeUrl('UserQuiz', 'frame', [$data['quilin_id']]); ?>" style="border:none; width:100%; height:30px;"></iframe>
                    </div>
                    <div class="repeat-items mb-5">
                        <div class="repeat-element">
                            <div class="repeat-element__title">
                                <?php echo Label::getLabel('LBL_QUIZ_TYPE'); ?>
                            </div>
                            <div class="repeat-element__content">
                                <?php echo Quiz::getTypes($data['quilin_type']) ?>
                            </div>
                        </div>
                        <div class="repeat-element">
                            <div class="repeat-element__title">
                                <?php echo Label::getLabel('LBL_TOTAL_NO._OF_QUESTIONS') ?>
                            </div>
                            <div class="repeat-element__content">
                                <?php echo $data['quilin_questions'] ?>
                            </div>
                        </div>
                        <div class="repeat-element">
                            <div class="repeat-element__title">
                                <?php echo Label::getLabel('LBL_DURATION'); ?>
                            </div>
                            <div class="repeat-element__content">
                                <?php
                                if ($data['quilin_duration'] > 0) {
                                    echo CommonHelper::convertDuration($data['quilin_duration']);
                                } else {
                                    echo Label::getLabel('LBL_NO_LIMIT');
                                }
                                ?>
                            </div>
                        </div>
                        <div class="repeat-element">
                            <div class="repeat-element__title">
                                <?php echo Label::getLabel('LBL_ATTEMPTS_AVAILABLE'); ?>
                            </div>
                            <div class="repeat-element__content">
                                <?php
                                $label = Label::getLabel('LBL_{attempts}/{total}');
                                echo str_replace(
                                    ['{attempts}', '{total}'],
                                    [($data['quilin_attempts'] - $attempts), $data['quilin_attempts']],
                                    $label
                                );
                                ?>
                            </div>
                        </div>
                        <div class="repeat-element">
                            <div class="repeat-element__title">
                                <?php echo Label::getLabel('LBL_PASS_PERCENTAGE'); ?>
                            </div>
                            <div class="repeat-element__content">
                                <?php echo MyUtility::formatPercent($data['quilin_passmark']) ?>
                            </div>
                        </div>
                        <?php if ($data['quilin_record_type'] != AppConstant::COURSE) { ?>
                            <div class="repeat-element">
                                <div class="repeat-element__title">
                                    <?php echo Label::getLabel('LBL_VALID_TILL'); ?>
                                </div>
                                <div class="repeat-element__content">
                                    <?php echo MyDate::showDate($data['quilin_validity'], true) ?>
                                </div>
                            </div>
                            <?php if ($data['quilin_certificate'] == AppConstant::YES) { ?>
                                <div class="repeat-element">
                                    <div class="repeat-element__title">
                                        <?php echo Label::getLabel('LBL_OFFER_CERTIFICATE'); ?>
                                    </div>
                                    <div class="repeat-element__content">
                                        <?php echo AppConstant::getYesNoArr($data['quilin_certificate']) ?>
                                    </div>
                                </div>
                            <?php } ?>
                        <?php } ?>
                    </div>
                    <a href="javascript:void(0);" onclick="start('<?php echo $data['quizat_id'] ?>')" class="btn btn--primary btn--wide">
                        <?php echo Label::getLabel('LBL_START_NOW'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>