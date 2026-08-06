<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($questions) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
$types = Question::getTypes();
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive table--aligned-middle">
        <tr class="title-row">
            <th><?php echo $titleLabel = Label::getLabel('LBL_TITLE'); ?></th>
            <th><?php echo $typeLabel = Label::getLabel('LBL_TYPE'); ?></th>
            <th><?php echo $cateLabel = Label::getLabel('LBL_CATEGORY'); ?></th>
            <th><?php echo $subcateLabel = Label::getLabel('LBL_SUBCATEGORY'); ?></th>
            <th><?php echo $statusLabel = Label::getLabel('LBL_STATUS'); ?></th>
            <th><?php echo $addedLabel = Label::getLabel('LBL_ADDED_ON'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_ACTIONS'); ?></th>
        </tr>
        <?php
        $naLabel = Label::getLabel('LBL_N/A');
        $statuses = Question::getStatuses();
        foreach ($questions as $question) {
        ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $titleLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo $question['ques_title']; ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $typeLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo $types[$question['ques_type']]; ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $cateLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo $question['ques_cate_name']; ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $subcateLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo !empty($question['ques_subcate_name']) ? $question['ques_subcate_name'] : '-'; ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $statusLabel; ?></div>
                        <div class="flex-cell__content">
                            <label class="switch switch--small">
                                <input class="switch__label" data-field-caption="" type="checkbox" name="ques_status" value="<?php echo $question['ques_status']; ?>" <?php echo ($question['ques_status'] == AppConstant::ACTIVE) ? 'checked="checked"' : '' ?> onchange="updateStatus('<?php echo $question['ques_id']; ?>', this)"> <i class="switch__handle bg-green"></i>
                            </label>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $addedLabel; ?></div>
                        <div class="flex-cell__content">
                            <?php echo MyDate::showDate($question['ques_created'], true); ?>
                        </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <a href="javascript:void(0);" onclick="questionForm('<?php echo $question['ques_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--edit icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#edit'; ?>"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_EDIT'); ?></div>
                            </a>
                            <a href="javascript:void(0);" onclick="remove('<?php echo $question['ques_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--issue icon--small">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#trash'; ?>"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_DELETE'); ?></div>
                            </a>
                        </div>
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php
$pagingArr = [
    'pageSize' => $post['pagesize'],
    'page' => $post['pageno'],
    'recordCount' => $recordCount,
    'pageCount' => ceil($recordCount / $post['pagesize'])
];
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
?>