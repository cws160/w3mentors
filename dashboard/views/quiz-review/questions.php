<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="page-body">
    <div class="container container--narrow">
        <div class="flex-header">
            <div class="d-sm-flex align-items-center justify-content-between">
                <div>
                    <div class="page-meta">
                        <span class="page-meta__item page-meta__item-first questionInfoJs">
                            <?php
                            $quesInfoLabel = Label::getLabel('LBL_QUESTION_{current-question}_OF_{total-questions}');
                            echo str_replace(
                                ['{current-question}', '{total-questions}'],
                                [
                                '<strong class="quesNumJs">0</strong>',
                                '<strong>' . $data['quilin_questions'] . '</strong>'
                                ],
                                $quesInfoLabel
                            );
                            ?>
                        </span>
                        <span class="page-meta__item page-meta__item-second">
                            <?php echo Label::getLabel('LBL_TOTAL_MARKS'); ?>
                            <strong class="totalMarksJs"><?php echo floatval($data['quilin_marks']) ?></strong>
                        </span>
                        <div class="page-meta__item">
                            <div class="page-progress">
                                <div class="page-progress__value">
                                    <?php echo Label::getLabel('LBL_QUIZ_PROGRESS'); ?>
                                </div>
                                <div class="page-progress__content">
                                    <div class="progress progress--xsmall progress--round">
                                        <?php if ($data['quizat_progress'] > 0) { ?>
                                            <div class="progress__bar bg-green progressBarJs" role="progressbar" style="width:<?php echo $data['quizat_progress'] ?>%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                        <?php } ?>
                                    </div>
                                </div>
                                <div class="page-progress__value">
                                    <strong class="progressJs">
                                        <?php echo MyUtility::formatPercent($data['quizat_progress']) ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="page-meta">
                        <div class="page-meta__item">
                            <div class="timer">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex-layout quizPanelJs">
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        view('<?php echo $data['quizat_id']; ?>');
    });
</script>