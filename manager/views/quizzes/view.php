<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_QUIZ_DETAIL'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <div class="table-group mb-1">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_BASIC_DETAILS'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_TITLE'); ?></th>
                    <td><?php echo CommonHelper::renderHtml($quiz['quiz_title']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_INSTRUCTIONS'); ?></th>
                    <td>
                        <div class="editor-content iframe-content">
                            <iframe onload="resetIframe(this);" src="<?php echo MyUtility::makeUrl('Quizzes', 'frame', [$quiz['quiz_id']]); ?>" style="border:none; width:100%; height:30px;"></iframe>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_TYPE'); ?></th>
                    <td><?php echo Quiz::getTypes($quiz['quiz_type']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_TEACHER_NAME'); ?></th>
                    <td><?php echo ucwords($quiz['teacher_first_name'] . ' ' . $quiz['teacher_last_name']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_ACTIVE'); ?></th>
                    <td><?php echo AppConstant::getYesNoArr($quiz['quiz_active']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                    <td><?php echo Quiz::getStatuses($quiz['quiz_status']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_ADDED_ON'); ?></th>
                    <td><?php echo MyDate::showDate($quiz['quiz_created'], true); ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="table-group mb-1">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_SETTINGS'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_DURATION'); ?></th>
                    <td>
                        <?php echo ($quiz['quiz_duration']) ? CommonHelper::convertDuration($quiz['quiz_duration']) : Label::getLabel('LBL_NA'); ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_NO_OF_ATTEMPTS_ALLOWED'); ?></th>
                    <td>
                        <?php echo ($quiz['quiz_attempts']) ? $quiz['quiz_attempts'] : Label::getLabel('LBL_NA'); ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_PASS_PERCENTAGE'); ?></th>
                    <td>
                        <?php echo ($quiz['quiz_passmark']) ? MyUtility::formatPercent($quiz['quiz_passmark']) : Label::getLabel('LBL_NA'); ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_VALIDITY'); ?></th>
                    <td>
                        <?php
                        if (!empty($quiz['quiz_validity'])) {
                            $label = Label::getLabel('LBL_{validity}_HOUR(S)');
                            echo str_replace('{validity}', $quiz['quiz_validity'], $label);
                        } else {
                            echo Label::getLabel('LBL_NA');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_CERTIFICATE'); ?></th>
                    <td><?php echo AppConstant::getYesNoArr($quiz['quiz_certificate']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_NO_OF_QUESTIONS'); ?></th>
                    <td><?php echo $quiz['quiz_questions']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_PASS_MESSAGE'); ?></th>
                    <td>
                        <?php echo ($quiz['quiz_passmsg']) ? CommonHelper::renderHtml(nl2br($quiz['quiz_passmsg'])) : Label::getLabel('LBL_NA');  ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_FAIL_MESSAGE'); ?></th>
                    <td>
                        <?php echo ($quiz['quiz_failmsg']) ? CommonHelper::renderHtml(nl2br($quiz['quiz_failmsg']))  : Label::getLabel('LBL_NA'); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>