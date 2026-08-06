<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="card-head">
    <div class="card-head-label">
        <h3 class="card-head-title">
            <?php echo Label::getLabel('LBL_COURSE_APPROVAL_REQUEST_DETAIL'); ?>
        </h3>
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
                    <th width="40%"><?php echo Label::getLabel('LBL_REQUESTED_ON'); ?></th>
                    <td><?php echo MyDate::showDate($requestData['coapre_created'], true); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_STATUS'); ?></th>
                    <td><?php echo Course::getRequestStatuses($requestData['coapre_status']); ?></td>
                </tr>
                <?php if ($requestData['coapre_remark'] != '') { ?>
                    <tr>
                        <th width="40%"><?php echo Label::getLabel('LBL_COMMENTS'); ?></th>
                        <td><?php echo nl2br($requestData['coapre_remark']); ?></td>
                    </tr>
                <?php } ?>
            </table>
        </div>
    </div>
    <div class="table-group mb-1">
        <div class="table-group-head">
            <h6 class="mb-0"><?php echo Label::getLabel('LBL_COURSE_INFORMATION'); ?></h6>
        </div>
        <div class="table-group-body">
            <table class="table table-coloum">
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_TITLE'); ?></th>
                    <td><?php echo $requestData['coapre_title']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_SUB_TITLE'); ?></th>
                    <td><?php echo $requestData['coapre_subtitle']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_CATEGORY'); ?></th>
                    <td><?php echo empty($requestData['coapre_cate_name']) ? Label::getLabel('LBL_NA') : $requestData['coapre_cate_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_SUBCATEGORY'); ?></th>
                    <td><?php echo empty($requestData['coapre_subcate_name']) ? Label::getLabel('LBL_NA') : $requestData['coapre_subcate_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_DETAIL'); ?></th>
                    <td>
                        <div class=" editor-content iframe-content">
                            <iframe onload="resetIframe(this);" src="<?php echo MyUtility::makeUrl('CourseRequests', 'frame', [$requestData['coapre_id']]); ?>" style="border:none; width:100%; height:30px;"></iframe>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_PRICE'); ?></th>
                    <td><?php echo MyUtility::formatMoney($requestData['coapre_price']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_DURATION'); ?></th>
                    <td><?php echo CommonHelper::convertDuration($requestData['coapre_duration']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_LEVEL'); ?></th>
                    <td><?php echo Course::getCourseLevels($requestData['coapre_level']); ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_LANGUAGE'); ?></th>
                    <td><?php echo $requestData['coapre_clang_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_CERTIFICATE'); ?></th>
                    <td><?php echo AppConstant::getYesNoArr($requestData['coapre_certificate']); ?></td>
                </tr>
                <?php if ($requestData['coapre_certificate_type'] > 0 && $requestData['coapre_certificate'] == AppConstant::YES) { ?>
                    <tr>
                        <th width="40%"><?php echo Label::getLabel('LBL_COURSE_CERTIFICATE_TYPE'); ?></th>
                        <td><?php echo Certificate::getTypes($requestData['coapre_certificate_type'], false); ?></td>
                    </tr>
                <?php } ?>
                <?php if ($requestData['coapre_quilin_id'] > 0) { ?>
                    <tr>
                        <th width="40%"><?php echo Label::getLabel('LBL_COURSE_QUIZ'); ?></th>
                        <td><?php echo CommonHelper::renderHtml($requestData['coapre_quiz_title']); ?></td>
                    </tr>
                <?php } ?>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_TAGS'); ?></th>
                    <td>
                        <div class="course-tags">
                            <?php
                            if (empty($requestData['coapre_srchtags'])) {
                                echo Label::getLabel('LBL_NA');
                            } else {
                                foreach ($requestData['coapre_srchtags'] as $key => $tag) { ?>
                                    <span class="badge bg-fill-dark"><?php echo $tag ?></span> <?php
                                }
                            }
                            ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th><?php echo Label::getLabel('LBL_COURSE_PREVIEW_VIDEO'); ?></th>
                    <td>
                        <a class="link-text link-underline" href="<?php echo MyUtility::makeUrl('Courses', 'video', [$requestData['coapre_preview_video']]); ?>" target="_blank">
                            <?php echo Label::getLabel('LBL_VIEW'); ?>
                        </a>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_CONTENT'); ?></th>
                    <td>
                        <?php if (!empty($requestData['coapre_learnings'])) { ?>
                            <ul class="">
                                <?php foreach ($requestData['coapre_learnings'] as $content) { ?>
                                    <li><?php echo $content['coinle_response']; ?></li>
                                <?php } ?>
                            </ul>
                        <?php
                        } else {
                            echo Label::getLabel('LBL_NA');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_LEARNERS'); ?></th>
                    <td>
                        <?php if (!empty($requestData['coapre_learners'])) { ?>
                            <ul class="">
                                <?php foreach ($requestData['coapre_learners'] as $content) { ?>
                                    <li><?php echo $content['coinle_response']; ?></li>
                                <?php } ?>
                            </ul>
                        <?php
                        } else {
                            echo Label::getLabel('LBL_NA');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_COURSE_REQUIREMENTS'); ?></th>
                    <td>
                        <?php if (!empty($requestData['coapre_requirements'])) { ?>
                            <ul class="">
                                <?php foreach ($requestData['coapre_requirements'] as $content) { ?>
                                    <li><?php echo $content['coinle_response']; ?></li>
                                <?php } ?>
                            </ul>
                        <?php
                        } else {
                            echo Label::getLabel('LBL_NA');
                        }
                        ?>
                    </td>
                </tr>
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
                    <th width="40%"><?php echo Label::getLabel('LBL_FIRST_NAME'); ?></th>
                    <td><?php echo $requestData['user_first_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_LAST_NAME'); ?></th>
                    <td><?php echo empty($requestData['user_last_name']) ? '-' : $requestData['user_last_name']; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_GENDER'); ?></th>
                    <td><?php echo empty($requestData['user_gender']) ? '-' : User::getGenderTypes()[$requestData['user_gender']]; ?></td>
                </tr>
                <tr>
                    <th width="40%"><?php echo Label::getLabel('LBL_EMAIL'); ?></th>
                    <td><?php echo $requestData['user_email']; ?></td>
                </tr>
            </table>
        </div>
    </div>
</div>
