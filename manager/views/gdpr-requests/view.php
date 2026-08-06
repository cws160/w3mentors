<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$fld = $frm->getField('gdpreq_status');
$fld->setFieldTagAttribute('onChange', 'showHideCommentBox(this.value);');

$fld = $frm->getField('gdpreq_comment');
$fld->setWrapperAttribute('id', 'remarkField');
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_REQUEST_DETAIL'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tbody>
            <tr>
                <th><?php echo Label::getLabel('LBL_Username'); ?>:</th>
                <td><?php echo $data['user_first_name'] . ' ' . $data['user_last_name']; ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_Request_Added'); ?>:</th>
                <td><?php echo MyDate::showDate($data['gdpreq_added_on'], true); ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_REQUEST_MODIFIED'); ?>:</th>
                <td><?php echo ($data['gdpreq_status'] == GdprRequest::STATUS_PENDING) ? Label::getLabel('LBL_NA') : MyDate::showDate($data['gdpreq_updated_on'], true); ?></td>
            </tr>
            <tr>
                <th><?php echo Label::getLabel('LBL_Erasure_Request_Reason'); ?>:</th>
                <td><?php echo nl2br($data['gdpreq_reason']); ?></td>
            </tr>
        </tbody>
    </table>
    <div class="table-group">
        <?php if ($data['gdpreq_status'] == GdprRequest::STATUS_PENDING) { ?>
            <div class="table-group-head">
                <h6 class="mb-0">
                    <?php echo Label::getLabel('LBL_Change_Status'); ?>
                </h6>
            </div>
            <div class="table-group-body">
                <?php
                $frm->setFormTagAttribute('class', 'form form_horizontal');
                $frm->setFormTagAttribute('onsubmit', 'updateStatus(this); return false;');
                $frm->developerTags['colClassPrefix'] = 'col-sm-';
                $frm->developerTags['fld_default_col'] = '10';
                echo $frm->getFormHtml();
                ?>
            </div>

        <?php } ?>
    </div>
</div>