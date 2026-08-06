<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$yesNoArr = AppConstant::getYesNoArr();
?>

<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_COURSE_DETAIL'); ?>
        </h3>
    </div>
</div>
<div class="form-edit-body p-0">
    <div class="table-group mb-1">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_BASIC_DETAILS'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_TITLE'); ?></th>
                    <td><?php echo $courseData['course_title']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_SUB_TITLE'); ?></th>
                    <td><?php echo $courseData['course_subtitle']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_TEACHER_NAME'); ?></th>
                    <td><?php echo $courseData['teacher_first_name'] . ' ' . $courseData['teacher_last_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_DURATION'); ?></th>
                    <td><?php echo CommonHelper::convertDuration($courseData['course_duration']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_CATEGORY'); ?></th>
                    <td><?php echo $courseData['cate_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_SUB_CATEGORY'); ?></th>
                    <td><?php echo empty($courseData['subcate_name']) ? Label::getLabel('LBL_NA') : $courseData['subcate_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_LEVEL'); ?></th>
                    <td><?php echo Course::getCourseLevels($courseData['course_level']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_LANGUAGE'); ?></th>
                    <td><?php echo $courseData['course_clang_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                    <td><?php echo Course::getStatuses($courseData['course_status']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_PRICE'); ?></th>
                    <td><?php echo CourseUtility::formatMoney($courseData['course_price']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_PUBLISHED_ON'); ?></th>
                    <td>
                        <?php
                        $fmtDate = MyDate::formatDate($courseData['coapre_updated']);
                        echo MyDate::showDate($fmtDate, true);
                        ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_SECTIONS'); ?></th>
                    <td><?php echo $courseData['course_sections']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_LECTURES'); ?></th>
                    <td><?php echo $courseData['course_lectures']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_REVIEWS'); ?></th>
                    <td><?php echo $courseData['course_reviews']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_STUDENTS'); ?></th>
                    <td><?php echo $courseData['course_students']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_CERTIFICATE'); ?></th>
                    <td><?php echo $yesNoArr[$courseData['course_certificate']]; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_RATINGS'); ?></th>
                    <td><?php echo $courseData['course_ratings']; ?></td>
                </tr>
                <?php if ($courseData['course_certificate_type'] > 0 && $courseData['course_certificate'] == AppConstant::YES) { ?>
                    <tr>
                        <th width="40%"><?php echo Label::getLabel('LBL_COURSE_CERTIFICATE_TYPE'); ?></th>
                        <td><?php echo Certificate::getTypes($courseData['course_certificate_type'], false); ?></td>
                    </tr>
                <?php } ?>
                <?php if ($courseData['course_quilin_id'] > 0) { ?>
                    <tr>
                        <th width="40%"><?php echo Label::getLabel('LBL_COURSE_QUIZ'); ?></th>
                        <td><?php echo CommonHelper::renderHtml($courseData['course_quiz_title']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
    <div class="table-group">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_OTHER_DETAILS'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_PREVIEW_VIDEO'); ?></th>
                    <td>
                        <a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Courses', 'video', [$courseData['course_preview_video']]); ?>" target="_blank">
                            <?php echo Label::getLabel('LBL_VIEW'); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_DESCRIPTION'); ?></th>
                    <td>
                        <div class="editor-content iframe-content">
                            <iframe onload="resetIframe(this);" src="<?php echo MyUtility::makeUrl('Courses', 'frame', [$courseData['course_id']]); ?>" style="border:none; width:100%; height:30px;"></iframe>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>