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
                            <?php echo Label::getLabel('LBL_TOTAL_MARKS'); ?> <strong class="totalMarksJs"></strong>
                        </span>
                        <div class="page-meta__item">
                            <div class="page-progress">
                                <div class="page-progress__value">
                                    <?php echo Label::getLabel('LBL_QUIZ_PROGRESS'); ?>
                                </div>
                                <div class="page-progress__content">
                                    <div class="progress progress--xsmall progress--round">
                                        <div class="progress__bar bg-green progressBarJs" role="progressbar" style="width:60%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                <div class="page-progress__value"> <strong class="progressJs"></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="page-meta">
                        <div class="page-meta__item">
                            <div class="timer">
                                <?php if ($data['quilin_duration'] > 0) { ?>
                                    <div class="timer__media">
                                        <svg class="icon icon--clock" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                                            <path d="M9,16a7,7,0,1,1,7-7A7,7,0,0,1,9,16Zm0-1.4A5.6,5.6,0,1,0,3.4,9,5.6,5.6,0,0,0,9,14.6ZM9.7,9h2.8v1.4H8.3V5.5H9.7Z" transform="translate(3 3)" fill="#333" />
                                        </svg>
                                        <span><?php echo Label::getLabel('LBL_TIME_LEFT:'); ?> </span>
                                    </div>
                                    <div class="timer__content">
                                        <?php
                                        $endtime = $data['quilin_duration'] + strtotime($data['quizat_started']);
                                        ?>
                                        <span class="timer__controls" id="quizTimer" timestamp="<?php echo $endtime; ?>"> 00:00:00:00 </span>
                                    </div>
                                <?php } ?>
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
        view('<?php echo $attemptId; ?>');

        $('#quizTimer').w3mentorsTimer({
            recordId: <?php echo $attemptId; ?>,
            recordType: 'QUIZ',
            callback: function() {
                finish();
            },
            notify: function() {
                fcom.error("<?php echo Label::getLabel('LBL_FEW_SECONDS_LEFT._PLEASE_KEEP_YOUR_ANSWERS_SAVED') ?>");
            }
        });
    });
</script>