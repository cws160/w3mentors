<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<style>
    mux-player {
        width: 100%;
        max-width: 100%;
    }
</style>
<div class="modal-header">
    <h5><?php echo $course['course_title'] ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body">
    <div class="preview-video ratio ratio--16by9">
        <?php $style = 'block;';
        if ($videoUrl) {
            $style = 'none;';
        }
        ?>
        <?php if (FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_VIDEO_CIPHER) { ?>
            <iframe width="100%" height="100%" src="<?php echo $videoUrl ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen="" allow="encrypted-media"></iframe>
        <?php } else { ?>
            <script src="https://cdn.jsdelivr.net/npm/@mux/mux-player"></script>
            <mux-player playback-id="<?php echo $videoUrl; ?>" metadata-video-title="Placeholder (optional)" metadata-viewer-user-id="Placeholder (optional)" accent-color="#FF0000"></mux-player>
        <?php } ?>
        <div class="course-video-error ratio ratio--2by1 heading-4 color-danger" style="display:<?php echo $style; ?>">
            <div class="d-flex justify-content-center align-items-center direction-column">
                <svg fill="var(--color-danger)">
                    <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#issue"></use>
                </svg>
                <span><?php echo Label::getLabel("LBL_VIDEO_NOT_FOUND"); ?></span>
            </div>
        </div>
    </div>
</div>