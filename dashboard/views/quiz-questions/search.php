<?php defined('SYSTEM_INIT') or die('Invalid Usage.');
$titleLbl = Label::getLabel('LBL_TITLE');
$typeLbl = Label::getLabel('LBL_TYPE');
$cateLbl = Label::getLabel('LBL_CATEGORY');
$subcateLbl = Label::getLabel('LBL_SUB_CATEGORY');
$types = Question::getTypes();
if (count($questions) < 1) { ?>
    <tr>
        <td colspan="5">
            <?php $this->includeTemplate('_partial/no-record-found.php'); ?>
        </td>
    </tr>
<?php
}
foreach ($questions as $question) { ?>
    <tr>
        <td>
            <div class="flex-cell">
                <div class="flex-cell__label"></div>
                <div class="flex-cell__content">
                    <label class="checkbox">
                        <input type="checkbox" name="questions[]" value="<?php echo $question['ques_id']; ?>">
                        <i class="input-helper"></i>
                    </label>
                </div>
            </div>
        </td>
        <td>
            <div class="flex-cell">
                <div class="flex-cell__label"><?php echo $titleLbl; ?></div>
                <div class="flex-cell__content">
                    <div style="max-width: 250px;">
                        <p class="mb-1 bold-600 color-black">
                            <?php echo $question['ques_title']; ?>
                        </p>
                    </div>
                </div>
            </div>
        </td>
        <td>
            <div class="flex-cell">
                <div class="flex-cell__label"><?php echo $typeLbl; ?></div>
                <div class="flex-cell__content">
                    <div style="max-width: 250px;"><?php echo $types[$question['ques_type']]; ?></div>
                </div>
            </div>
        </td>
        <td>
            <div class="flex-cell">
                <div class="flex-cell__label"><?php echo $cateLbl; ?></div>
                <div class="flex-cell__content"><?php echo $question['ques_cate_name']; ?></div>
            </div>
        </td>
        <td>
            <div class="flex-cell">
                <div class="flex-cell__label"><?php echo $subcateLbl; ?></div>
                <div class="flex-cell__content">
                    <?php echo !empty($question['ques_subcate_name']) ? $question['ques_subcate_name'] : '-'; ?>
                </div>
            </div>
        </td>
    </tr>
<?php } ?>