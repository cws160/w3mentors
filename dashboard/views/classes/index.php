<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'search(this);return false;');
if ($siteUserType == User::TEACHER) {
    $statusFld = $frm->getField('grpcls_status');
} elseif ($siteUserType == User::LEARNER) {
    $statusFld = $frm->getField('ordcls_status');
}
$viewFld = $frm->getField('view');
$keywordFld = $frm->getField('keyword');
$keywordFld->addFieldTagAttribute('placeholder', Label::getLabel('LBL_KEYWORD'));
$durationFld = $frm->getField('grpcls_duration');
$startdateFld = $frm->getField('grpcls_start_datetime');
$startdateFld->addFieldtagAttribute('id', 'start_datetime');
$startdateFld->addFieldtagAttribute('placeholder', Label::getLabel('LBL_START_DATE'));
$enddateFld = $frm->getField('grpcls_end_datetime');
$enddateFld->addFieldtagAttribute('id', 'end_datetime');
$enddateFld->addFieldtagAttribute('placeholder', Label::getLabel('LBL_END_TIME'));
$offlineClsFld = $frm->getField('grpcls_offline');
$offlineClsFld->developerTags['cbHtmlBeforeCheckbox'] = '<span class="checkbox">';
$offlineClsFld->developerTags['cbHtmlAfterCheckbox'] = '<i class="input-helper"></i></span>';
$frm->getField('btn_clear')->addFieldTagAttribute('onClick', 'clearSearch();');
?>
<script>
    var endClassConfirmMsg = "<?php echo CommonHelper::htmlEntitiesDecode(Label::getLabel('LBL_END_CLASS_CONFIRM_MSG')); ?>";
