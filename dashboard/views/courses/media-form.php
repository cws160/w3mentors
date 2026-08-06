<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');

$frm->setFormTagAttribute('class', 'form');
$frm->setFormTagAttribute('onsubmit', 'submitMedia(); return(false);');
$frm->setFormTagAttribute('id', 'frmCourses');
$crsFld = $frm->getField('course_image');
$crsFld->setFieldTagAttribute('onchange', 'setupMedia()');
$videoFld = $frm->getField('course_preview_video');
$videoFld->addFieldTagAttribute('onchange', 'uploadVideo(this);');
$fld = $frm->getField('btn_submit');
$fld->setFieldTagAttribute('class', 'btn btn--primary -no-border');
$fld = $frm->getField('btn_submit');
?>
<style>
    .media-placeholder__reload {
        text-align: center;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
        cursor: pointer;
    }

    .media-placeholder__reload svg {
        width: 60px;
        height: 60px;
        margin: 0 auto;
        display: block;
        fill: currentColor;
    }

    mux-player {
        width: 100%;
        max-width: 100%;
        display: contents;
    }
</style>
<?php echo $frm->getFormTag(); ?>
<div class="page-layout">
    <div class="page-layout__small">
        <?php echo $this->includeTemplate('courses/sidebar.php', ['frm' => $frm, 'active' => 1, 'courseId' => $courseId]) ?>
    </div>
    <div class="page-layout__large">
        <div class="box-panel">
            <div class="box-panel__head">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4><?php echo Label::getLabel('LBL_MANAGE_BASIC_DETAILS'); ?></h4>
                    </div>
                </div>
            </div>
            <div class="box-panel__body">
                <nav class="tabs tabs--line px-4">
                    <ul>
                        <li>
                            <a href="javascript:void(0)" onclick="generalForm();">
                                <?php echo Label::getLabel('LBL_General'); ?>
                            </a>
                        </li>
                        <li class="is-active">
                            <a href="javascript:void(0)">
                                <?php echo Label::getLabel('LBL_PHOTOS_&_VIDEOS'); ?>
                            </a>
                        </li>
                    </ul>
                </nav>
                <div class="tabs-data">
                    <div class="box-panel__container">
                        <div class="media-uploader">
                            <div class="media-uploader__asset">
                                <div class="media-uploader__wrap">

                                    <!-- [ UPLOADED MEDIA ========= -->
                                    <?php if ($image) { ?>
                                        <a href=" javascript:void(0)" class="close mediaCloseJs" onclick="removeMedia('<?php echo Afile::TYPE_COURSE_IMAGE ?>');"></a>
                                        <div class="media-placeholder ratio ratio--16by9">
                                            <div class="media-placeholder__preview">
                                                <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_COURSE_IMAGE, $courseId, 'LARGE'], CONF_WEBROOT_FRONT_URL) . '?=' . time(); ?> alt="">
                                            </div>
                                        </div>
                                    <?php } else { ?>
                                        <div class=" media-placeholder ratio ratio--16by9">
                                                <svg class=" media-placeholder__default">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#placeholder-image"></use>
                                                </svg>
                                            </div>
                                        <?php } ?>
                                        <!-- ] -->
                                        </div>
                                </div>
                                <div class="media-uploader__content">
                                    <h6 class="mb-3"><?php echo $crsFld->getCaption(); ?>
                                        <span class="spn_must_field">*</span>
                                    </h6>
                                    <p class="style-italic mb-4">
                                        <?php
                                        $lbl = Label::getLabel('LBL_COURSE_IMAGE_INFO');
                                        echo str_replace(
                                            ['{extensions}', '{dimensions}', '{filesize}'],
                                            [implode(', ', $extensions), implode('x', $dimensions), $filesize],
                                            $lbl
                                        );
                                        ?>
                                    </p>
                                    <button type="button" class="btn btn--primary-bordered btn--fileupload cursor-pointer">
                                        <svg class="icon icon--back me-2">
                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#photo-icon"></use>
                                        </svg>
                                        <?php echo Label::getLabel('LBL_UPLOAD_FILE'); ?>
                                        <?php echo $crsFld->getHtml(); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="media-uploader">
                                <div class="media-uploader__asset">
                                    <div class="media-uploader__wrap">
                                        <?php if (!empty($error)) { ?>
                                            <div class="media-placeholder ratio ratio--16by9">
                                                <span class="media-placeholder__reload color-red" style="">
                                                    <a href=" javascript:void(0)">
                                                        <svg>
                                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#issue"></use>
                                                        </svg>
                                                        <span class="underline mt-2">
                                                            <?php echo ucwords($error); ?>
                                                        </span>
                                                    </a>
                                                </span>
                                            </div>
                                        <?php } else { ?>
                                            <a href="javascript:void(0)" class="close mediaCloseJs" style="display:<?php echo (!empty($videoUrl) && $videoReady) ? 'block' : 'none'; ?>" onclick="removeMedia();"></a>
                                            <div class="media-placeholder ratio ratio--16by9">
                                                <div class="media-placeholder__preview videoPreviewJs" style="display:<?php echo (!empty($videoUrl) && $videoReady) ? 'block' : 'none'; ?>">
                                                    <?php if (FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_VIDEO_CIPHER) { ?>
                                                        <iframe src="<?php echo $videoUrl; ?>" style="border:0;width:100%;height:100%" allow="encrypted-media" allowfullscreen></iframe>
                                                    <?php } else { ?>
                                                        <script src="https://cdn.jsdelivr.net/npm/@mux/mux-player"></script>
                                                        <mux-player playback-id="<?php echo $videoUrl; ?>" metadata-video-title="Placeholder (optional)" metadata-viewer-user-id="Placeholder (optional)" accent-color="#FF0000"></mux-player>
                                                    <?php } ?>
                                                    <!-- [ DEFAULT ICON ========= -->
                                                </div>
                                                <svg class="media-placeholder__default videoDefaultJs" style="display:<?php echo (empty($videoUrl) && !$videoReady) ? 'block' : 'none'; ?>">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#placeholder-video"></use>
                                                </svg>
                                                <span class="media-placeholder__reload mediaCloseJs" style="display:<?php echo (!empty($videoUrl) && !$videoReady) ? 'flex' : 'none'; ?>">
                                                    <a href=" javascript:void(0)" onclick="mediaForm();">
                                                        <svg width="100%" height="100%">
                                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#reload-icon"></use>
                                                        </svg>
                                                        <span class="underline mt-2">
                                                            <?php echo Label::getLabel('LBL_CLICK_TO_RELOAD'); ?>
                                                        </span>
                                                    </a>
                                                </span>
                                            </div>
                                        <?php } ?>
                                        <!-- ] -->
                                        <!-- <div class="progress-div d-none">
                                        <progress class='progress progress-bar' value='0' max='100.0'></progress>
                                        <span class="progress-bar-info" id='info'>0%</span>
                                    </div> -->
                                    </div>
                                </div>
                                <div class="media-uploader__content">
                                    <h6 class="mb-3">
                                        <?php echo Label::getLabel('LBL_COURSE_PREVIEW_VIDEO'); ?>
                                        <span class="spn_must_field">*</span>
                                    </h6>
                                    <p class="style-italic mb-4">
                                        <?php
                                        $lblVideo = Label::getLabel('LBL_COURSE_PREVIEW_VIDEO_INFO');
                                        echo str_replace(
                                            ['{extensionsVideo}', '{filesizeVideo}'],
                                            [implode(', ', $videoExtensions), $vdoFilesize],
                                            $lblVideo
                                        );
                                        ?>
                                    </p>
                                    <button type="button" class="btn btn--primary-bordered btn--fileupload cursor-pointer">
                                        <svg class="icon icon--back me-2">
                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD; ?>images/sprite.svg#video-camera"></use>
                                        </svg>
                                        <?php echo Label::getLabel('LBL_UPLOAD_VIDEO'); ?>
                                        <?php echo $frm->getFieldHtml('course_preview_video'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php echo $frm->getFieldHtml('course_id'); ?>
    </form>
    <?php echo $frm->getExternalJS(); ?>
    <script>
        errorMsg = "<?php echo Label::getLabel('LBL_PLEASE_UPLOAD_BOTH_FILES.') ?>";
    </script>