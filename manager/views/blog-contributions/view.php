<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_Contribution_Detail'); ?></h3>
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
                    <th style="width: 35%;"><?php echo Label::getLabel('LBL_FULL_NAME'); ?></th>
                    <td><?php echo ucfirst($data['bcontributions_author_first_name'] . ' ' . $data['bcontributions_author_last_name']); ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_EMAIL'); ?></th>
                    <td><?php echo $data['bcontributions_author_email']; ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_PHONE'); ?></th>
                    <td><?php echo $data['bcontributions_author_phone']; ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_POSTED_ON'); ?></th>
                    <td><?php echo MyDate::showDate($data['bcontributions_added_on'], true); ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                    <td><?php echo $statusArr[$data['bcontributions_status']]; ?></td>
                </tr>
                <?php if (!empty($fileData)) { ?>
                    <tr>
                        <th><?php echo Label::getLabel('LBL_ATTACHED_FILE'); ?></th>
                        <td><a target="_new" class="link-text" href="<?php echo MyUtility::makeUrl('Image', 'download', [Afile::TYPE_BLOG_CONTRIBUTION, $fileData['file_record_id']]); ?>"><?php echo $fileData['file_name']; ?></a></td>
                    </tr>
                <?php } ?>
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