</script>
<!-- [ PAGE ========= -->
<div class="container container--fixed">
    <div class="page__head">
        <div class="row align-items-center justify-content-between">
            <div class="col-sm-6">
                <h1><?php echo Label::getLabel('LBL_MANAGE_CLASSES'); ?></h1>
            </div>
            <div class="col-sm-auto">
                <div class="buttons-group d-flex align-items-center gap-2">
                    <a href="javascript:void(0)" class="btn btn--secondary slide-toggle-js d-flex d-sm-none">
                        <svg class="icon icon--search icon--small me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_SEARCH'); ?>
                    </a>
                    <?php if ($siteUserType == User::TEACHER) { ?>
                        <a href="javascript:void(0);" onclick="addForm(0);" class="btn color-secondary btn--bordered">
                            <svg class="icon icon--add icon--small me-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24">
                                <path d="M11 11V7h2v4h4v2h-4v4h-2v-4H7v-2h4zm1 11C6.477 22 2 17.523 2 12S6.477 2 12 2s10 4.477 10 10-4.477 10-10 10zm0-2a8 8 0 1 0 0-16 8 8 0 0 0 0 16z"></path>
                            </svg>
                            <?php echo Label::getLabel('LBL_ADD_CLASS'); ?>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
    <div class="page__body">
        <?php
        if (!empty($upcomingClass)) {
            $classId = ($siteUserType == User::LEARNER) ? $upcomingClass['ordcls_id'] : $upcomingClass['grpcls_id'];
        ?>
            <!-- [ INFO BAR ========= -->
            <div class="infobar infobar--primary">
                <div class="row justify-content-between align-items-center position-relative">
                    <div class="col-lg-8 col-sm-6">
                        <div class="d-flex align-items-lg-center">
                            <div class="infobar__media me-4">
                                <div class="infobar__media-icon infobar__media-icon--vcamera ">
                                    <svg class="icon icon--vcamera">
                                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#video-camera'; ?>"></use>
                                    </svg>
                                </div>
                            </div>
                            <div class="infobar__content">
                                <div class="upcoming-lesson display-inline">
                                    <?php echo Label::getLabel('LBL_NEXT_CLASS:'); ?> <date class=" bold-600"> <?php echo MyDate::showDate($upcomingClass['grpcls_start_datetime']); ?></date> <?php echo Label::getLabel('LBL_AT'); ?> <time class=". bold-600"><?php echo date(MyDate::getFormatTime(), $upcomingClass['grpcls_starttime_unix']); ?></time>
                                    <?php if ($siteUserType == User::LEARNER) { ?>
                                        <?php echo Label::getLabel('LBL_WITH'); ?>
                                        <div class="avtar-meta display-inline">
                                            <span class="avtar avtar--round avtar--xsmall display-inline me-2" data-title="<?php echo CommonHelper::getFirstChar($upcomingClass['teacher_first_name']); ?>">
                                                <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $upcomingClass['grpcls_teacher_id'], Afile::SIZE_SMALL], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME); ?>" />
                                            </span>
                                            <?php echo $upcomingClass['teacher_first_name'] . ' ' . $upcomingClass['teacher_last_name']; ?>
                                        </div>
                                    <?php
                                    } else {
                                        echo "(" . $upcomingClass['grpcls_title'] . ")";
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="upcoming-lesson-action d-flex align-items-center justify-content-between justify-content-sm-end">
                            <div class="timer me-3">
                                <div class="timer__media"><span><svg class="icon icon--clock icon--small">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#clock'; ?>"></use>
                                        </svg></span></div>
                                <div class="timer__content">
                                    <div class="timer__controls timer-js style colorDefinition size_sm" id="classStartTimer" timestamp="<?php echo $upcomingClass['grpcls_start_datetime_utc']; ?>">00:00:00:00</div>
                                </div>
                            </div>
                            <?php if (empty($upcomingClass['grpcls_offline'])) { ?>
                                <a href="<?php echo MyUtility::makeUrl('Classes', 'view', [$classId]); ?>" class="btn bg-secondary"><?php echo Label::getLabel('LBL_ENTER_CLASSROOM') ?></a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
            <!-- ] -->
        <?php } ?>
        <!-- [ FILTERS ========= -->
        <div class="page-filter">
            <?php echo $frm->getFormTag(); ?>
            <div class="switch-controls">
                <div class="switch-controls__colum-left">
                    <div class="switch-ui">
                        <ul>
                            <?php foreach ($statusFld->options as $value => $label) { ?>
                                <li>
                                    <label class="switch-ui__item">
                                        <input type="radio" class="switch-ui__input" onchange="search(this.form);" name="<?php echo $statusFld->getName(); ?>" value="<?php echo $value; ?>" <?php echo ($statusFld->value == $value) ? "checked" : ""; ?> />
                                        <span class="switch-ui__label"><?php echo $label; ?></span>
                                    </label>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
                <div class="switch-controls__colum-right">
                    <div class="switch-ui switch-ui--icons">
                        <ul>
                            <li>
                                <label class="switch-ui__item">
                                    <input type="radio" class="switch-ui__input" onchange="search(this.form);" name="<?php echo $viewFld->getName(); ?>" value="<?php echo AppConstant::VIEW_LISTING; ?>" <?php echo ($viewFld->value == AppConstant::VIEW_LISTING) ? 'checked' : ''; ?> />
                                    <span class="switch-ui__label">
                                        <svg class="icon icon--view icon--small me-2">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#lesson-view'; ?>"></use>
                                        </svg>
                                        <?php echo $viewFld->options[AppConstant::VIEW_LISTING]; ?>
                                    </span>
                                </label>
                            </li>
                            <li>
                                <label class="switch-ui__item">
                                    <input type="radio" class="switch-ui__input" onchange="search(this.form);" name="<?php echo $viewFld->getName(); ?>" value="<?php echo AppConstant::VIEW_CALENDAR; ?>" <?php echo ($viewFld->value == AppConstant::VIEW_CALENDAR) ? 'checked' : ''; ?> />
                                    <span class="switch-ui__label">
                                        <svg class="icon icon--calendar me-1">
                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#calendar'; ?>"></use>
                                        </svg>
                                        <?php echo $viewFld->options[AppConstant::VIEW_CALENDAR]; ?>
                                    </span>
                                </label>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="search-filter slide-target-js">
                <div class="row">
                    <div class="col-lg-4 col-sm-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $keywordFld->getCaption(); ?>
                                    <?php if ($keywordFld->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $keywordFld->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $offlineClsFld->getCaption(); ?>
                                    <?php if ($offlineClsFld->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $offlineClsFld->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $startdateFld->getCaption(); ?>
                                    <?php if ($startdateFld->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $startdateFld->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="field-set">
                            <div class="caption-wraper">
                                <label class="field_label">
                                    <?php echo $enddateFld->getCaption(); ?>
                                    <?php if ($enddateFld->requirement->isRequired()) { ?>
                                        <span class="spn_must_field">*</span>
                                    <?php } ?>
                                </label>
                            </div>
                            <div class="field-wraper">
                                <div class="field_cover">
                                    <?php echo $enddateFld->getHtml(); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-sm-6">
                        <div class="field-set">
                            <div class="caption-wraper"><label class="field_label"></label></div>
                            <div class="field-wraper form-buttons-group">
                                <div class="field_cover">
                                    <?php echo $frm->getFieldHtml('pageno'); ?>
                                    <?php echo $frm->getFieldHtml('pagesize'); ?>
                                    <?php echo $frm->getFieldHtml('package_id'); ?>
                                    <?php echo $frm->getFieldHtml('ordcls_id'); ?>
                                    <?php echo $frm->getFieldHtml('grpcls_id'); ?>
                                    <?php echo $frm->getFieldHtml('order_id'); ?>
                                    <?php echo $frm->getFieldHtml('btn_submit'); ?>
                                    <?php echo $frm->getFieldHtml('btn_clear'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </form>
            <?php echo $frm->getExternalJS(); ?>
        </div>
        <!-- ] ========= -->

        <!-- [ PAGE PANEL ========= -->
        <div class="page-content" id="listing"></div>
        <!-- ] -->
    </div>
    <script>
        const VIEW_CALENDAR = <?php echo AppConstant::VIEW_CALENDAR ?>;
        const VIEW_LISTING = <?php echo AppConstant::VIEW_LISTING ?>;
        $(document).ready(function() {
            search(document.frmClassSearch);
            <?php if (!empty($upcomingClass)) { ?>
                $("#classStartTimer").w3mentorsTimer({
                    recordId: '<?php echo $classId; ?>',
                    recordType: 'CLASS'
                });
            <?php } ?>
        });
    </script>