<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$frm->addFormTagAttribute('class', 'form height-100');
$btnSubmit = $frm->getField('btn_submit');
$btnSubmit->setFieldTagAttribute('class', 'btn btn--primary btnNextJs');
$btnSkip = $frm->getField('btn_skip');
if (count($attemptedQues) == $question['qulinqu_order']) {
    $frm->addFormTagAttribute('onsubmit', 'save(this); return false;');
    $btnSubmit->value = Label::getLabel('LBL_SAVE');
} else {
    $frm->addFormTagAttribute('onsubmit', 'saveAndNext(this); return false;');
    $btnSkip->setFieldTagAttribute('onclick', 'skipAndNext(' . $data['quizat_id'] . ');');
    $btnSkip->setFieldTagAttribute('class', 'btn btn--transparent border-0 color-black style-italic ');
}
$fld = $frm->getField('ques_answer');
?>
<div class="flex-layout__large">
    <?php echo $frm->getFormTag(); ?>
    <div class="box-view box-view--space box-flex">
        <div class="box-view__head mb-4">
            <small class="style-italic"><?php echo Label::getLabel('LBL_MARKS:') . ' ' . $question['qulinqu_marks']; ?></small>
            <h4 class="mb-2">
                <?php echo str_replace('{number}', $question['qulinqu_order'], Label::getLabel('LBL_Q{number}.')) . ' ' . $question['qulinqu_title']; ?>
            </h4>
            <p><?php echo CommonHelper::renderHtml(nl2br($question['qulinqu_detail'])); ?></p>
        </div>
        <div class="box-view__body">
            <div class="option-list">
                <?php
                if ($question['qulinqu_type'] != Question::TYPE_TEXT && count($options) > 0) {
                    $type = ($question['qulinqu_type'] == Question::TYPE_SINGLE) ? 'radio' : 'checkbox';
                    foreach ($options as $option) {
                ?>
                        <label class="option">
                            <input type="<?php echo $type; ?>" name="ques_answer[]" class="option__input" value="<?php echo $option['queopt_id']; ?>" <?php echo (in_array($option['queopt_id'], $fld->value)) ? 'checked="checked"' : ''; ?>>
                            <span class="option__item">
                                <span class="option__icon">
                                    <?php if ($question['qulinqu_type'] == Question::TYPE_MULTIPLE) { ?>
                                        <svg class="icon-correct" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M4 3h16a1 1 0 0 1 1 1v16a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zm7.003 13l7.07-7.071-1.414-1.414-5.656 5.657-2.829-2.829-1.414 1.414L11.003 16z"></path>
                                        </svg>
                                        <svg class="icon-incorrect" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M-2196-558h-16a1,1,0,0,1-1-1v-16a1,1,0,0,1,1-1h16a1,1,0,0,1,1,1v16A1,1,0,0,1-2196-558Zm-8.125-8.042h0l3.791,3.791,1.083-1.084-3.792-3.792,3.792-3.792-1.083-1.084-3.792,3.793-3.792-3.793-1.083,1.084,3.792,3.792-3.792,3.792,1.083,1.084,3.791-3.791Z" transform="translate(2216 579)"></path>
                                        </svg>
                                    <?php } else { ?>
                                        <svg class="icon-correct" xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm-.997-6l7.07-7.071-1.414-1.414-5.656 5.657-2.829-2.829-1.414 1.414L11.003 16z" />
                                        </svg>
                                        <svg class="icon-incorrect" xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M12 22C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-11.414L9.172 7.757 7.757 9.172 10.586 12l-2.829 2.828 1.415 1.415L12 13.414l2.828 2.829 1.415-1.415L13.414 12l2.829-2.828-1.415-1.415L12 10.586z" />
                                        </svg>
                                    <?php } ?>
                                </span>
                                <span class="option__value"><?php echo $option['queopt_title'] ?></span>
                            </span>
                        </label>
                    <?php } ?>
                <?php } else { ?>
                    <?php echo $fld->getHtml(); ?>
                <?php } ?>
            </div>
            <?php if (!empty($question['qulinqu_hint'])) { ?>
                <div class="option-hint">
                    <span class="d-inline-flex align-items-center">
                        <span class="option-hint__title d-inline-flex align-items-center me-1">
                            <strong class="d-inline-flex align-items-center">
                                <svg class="icon icon--dashboard me-2 icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#hint'; ?>"></use>
                                </svg>
                                <?php echo Label::getLabel('LBL_HINT:'); ?>
                            </strong>
                        </span>
                        <span class="option-hint__content"><?php echo CommonHelper::renderHtml($question['qulinqu_hint']) ?></span>
                    </span>
                </div>
            <?php } ?>
        </div>
        <div class="box-view__footer">
            <div class="box-actions form">
                <?php if ($question['qulinqu_order'] > 1) { ?>
                    <div class="box-actions__cell box-actions__cell-left">
                        <input type="button" name="back" value="<?php echo Label::getLabel('LBL_BACK') ?>" onclick="previous('<?php echo $data['quizat_id'] ?>')" class="btn btn--bordered-primary btnPrevJs">
                    </div>
                <?php } ?>
                <div class="box-actions__cell box-actions__cell-right">
                    <?php
                    if ($question['qulinqu_order'] < count($attemptedQues)) {
                        echo $btnSkip->getHtml();
                    }

                    echo $frm->getFieldHtml('ques_type');
                    echo $frm->getFieldHtml('ques_id');
                    echo $frm->getFieldHtml('ques_attempt_id');
                    echo $frm->getFieldHtml('quatqu_id');
                    echo $btnSubmit->getHtml();
                    ?>
                </div>
            </div>
        </div>
    </div>
    </form>
    <?php echo $frm->getExternalJs(); ?>
</div>
<div class="flex-layout__small">
    <div class="box-view box-view--space box-flex">
        <div class="box-view__head mb-3">
            <h4><?php echo Label::getLabel('LBL_ATTEMPT_SUMMARY'); ?></h4>
        </div>
        <div class="box-view__body">
            <nav class="attempt-list">
                <ul>
                    <?php foreach ($attemptedQues as $quest) {
                        $class = "";
                        $action = "onclick=\"getByQuesId('" . $data['quizat_id'] . "', '" . $quest['qulinqu_id'] . "')\";";
                        if (!empty($quest['quatqu_id'])) {
                            $class = "is-visited";
                        }
                        if ($data['quizat_qulinqu_id'] == $quest['qulinqu_id']) {
                            $class .= " is-current";
                            $action = "";
                        }
                    ?>
                        <li class="<?php echo $class; ?>">
                            <a href="javascript:void(0);" class="attempt-action" <?php echo $action; ?>>
                                <?php echo $quest['qulinqu_order'] ?>
                            </a>
                        </li>
                    <?php } ?>
                </ul>
            </nav>
        </div>
        <div class="box-view__footer">
            <div class="legends mb-5">
                <h6><?php echo Label::getLabel('LBL_LEGEND'); ?></h6>
                <div class="legend-list">
                    <ul>
                        <li class="is-answered"><span class="legend-list__item">
                                <?php echo Label::getLabel('LBL_ANSWERED'); ?>
                            </span></li>
                        <li class="is-skip"><span class="legend-list__item">
                                <?php echo Label::getLabel('LBL_NOT_ANSWERED'); ?>
                            </span></li>
                    </ul>
                </div>
            </div>
            <div class="box-actions form">
                <input type="button" value="<?php echo Label::getLabel('LBL_SUBMIT_&_FINISH') ?>" class="btn btn--bordered-primary btn--block" onclick="saveAndFinish();">
            </div>
        </div>
    </div>
</div>