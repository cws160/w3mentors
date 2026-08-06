<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title"><?php echo Label::getLabel('LBL_TEACHER_REQUEST_DETAIL'); ?></h3>
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
                    <th width="40%"><?php echo Label::getLabel('LBL_REFERENCE_NUMBER'); ?></th>
                    <td><?php echo $row['tereq_reference']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_REQUESTED_ON'); ?></th>
                    <td><?php echo MyDate::showDate($row['tereq_date'], true); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                    <td><?php echo TeacherRequest::getStatuses($row['tereq_status']); ?></td>
                </tr>
                <?php if ($row['tereq_comments'] != '') { ?>
                    <tr>
                        <th width="40%"><?php echo Label::getLabel('LBL_COMMENTS/REASON'); ?></th>
                        <td><?php echo nl2br($row['tereq_comments']); ?></td>
                    </tr>
                <?php } ?>
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
                    <th><?php echo Label::getLabel('LBL_PROFILE_PICTURE'); ?></th>
                    <td><img src="<?php echo MyUtility::makeUrl('Image', 'show', [$userImage['file_type'], $row['tereq_user_id'], Afile::SIZE_SMALL]); ?>" /></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_PHOTO_ID'); ?></th>
                    <td>
                        <?php
                        if (!empty($photoIdRow['file_record_id'])) {
                            echo '<a target="_blank" class="link-text" href="' . MyUtility::makeFullUrl('Image', 'download', [Afile::TYPE_TEACHER_APPROVAL_PROOF, $photoIdRow['file_record_id']]) . '" download>' . $photoIdRow['file_name'] . '</a>';
                        } else {
                            echo "-";
                        }
                        ?></td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_FIRST_NAME'); ?></th>
                    <td><?php echo $row['tereq_first_name']; ?>&nbsp;</td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_LAST_NAME'); ?></th>
                    <td><?php echo $row['tereq_last_name']; ?>&nbsp;</td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_GENDER'); ?></th>
                    <td><?php echo User::getGenderTypes()[$row['tereq_gender']]; ?>&nbsp;</td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_PHONE_NUMBER'); ?></th>
                    <td><?php echo $row['tereq_phone_code'] . ' ' . $row['tereq_phone_number']; ?>&nbsp;</td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_YOU_TUBE_VIDEO_LINK'); ?></th>
                    <td><?php echo $row['tereq_video_link']; ?> &nbsp;</td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_PROFILE_INFO'); ?></th>
                    <td><?php echo nl2br($row['tereq_biography'] ?? ''); ?> &nbsp; </td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_TREQUEST_TEACHING_LANGUAGE'); ?></th>
                    <td>
                        <ul class="">
                            <?php foreach ($teachLanguages as $lang) { ?>
                                <li><?php echo $lang; ?></li>
                            <?php } ?>
                        </ul>
                    </td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_SPOKEN_LANGUAGE'); ?></th>
                    <td>
                        <?php
                        foreach ($row['tereq_speak_langs'] as $key => $val) { ?>
                            <?php echo isset($speakLanguagesArr[$val]) ? ($speakLanguagesArr[$val] . ((!empty($row['tereq_slang_proficiency']) && isset($speakLanguageProfArr[$row['tereq_slang_proficiency'][$key]])) ? ' : ' . $speakLanguageProfArr[$row['tereq_slang_proficiency'][$key]] : '') . '<br/>') :  Label::getLabel('LBL_NA'); ?>
                        <?php } ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>

</div>