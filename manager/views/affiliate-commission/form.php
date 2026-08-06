<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
if ($commissionId > 0) {
    $frm->getField('user_name')->addFieldTagAttribute('disabled', true);
}
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_AFFILIATE_COMMISSION_SETUP'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body">
    <?php echo $frm->getFormHtml(); ?>
</div>

<script>
    $("document").ready(function() {
        $("input[name='user_name']").autocomplete({
            'source': function(request, response) {
                fcom.updateWithAjax(fcom.makeUrl('AffiliateCommission', 'AutoCompleteJson'), {
                    keyword: request
                }, function(result) {
                    response($.map(result.data, function(item) {
                        return {
                            label: escapeHtml(item['full_name'] + ' (' + item['user_email'] + ')'),
                            value: item['user_id'],
                            name: item['full_name']
                        };
                    }));
                }, {
                    process: false
                });
            },
            'select': function(item) {
                $("input[name='afcomm_user_id']").val(item.value);
                $("input[name='user_name']").val(item.name);
            }
        });
        $("input[name='user_name']").keyup(function() {
            $("input[name='afcomm_user_id']").val('');
        });
    });
</script>