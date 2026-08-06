<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_VIEW_USER_DETAIL'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tbody>
            <tr>
                <th><?php echo Label::getLabel('LBL_NAME'); ?></th>
                <td><?php echo $data['user_full_name']; ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_EMAIL'); ?></th>
                <td><?php echo $data['user_email']; ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_TIMEZONE'); ?></th>
                <td><?php echo MyDate::formatTimeZoneLabel($data['user_timezone']); ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_REG_DATE'); ?></th>
                <td><?php echo MyDate::showDate($data['user_created'], true); ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_PHONE_NO'); ?></th>
                <td><?php echo $data['user_phone_code'] . ' ' . $data['user_phone_number']; ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_COUNTRY'); ?></th>
                <td><?php echo $data['country_name']; ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_BIOGRAPHY'); ?></th>
                <td> <?php echo nl2br($data['user_biography'] ?? ''); ?></td>
            </tr>
        </tbody>
    </table>
</div>