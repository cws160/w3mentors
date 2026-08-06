<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$reprtStatusArr = ForumQuestion::getReportStatusArray();
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_REPORT_INFORMATION'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REPORT_TITLE'); ?></th>
            <td><?php echo $records['fquerep_title']; ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_COMMENT'); ?></th>
            <td><?php echo nl2br($records['fquerep_comments']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REPORTED_BY'); ?></th>
            <td><?php echo $records['username']; ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REPORTED_ON'); ?></th>
            <td><?php echo MyDate::showDate($records['fquerep_added_on'], true); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ACTION'); ?></th>
            <td><?php echo $reprtStatusArr[$records['fquerep_status']]; ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ADMIN_COMMENT'); ?></th>
            <td>
                <?php
                if (0 < strlen($records['fquerep_admin_comments'])) {
                    echo nl2br($records['fquerep_admin_comments']);
                } else {
                    echo Label::getLabel('LBL_Na');
                }
                ?>
            </td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_ACTION_ON'); ?></th>
            <td>
                <?php
                $updatedTime = strtotime($records['fquerep_updated_on']);
                if ($updatedTime > 0) {
                    echo MyDate::showDate($records['fquerep_updated_on'], true);
                } else {
                    echo Label::getLabel('LBL_Na');
                }
                ?>
            </td>
        </tr>
    </table>
</div>