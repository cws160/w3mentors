<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_COURSE_REFUND_REQUEST_DETAIL'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <div class="table-group mb-1">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_REQUEST_INFORMATION'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_REQUESTED_ON'); ?></th>
                    <td><?php echo MyDate::showDate($requestData['corere_created'], true); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                    <td><?php echo Course::getRefundStatuses($requestData['corere_status']); ?></td>
                </tr>
                <?php if ($requestData['corere_remark'] != '') { ?>
                    <tr>
                        <th width="40%"><?php echo Label::getLabel('LBL_COMMENTS'); ?></th>
                        <td><?php echo nl2br($requestData['corere_remark']); ?></td>
                    </tr>
                <?php } ?>
                <?php if ($requestData['corere_comment'] != '') { ?>
                    <tr>
                        <th width="40%"><?php echo Label::getLabel('LBL_DECLINE_REASON/COMMENTS'); ?></th>
                        <td><?php echo nl2br($requestData['corere_comment']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
    <div class="table-group mb-1">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_COURSE_INFORMATION'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_TITLE'); ?></th>
                    <td><?php echo $requestData['course_title']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_PURCHASED_PRICE'); ?></th>
                    <td><?php echo CourseUtility::formatMoney($requestData['ordcrs_amount']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_COMPLETED_PROGRESS'); ?></th>
                    <td>
                        <?php echo (($requestData['crspro_status'] == CourseProgress::COMPLETED) ? 100 : $requestData['crspro_progress'] ). '%'; ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_DURATION'); ?></th>
                    <td><?php echo CommonHelper::convertDuration($requestData['course_duration']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                    <td><?php echo Course::getStatuses($requestData['course_status']); ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="table-group">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_PROFILE_INFORMATION'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_FIRST_NAME'); ?></th>
                    <td><?php echo $requestData['user_first_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_LAST_NAME'); ?></th>
                    <td><?php echo empty($requestData['user_last_name']) ? '-' : $requestData['user_last_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_GENDER'); ?></th>
                    <td><?php echo !empty($requestData['user_gender']) ? User::getGenderTypes()[$requestData['user_gender']] : '-'; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_EMAIL'); ?></th>
                    <td><?php echo $requestData['user_email']; ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>