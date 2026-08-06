<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
?>
<div class="container container--fixed">
    <div class="page__head">
        <a href="<?php echo MyUtility::makeUrl('Courses') ?>" class="page-back">
            <svg class="icon icon--back me-2">
                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#arrow-back"></use>
            </svg>
            <?php echo Label::getLabel('LBL_BACK_TO_COURSES'); ?>
        </a>
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-8">
                <h1 id="mainHeadingJs">
                    <?php
                    if ($courseId > 0) {
                        echo $courseTitle;
                    } else {
                        echo Label::getLabel('LBL_MANAGE_COURSE_DETAILS');
                    }
                    ?>
                </h1>
                <p class="m-0"><?php echo Label::getLabel('LBL_MANAGE_COURSE_SUB_HEADING'); ?></p>
            </div>
            <div class="col-sm-auto"></div>
        </div>
    </div>
    <div class="page__body" id="pageContentJs"></div>
    <?php
    $label = str_replace('{size}', MyUtility::convertBitesToMb($videoSize), Label::getLabel('LBL_FILE_SIZE_SHOULD_BE_LESS_THEN_{size}_MB'));
    ?>
    <script>
        var courseId = "<?php echo $courseId ?>";
        var siteLangId = "<?php echo $siteLangId; ?>";
        videoSize = "<?php echo $videoSize; ?>";
        videoSizeLbl = "<?php echo $label; ?>";
    </script>