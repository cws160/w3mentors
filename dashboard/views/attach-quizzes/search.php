<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($quizzes) == 0) {
?>
    <tr>
        <td colspan="3">
            <?php $this->includeTemplate('_partial/no-record-found.php'); ?>
        </td>
    </tr>
<?php return;
}
$titleLbl = Label::getLabel('LBL_TITLE');
$typeLbl = Label::getLabel('LBL_TYPE');
$actionLabel = Label::getLabel('LBL_ACTION');
?>
<?php
$statuses = Question::getStatuses();
foreach ($quizzes as $quiz) {
?>
    <tr>
        <?php if ($post['record_type'] != AppConstant::COURSE) { ?>
            <td>
                <label class="checkbox">
                    <input type="checkbox" name="quilin_quiz_id[]" value="<?php echo $quiz['quiz_id']; ?>">
                    <i class="input-helper"></i>
                </label>
            </td>
        <?php } ?>
        <td>
            <div class="flex-cell">
                <div class="flex-cell__label"><?php echo $titleLbl; ?></div>
                <div class="flex-cell__content">
                    <?php echo $quiz['quiz_title']; ?>
                </div>
            </div>
        </td>
        <td>
            <div class="flex-cell">
                <div class="flex-cell__label"><?php echo $typeLbl; ?></div>
                <div class="flex-cell__content">
                    <?php echo $types[$quiz['quiz_type']]; ?>
                </div>
            </div>
        </td>
        <?php if ($post['record_type'] == AppConstant::COURSE) { ?>
            <td>
                <div class="flex-cell">
                    <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                    <div class="flex-cell__content">
                        <a href="javascript:void(0);" data-title="<?php echo $quiz['quiz_title']; ?>" onclick="setQuiz('<?php echo $quiz['quiz_id']; ?>', this)" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                            <svg class="icon icon--issue icon--small">
                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#assign'; ?>"></use>
                            </svg>
                            <div class="tooltip tooltip--top bg-black">
                                <?php echo Label::getLabel('LBL_ATTACH_QUIZ'); ?>
                            </div>
                        </a>
                    </div>
                </div>
            </td>
        <?php } ?>
    </tr>
<?php } ?>
<?php if ($post['record_type'] == AppConstant::COURSE) { ?>

<?php } ?>