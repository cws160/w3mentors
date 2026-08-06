<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_USER_ADDRESSES'); ?></h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <table class="table table-coloum">
        <tbody>
            <tr>
                <th><?php echo Label::getLabel('LBL_Sr_No.'); ?></th>
                <th><?php echo Label::getLabel('LBL_Addresses'); ?></th>
            </tr>
            <?php $srn = 1; ?>
            <?php foreach ($addresses as $key => $value) { ?>
                <tr class="<?php echo ($value['usradd_default'] == AppConstant::YES) ? 'table-secondary' : ''; ?>">
                    <td><?php echo $srn++; ?></td>
                    <td>
                        <?php echo UserAddresses::format($value); 
                        if(($value['usradd_default'] == AppConstant::YES)) { ?>
                         <span class="link-primary"> (<?php  echo  Label::getLabel('LBL_DEFAULT_ADDRESS') ?>)</span>
                         <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            <?php if (count($addresses) == 0) { ?>
                <tr>
                    <td colspan="2">
                        <?php echo Label::getLabel('LBL_NO_RECORDS_FOUND'); ?>
                    </td>
                </tr>
            <?php  } ?>

        </tbody>
    </table>
</div>