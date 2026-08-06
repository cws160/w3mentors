<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_COURSE_EDIT_REQUEST_DETAIL'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REQUESTED_ON'); ?></th>
            <td><?php echo MyDate::showDate($requestData['coedre_created'], true); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
            <td><?php echo Course::getEditRequestStatuses($requestData['coedre_status']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_REQUEST_REASON'); ?></th>
            <td><?php echo nl2br($requestData['coedre_reason']); ?></td>
        </tr>
    </table>
</div>