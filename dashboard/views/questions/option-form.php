<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$titleFld = $frm->getField("queopt_title[]");
$answerFld = $frm->getField('ques_answer[]');
if ($type == Question::TYPE_SINGLE) {
    $quesType = 'radio';
} elseif ($type == Question::TYPE_MULTIPLE) {
    $quesType = 'checkbox';
}

$requirements = [];
if ($titleFld->requirements()->isRequired()) {
    $requirements["required"] = true;
}
$length = $titleFld->requirements()->getLength();
if (!empty($length)) {
    $requirements["lengthrange"] = $length;
}
?>
<div class="sortableLearningJs">
    <?php if (count($options) > 0) { ?>
        <?php foreach ($options as $key => $option) { ?>
            <div class="d-flex optionsRowJs align-items-center mb-3 gap-3">
                <div>
                    <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent btn--bordered color-gray-400 cursor-move sortHandlerJs">
                        <svg class="svg-icon" xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24">

                            <g transform="translate(-9 -7)">
                                <rect transform="translate(9 9)" fill="none"></rect>
                                <g transform="translate(15 12)">
                                    <g transform="translate(-540 -436)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-534 -436)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-540 -430)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-534 -430)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-540 -424)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-534 -424)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </a>
                </div>
                <div class="col-sm-9">
                    <div class="field-wraper">
                        <div class="field_cover">
                            <input data-field-caption="<?php echo $titleFld->getCaption(); ?>" placeholder="<?php echo $titleFld->getCaption(); ?>" data-fatreq='<?php echo json_encode($requirements); ?>' type="text" name="queopt_title[<?php echo $option['queopt_id']; ?>]" value="<?php echo $option['queopt_title']; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="field-wraper">
                        <label class="switch-group d-flex align-items-center justify-content-between">
                            <input class="me-2" data-field-caption="<?php echo $answerFld->getCaption(); ?>" data-fatreq="{&quot;required&quot;:false}" type="<?php echo $quesType; ?>" name="ques_answer[]" value="<?php echo $option['queopt_id']; ?>" <?php echo (in_array($option['queopt_id'], $answers)) ? 'checked="checked"' : ''; ?>>
                            <span class="switch-group__label">
                                <?php echo $answerFld->getCaption(); ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
        <?php } ?>
    <?php } else { ?>
        <?php
        $i = 1;
        while ($count > 0) { ?>
            <div class="d-flex optionsRowJs align-items-center mb-3 gap-3">
                <div>
                    <a href="javascript:void(0)" class="btn btn--equal btn--sort btn--transparent color-gray-400 btn--bordered cursor-move sortHandlerJs">
                        <svg class="svg-icon" xmlns="https://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <g transform="translate(-9 -7)">
                                <rect transform="translate(9 9)" fill="none"></rect>
                                <g transform="translate(15 12)">
                                    <g transform="translate(-540 -436)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-534 -436)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-540 -430)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-534 -430)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-540 -424)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                    <g transform="translate(-534 -424)">
                                        <circle cx="2" cy="2" r="2" transform="translate(540 436)"></circle>
                                    </g>
                                </g>
                            </g>
                        </svg>
                    </a>
                </div>
                <div class="col-sm-9">
                    <div class="field-wraper">
                        <div class="field_cover">
                            <input data-field-caption="<?php echo $titleFld->getCaption(); ?>" placeholder="<?php echo $titleFld->getCaption(); ?>" data-fatreq='<?php echo json_encode($requirements); ?>' type="text" name="queopt_title[<?php echo $i; ?>]" value="<?php echo $titleFld->value; ?>">
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="field-wraper">
                        <label class="switch-group d-flex align-items-center justify-content-between">
                            <input class="me-2" data-field-caption="<?php echo $answerFld->getCaption(); ?>" data-fatreq="{&quot;required&quot;:false}" type="<?php echo $quesType; ?>" name="ques_answer[]" value="<?php echo $i; ?>" <?php echo ($i == 1 && $type == Question::TYPE_SINGLE) ? 'checked="checked"' : ''; ?>>
                            <span class="switch-group__label">
                                <?php echo $answerFld->getCaption(); ?>
                            </span>
                        </label>
                    </div>
                </div>
            </div>
    <?php
            $i++;
            $count--;
        }
    }
    ?>
</div>
<script type="text/javascript">
    $(function() {
        $(".sortableLearningJs").sortable({
            handle: ".sortHandlerJs",
            containment: ".more-container-js"
        });
    });
</script>