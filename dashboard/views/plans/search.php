<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($plans) == 0) {
    if ($planType == 0) {
        $this->includeTemplate('_partial/no-record-found.php');
        return;
    } else {
        $this->includeTemplate('_partial/no-record-found-plans.php');
        return;
    }
}
?>
<div class="table-scroll">
    <table class="table table--styled table--responsive">
        <tr class="title-row">
            <th><?php echo $titleLabel = Label::getLabel('LBL_Title'); ?></th>
            <th><?php echo $descriptionLabel = Label::getLabel('LBL_Description'); ?></th>
            <th><?php echo $levelLabel = Label::getLabel('LBL_Level'); ?></th>
            <th><?php echo $actionLabel = Label::getLabel('LBL_Actions'); ?></th>
        </tr>
        <?php foreach ($plans as $plan) { ?>
            <tr>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $titleLabel; ?> </div>
                        <div class="flex-cell__content">
                            <div style="max-width: 250px;">
                                <span class="bold-600"><?php echo $plan['plan_title']; ?></< /span>
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"> <?php echo $descriptionLabel; ?> </div>
                        <div class="flex-cell__content">
                            <div style="max-width: 250px;"><?php echo nl2br($plan['plan_detail']); ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $levelLabel ?></div>
                        <div class="flex-cell__content"><span class="badge color-secondary badge--curve"><?php echo Plan::getLevels($plan['plan_level']); ?></span></div>
                    </div>
                </td>
                <td>
                    <div class="flex-cell">
                        <div class="flex-cell__label"><?php echo $actionLabel; ?></div>
                        <div class="flex-cell__content">
                            <div class="actions-group">
                                <?php if (isset($post['listing_type']) && $post['listing_type'] == Plan::LISTING_TYPE) { ?>
                                    <a href="javascript:void(0);" onclick="assignPlanToClasses('<?php echo $post['recordId']; ?>', '<?php echo $plan['plan_id']; ?>', '<?php echo $planType; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                        <svg class="icon icon--issue icon--small">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#assign'; ?>"></use>
                                        </svg>
                                        <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_ASSIGN_PLAN'); ?></div>
                                    </a>
                                <?php } else { ?>
                                    <a href="javascript:void(0);" onclick="form('<?php echo $plan['plan_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                        <svg class="icon icon--issue icon--small">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#edit'; ?>"></use>
                                        </svg>
                                        <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_EDIT'); ?></div>
                                    </a>
                                    <a href="javascript:void(0);" onclick="remove('<?php echo $plan['plan_id']; ?>');" class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                        <svg class="icon icon--issue icon--small">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#trash'; ?>"></use>
                                        </svg>
                                        <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_DELETE'); ?></div>
                                    </a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        <?php } ?>
    </table>
</div>
<?php
$pagingArr = [
    'page' => $post['pageno'],
    $post['pageno'],
    'pageSize' => $post['pagesize'],
    'pageCount' => $pageCount,
    'recordCount' => $recordCount
];
if (isset($post['listing_type']) && $post['listing_type'] == Plan::LISTING_TYPE) {
    $pagingArr = $pagingArr + ['callBackJsFunc' => 'goToPlanSearchPage'];
}
$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmPlanSearchPaging']);
?>
</div>