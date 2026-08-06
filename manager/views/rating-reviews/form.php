<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form form_horizontal');
$frm->setFormTagAttribute('onsubmit', 'setup(this); return(false);');
$frm->developerTags['colClassPrefix'] = 'col-md-';
$frm->developerTags['fld_default_col'] = 12;
$overallRating = round($data["ratrev_overall"]);
?>

<div class="form-edit-body p-0">
    <div class="table-group mb-1">
        <div class="table-group-head">
            <h6 class="mb-0">
                <?php
                if ($data['ratrev_type'] == AppConstant::COURSE) {
                    echo Label::getLabel('LBL_COURSE_RATING_INFORMATION');
                } else {
                    echo Label::getLabel('LBL_TEACHER_RATING_INFORMATION');
                }
                ?>
            </h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <?php if ($data['ratrev_type'] == AppConstant::COURSE && !empty($data['course_name'])) { ?>
                    <tr>
                        <th><?php echo Label::getLabel('LBL_COURSE_NAME'); ?></th>
                        <td><?php echo $data['course_name']; ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_REVIEWED_BY'); ?></th>
                    <td><?php echo $data['learner_first_name'] . ' ' . $data['learner_last_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_RATING'); ?></th>
                    <td>
                        <ul class="rating list-inline">
                            <?php for ($j = 1; $j <= 5; $j++) { ?>
                                <li class="<?php echo $j <= $overallRating ? "active" : "in-active" ?>" style="padding: 0px;">
                                    <svg xml:space="preserve" enable-background="new 0 0 70 70" viewBox="0 0 70 70" height="18px" width="18px" y="0px" x="0px" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns="http://www.w3.org/2000/svg" id="Layer_1" version="1.1">
                                        <g>
                                            <path d="M51,42l5.6,24.6L35,53.6l-21.6,13L19,42L0,25.4l25.1-2.2L35,0l9.9,23.2L70,25.4L51,42z M51,42" fill="<?php echo $j <= $overallRating ? "#ff3a59" : "#474747" ?>" />
                                        </g>
                                    </svg>
                                </li>
                            <?php } ?>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_REVIEW_TITLE'); ?></th>
                    <?php $findKeywordStr = ''; ?>
                    <td><?php echo nl2br($data['ratrev_title']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_REVIEW_COMMENTS'); ?></th>
                    <?php $findKeywordStr = ''; ?>
                    <td><?php echo nl2br($data['ratrev_detail']); ?></td>
                </tr>
            </table>
        </div>
    </div>
    <div class="table-group">
        <div class="table-group-head">
            <h6 class="mb-0">
                <?php echo Label::getLabel('LBL_CHANGE_STATUS'); ?>
            </h6>
        </div>
        <div class="table-group-body">
            <?php if (empty($data['teacher_deleted'])) { ?>
                <?php echo $frm->getFormHtml(); ?>
            <?php } ?>
        </div>
    </div>
</div>