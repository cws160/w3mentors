<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
$statusClsArr = [
    User::TEACHER => ['cls' => 'is-inprogress', 'html' => '<div class="log-item__media"><span class="log-icon" data-toggle="tooltip"data-placement="top" data-original-title="In-progress"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5.463,4.433A10,10,0,0,1,20.19,17.74L17,12h3A8,8,0,0,0,6.46,6.228l-1-1.8ZM18.537,19.567A10,10,0,0,1,3.81,6.26L7,12H4a8,8,0,0,0,13.54,5.772Z"></path></svg></span></div>'],
    User::LEARNER => ['cls' => 'is-rejected', 'html' => '<div class="log-item__media"><span class="log-icon" data-toggle="tooltip"data-placement="top" data-original-title="Rejected"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12,22A10,10,0,1,1,22,12,10,10,0,0,1,12,22Zm0-2a8,8,0,1,0-8-8A8,8,0,0,0,12,20Zm-1-5h2v2H11Zm0-8h2v6H11Z"></path></svg></span></div>'],
    User::SUPPORT =>  ['cls' => 'is-approved', 'html' => '<div class="log-item__media"><span class="log-icon" data-toggle="tooltip"data-placement="top" data-original-title="Approved"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10,15.172l9.192-9.193,1.415,1.414L10,18,3.636,11.636,5.05,10.222Z"></path></svg></span></div>']
];
$logs = array_reverse($logs);
?>
<?php if (count($logs)) { ?>
    <div class="card">
        <div class="card-head">
            <div class="card-head-label">
                <h3 class="card-head-title"><?php echo Label::getLabel('LBL_ISSUE_LOGS'); ?></h3>
            </div>
        </div>
        <div class="form-edit-body">
            <div class="log-list">
                <?php foreach ($logs as $log) { ?>
                    <div class="log-item <?php echo $statusClsArr[$log['reislo_added_by_type']]['cls']; ?>">
                        <?php echo $statusClsArr[$log['reislo_added_by_type']]['html']; ?>
                        <div class="log-item__content">
                            <span class="log-date"><?php echo MyDate::showDate($log['reislo_added_on'], true); ?></span>
                            <span class="log-title">
                                <span class="log-author"> <?php echo $log['user_fullname']; ?> <?php echo '(' . Issue::getUserTypeArr($log['reislo_added_by_type']) . ')'; ?></span><span class="log-message"> <?php echo $actionArr[$log['reislo_action']]; ?></span>
                            </span>
                            <div style="display:block;">
                                <div class="log-comments">
                                    <div class="repeat-element">
                                        <div class="repeat-element__title"><?php echo Label::getLabel('LBL_COMMENTS'); ?></div>
                                        <div class="repeat-element__content"><?php echo nl2br($log['reislo_comment']); ?> </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php } ?>
                <div class="log-item <?php echo $statusClsArr[User::LEARNER]['cls']; ?>">
                    <?php echo $statusClsArr[User::LEARNER]['html']; ?>
                    <div class="log-item__content">
                        <span class="log-date"><?php echo MyDate::showDate($issue['repiss_reported_on'], true); ?></span>
                        <span class="log-title">
                            <span class="log-author"> <?php echo $issue['learner_full_name'];; ?> <?php echo '(' . Issue::getUserTypeArr(User::LEARNER) . ')'; ?></span><span class="log-message"> <?php echo $issue['repiss_title']; ?></span>
                        </span>
                        <div style="display:block;">
                            <div class="log-comments">
                                <div class="repeat-element">
                                    <div class="repeat-element__title"><?php echo Label::getLabel('LBL_COMMENTS'); ?></div>
                                    <div class="repeat-element__content"><?php echo nl2br($issue['repiss_comment']); ?> </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($order['order_discount_value'] > 0 || $order['order_reward_value'] > 0) { ?>
            <div class="card-footer">
                <span class="link-primary"><?php echo Label::getLabel('LBL_NOTE_REFUND_WITH_DISCOUNT_OR_REWARDS'); ?></span>
            </div>
        <?php  } ?>
    </div>
<?php } ?>
<?php
if ($issue['repiss_status'] == Issue::STATUS_ESCALATED) {
    $frm->developerTags['colClassPrefix'] = 'col-md-';
    $frm->developerTags['fld_default_col'] = 12;
    $frm->setFormTagAttribute('id', 'actionForm');
    $frm->setFormTagAttribute('class', 'form');
    $frm->setFormTagAttribute('onsubmit', 'setupAction(this); return(false);');
?>

    <div class="table-group">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_ACTION_FORM'); ?></h6>
        </div>
        <div class="table-group-body">
            <?php echo $frm->getFormHtml(); ?>
        </div>
    </div>
<?php } ?>