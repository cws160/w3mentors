<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$types = Quiz::getTypes();
$status = QuizAttempt::getStatuses();
?>
<div class="modal-header">
    <h5><?php echo Label::getLabel('LBL_ATTACHED_QUIZZES'); ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="form-edit-body">
        <div class="table-scroll">
            <table class="table table--responsive table--bordered" id="">
                <thead>
                    <tr class="title-row">
                        <?php if ($recordType == AppConstant::LESSON) { ?>
                            <th><?php echo Label::getLabel('LBL_ID'); ?></th>
                        <?php } ?>
                        <th><?php echo Label::getLabel('LBL_TITLE'); ?></th>
                        <th><?php echo Label::getLabel('LBL_TYPE'); ?></th>
                        <?php if ($recordType == AppConstant::LESSON) { ?>
                            <th><?php echo Label::getLabel('LBL_LEARNER'); ?></th>
                        <?php } ?>
                        <th><?php echo Label::getLabel('LBL_VALID_TILL'); ?></th>
                        <th><?php echo Label::getLabel('LBL_ACTION'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($quizzes) > 0) { ?>
                        <?php foreach ($quizzes as $quiz) { ?>
                            <tr class="quizRowJs quizRow<?php echo $quiz['quilin_id'] ?>">
                                <?php if ($recordType == AppConstant::LESSON) { ?>
                                    <td>
                                        <?php
                                        $label = Label::getLabel('LBL_QZ{quiz-id}');
                                        $str = str_pad($quiz['quilin_quiz_id'], 3, "0", STR_PAD_LEFT) . '-' . $quiz['users']['quizat_id'];
                                        echo str_replace('{quiz-id}', $str, $label);
                                        ?>
                                    </td>
                                <?php } ?>
                                <td width="40%">
                                    <div class="d-inline-flex action-trigger">
                                        <?php if ($recordType == AppConstant::GCLASS) { ?>
                                            <span class="arrow-icon ms-0" onclick="view('<?php echo $quiz['quilin_id'] ?>');"></span>
                                        <?php } ?>
                                        <span><?php echo $quiz['quilin_title'] ?></span>

                                    </div>
                                </td>
                                <td><?php echo $types[$quiz['quilin_type']] ?></td>
                                <?php if ($recordType == AppConstant::LESSON) { ?>
                                    <td>
                                        <?php echo ucwords($quiz['users']['user_first_name'] . ' ' . $quiz['users']['user_last_name']); ?>
                                    </td>
                                <?php } ?>
                                <td>
                                    <?php
                                    if (strtotime(date('Y-m-d H:i:s')) >= strtotime($quiz['quilin_validity'])) {
                                        echo Label::getLabel('LBL_EXPIRED');
                                    } else {
                                        echo MyDate::showDate(MyDate::formatDate($quiz['quilin_validity']), true);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if ($recordType == AppConstant::LESSON) { ?>
                                        <?php
                                        $target = '';
                                        $url = "javascript:void(0);";
                                        if ($quiz['users']['quizat_status'] == QuizAttempt::STATUS_COMPLETED) {
                                            $target = "target='_blank'";
                                            $url = MyUtility::makeFullUrl('QuizReview', 'index', [$quiz['users']['quizat_id']]);
                                        }
                                        ?>
                                        <a <?php echo $target; ?> href="<?php echo $url; ?>" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover <?php echo empty($target) ? 'btn--disabled' : '' ?>">
                                            <svg class="icon icon--cancel icon--small">
                                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#view"></use>
                                            </svg>
                                            <div class="tooltip tooltip--top bg-black">
                                                <?php echo Label::getLabel('LBL_VIEW'); ?>
                                            </div>
                                        </a>
                                    <?php } ?>
                                    <a href="javascript:void(0);" data-record-id="<?php echo $recordId; ?>" data-record-type="<?php echo $recordType; ?>" onclick="removeQuiz('<?php echo $quiz['quilin_id']; ?>', this);" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                        <svg class="icon icon--cancel icon--small">
                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#cancel"></use>
                                        </svg>
                                        <div class="tooltip tooltip--top bg-black">
                                            <?php echo Label::getLabel('LBL_REMOVE'); ?>
                                        </div>
                                    </a>
                                </td>
                            </tr>
                            <?php if ($recordType == AppConstant::GCLASS) { ?>
                                <tr style="display:none;" class="userListJs userListJs<?php echo $quiz['quilin_id'] ?>">
                                    <td colspan="4">
                                        <table class="table table--responsive table--aligned-middle table-inner table--condensed" id="">
                                            <thead>
                                                <tr class="title-row">
                                                    <th><?php echo Label::getLabel('LBL_ID'); ?></th>
                                                    <th><?php echo Label::getLabel('LBL_LEARNER'); ?></th>
                                                    <th><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                                                    <th><?php echo Label::getLabel('LBL_ACTION'); ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (isset($quiz['users']) && count($quiz['users']) > 0) { ?>
                                                    <?php foreach ($quiz['users'] as $user) { ?>
                                                        <tr>
                                                            <td>
                                                                <?php
                                                                $label = Label::getLabel('LBL_QZ{quiz-id}');
                                                                $str = str_pad($quiz['quilin_quiz_id'], 3, "0", STR_PAD_LEFT) . '-' . $user['quizat_id'];
                                                                echo str_replace('{quiz-id}', $str, $label);
                                                                ?>
                                                            </td>
                                                            <td>
                                                                <?php echo ucwords($user['user_first_name'] . ' ' . $user['user_last_name']); ?>
                                                            </td>
                                                            <td><?php echo $status[$user['quizat_status']] ?></td>
                                                            <td>
                                                                <?php
                                                                $target = '';
                                                                $url = "javascript:void(0);";
                                                                if ($user['quizat_status'] == QuizAttempt::STATUS_COMPLETED) {
                                                                    $target = "target='_blank'";
                                                                    $url = MyUtility::makeFullUrl('QuizReview', 'index', [$user['quizat_id']]);
                                                                }
                                                                ?>
                                                                <a <?php echo $target; ?> href="<?php echo $url ?>" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover <?php echo empty($target) ? 'btn--disabled' : '' ?>">
                                                                    <svg class="icon icon--cancel icon--small">
                                                                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#view"></use>
                                                                    </svg>
                                                                    <div class="tooltip tooltip--top bg-black">
                                                                        <?php echo Label::getLabel('LBL_VIEW'); ?>
                                                                    </div>
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="3"><?php echo Label::getLabel('LBL_NO_USER_AVAILABLE'); ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                    <?php } ?>
                    <tr class="noRecordJS" style="display:none;">
                        <td colspan="6"><?php $this->includeTemplate('_partial/no-record-found.php'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>