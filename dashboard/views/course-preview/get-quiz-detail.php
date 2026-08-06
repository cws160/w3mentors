<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="row justify-content-between">
    <div class="col-xl-12">
        <div class="cms-container">
            <div class="iframe-content editorContentJs">
                <iframe onload="resetIframe(this);" src="<?php echo MyUtility::makeUrl('CoursePreview', 'frame', [$quiz['quilin_id'], 'quiz']); ?>" style="border:none; width:100%; height:30px;"></iframe>
            </div>
        </div>
    </div>
</div>
<div class="page-directions border-top">
    <div class="row justify-content-between">
        <div class="col-sm-6">
        </div>
        <div class="col-sm-auto">
            <div class="btn-actions">
                <a href="javascript:void(0);" last-record='0' class="btn btn--primary-bordered mr-1" onclick="loadLecture('<?php echo $lectureId ?>');">
                    <svg class="icon icon--arrow icon--xsmall me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#prev"></use>
                    </svg>
                    <?php echo Label::getLabel('LBL_PREV') ?>
                </a>
                <a href="javascript:void(0);" last-record='1' class="btn btn--primary-bordered ms-1 getNextJs btn--disabled">
                    <?php echo Label::getLabel('LBL_NEXT') ?>
                    <svg class="icon icon--arrow icon--xsmall ms-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#next"></use>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        getQuiz();
    });
</script>