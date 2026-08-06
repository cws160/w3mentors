<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Comment_Details'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <div class="table-group mb-1">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_Details'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th style="width: 35%;"><?php echo Label::getLabel('LBL_Full_Name'); ?></th>
                    <td><?php echo ucfirst($data['bpcomment_author_name']); ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_Email'); ?></th>
                    <td><?php echo $data['bpcomment_author_email']; ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_Posted_On'); ?></th>
                    <td><?php echo MyDate::showDate($data['bpcomment_added_on'], true); ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_Blog_Post_Title'); ?></th>
                    <td><?php echo $data['post_title']; ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_Comment'); ?></th>
                    <td><?php echo nl2br($data['bpcomment_content']); ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_User_IP'); ?></th>
                    <td><?php echo $data['bpcomment_user_ip']; ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_User_Agent'); ?></th>
                    <td><?php echo $data['bpcomment_user_agent']; ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="table-group">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_Update_Status'); ?></h6>
        </div>
        <div class="table-group-body">
            <?php
            $frm->setFormTagAttribute('class', 'form form_horizontal');
            $frm->developerTags['colClassPrefix'] = 'col-sm-';
            $frm->developerTags['fld_default_col'] = '12';
            $frm->setFormTagAttribute('onsubmit', 'updateStatus(this); return(false);');
            echo $frm->getFormHtml();
            ?>
        </div>
    </div>
</div>