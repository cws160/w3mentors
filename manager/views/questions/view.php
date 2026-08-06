<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArr = AppConstant::getYesNoArr();
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_QUESTIONS_DETAIL'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_TITLE'); ?></th>
            <td><?php echo CommonHelper::renderHtml($questionData['ques_title']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_DESCRIPTION'); ?></th>
            <td>
                <?php echo ($questionData['ques_detail']) ? CommonHelper::renderHtml(nl2br($questionData['ques_detail'])) : Label::getLabel('LBL_NA'); ?>
            </td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_TYPE'); ?></th>
            <td>
            <?php echo Question::getTypes($questionData['ques_type']); ?>
            </td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_TEACHER_NAME'); ?></th>
            <td>
                <?php echo $questionData['teacher_first_name'] . ' ' . $questionData['teacher_last_name']; ?>
            </td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_CATEGORY'); ?></th>
            <td><?php echo CommonHelper::renderHtml($questionData['ques_cate_name']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_SUB_CATEGORY'); ?></th>
            <td>
                <?php echo empty($questionData['ques_subcate_name']) ? Label::getLabel('LBL_NA') : CommonHelper::renderHtml($questionData['ques_subcate_name']); ?>
            </td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
            <td><?php echo Question::getStatuses($questionData['ques_status']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_MARKS'); ?></th>
            <td><?php echo $questionData['ques_marks']; ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_HINT'); ?></th>
            <td>
                <?php echo ($questionData['ques_hint']) ? CommonHelper::renderHtml($questionData['ques_hint']) : Label::getLabel('LBL_NA'); ?>
            </td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ADDED_ON'); ?></th>
            <td><?php echo MyDate::showDate($questionData['ques_created'], true); ?></td>
        </tr>
        <?php if ($questionData['ques_type'] != Question::TYPE_TEXT) { ?>
            <tr>
                <th width="40%"><?php echo Label::getLabel('LBL_OPTIONS'); ?></th>
                <td>
                    <ul>
                        <?php foreach ($options as $option) : ?>
                            <li><?php echo $option['queopt_title']; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </td>
            </tr>
            <tr>
                <th width="40%"><?php echo Label::getLabel('LBL_ANSWERS'); ?></th>
                <td>
                    <?php if (isset($answers) && count($answers) > 0) :  ?>
                        <ul>
                            <?php foreach ($answers as $answerId) : ?>
                                <li><?php echo $options[$answerId]['queopt_title']; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>