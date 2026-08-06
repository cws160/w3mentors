<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_QUESTION_Detail'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_Added_By'); ?></th>
            <td><?php echo $records['username']; ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_Added_On'); ?></th>
            <td><?php echo MyDate::showDate($records['fque_added_on'], true); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
            <td><?php echo $statusArr[$records['fque_status']]; ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_Title'); ?></th>
            <td><?php echo CommonHelper::renderHtml($records['fque_title']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_Description'); ?></th>
            <td><?php echo CommonHelper::renderHtml($records['fque_description']); ?></td>
        </tr>
        <tr>
            <th width="40%"><?php echo Label::getLabel('LBL_Binded_Tags'); ?></th>
            <td>
                <div class="tags">
                    <?php
                    if (1 > count($queTags)) {
                        echo Label::getLabel('LBL_NA');
                    } else {
                        foreach ($queTags as $key => $tag) {
                            ?>
                            <a href="javascript:void(0);" class="badge bg-fill-dark mb-1"><?php echo $tag; ?></a>
                            <?php
                        }
                    }
                    ?>
                </div>
            </td>
        </tr>
    </table>
</div>