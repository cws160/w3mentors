<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
echo $this->includeTemplate('tutorials/head-section.php', [
    'progress' => $progress,
    'progressId' => $progressId,
    'siteLangId' => $siteLangId,
    'siteUserId' => $siteUserId,
    'siteUserType' => $siteUserType,
    'course' => $course,
    'controllerName' => $controllerName,
    'action' => $actionName,
    'canDownloadCertificate' => $canDownloadCertificate,
]);
?>
<div class="body">
    <div class="d-flex justify-content-center">
        <div class="col-lg-4">
            <div class="message-display no-skin">
                <div class="message-display__media">
                    <img src="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/700x400.svg" alt="">
                </div>
                <h3 class="mb-3">
                    <?php echo Label::getLabel('LBL_CONGRATULATIONS') ?>
                    <?php echo ucwords($user['user_first_name'] . ' ' . $user['user_last_name']) ?>
                </h3>
                <h4 class="mb-2"><?php echo $heading ?? Label::getLabel('LBL_YOU_HAVE_COMPLETED_THE_COURSE_SUCCESSFULLY'); ?></h4>
                <?php if ($course['course_certificate'] == AppConstant::YES) { ?>
                    <p>
                        <?php echo Label::getLabel('LBL_NOW_YOU_ARE_ELIGIBLE_FOR_THE_CERTIFICATE'); ?>
                    </p>
                <?php } ?>
                <div class="d-flex justify-content-center">
                    <a href="javascript:void(0);" onclick="retake('<?php echo $progressId ?>');" class="btn btn--primary-bordered m-1">
                        <svg class="icon icon--png icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#retake"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_RETAKE_COURSE'); ?>
                    </a>
                    <a href="<?php echo MyUtility::makeUrl('Courses', '', [], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary-bordered m-1">
                        <svg class="icon icon--png icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#arrow-back"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_GO_BACK_TO_COURSE_LISTING'); ?>
                    </a>
                    <?php if ($canDownloadCertificate == true) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Certificates', 'index', [$progressId], CONF_WEBROOT_DASHBOARD); ?>" class="btn btn--primary m-1">
                            <svg class="icon icon--png icon--small me-2">
                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#download-icon"></use>
                            </svg>
                            <?php echo Label::getLabel('LBL_DOWNLOAD_CERTIFICATE'); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>