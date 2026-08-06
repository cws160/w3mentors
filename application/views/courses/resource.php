<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="modal-header">
    <h5><?php echo $resource['lecture_title'] ?></h5>
    <button type="button" class="btn-close w3mentorsmodalJs" data-bs-dismiss="modal" aria-label=""></button>
</div>
<div class="modal-body p-0">
    <div class="video-preview">
        <div class="video-preview__body">
            <div class="video-preview__large">
                <div class="preview-video ratio ratio--16by9">
                    <?php $url = (new VideoStreamer())->getUrl($resource['lecsrc_link']);
                    $style = 'block;';
                    if ($url) {
                        $style = 'none;';
                    }
                    if (FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_VIDEO_CIPHER) { ?>
                        <iframe width="100%" height="100%" src="<?php echo $url ?>" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen=""></iframe>
                    <?php } else { ?>
                        <script src="https://cdn.jsdelivr.net/npm/@mux/mux-player"></script>
                        <mux-player playback-id="<?php echo $url; ?>" metadata-video-title="Placeholder (optional)" metadata-viewer-user-id="Placeholder (optional)" accent-color="#FF0000"></mux-player>
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
            <div class="video-preview__small">
                <h6 class="padding-6 pb-3 pt-3 bold-700">
                    <?php echo Label::getLabel('LBL_FREE_SAMPLE_VIDEOS') ?>
                </h6>
                <div class="more-videos">
                    <?php if (count($lectures) > 0) { ?>
                        <?php foreach ($lectures as $lecture) {
                        ?>
                            <!-- video thumb -->
                            <div class="more-videos__item" onclick="openMedia('<?php echo $lecture['lecsrc_id']; ?>');">
                                <div class="video-item <?php echo ($resource['lecsrc_lecture_id'] == $lecture['lecsrc_lecture_id']) ? 'is-active' : ''; ?>">
                                    <div class="video-item__content">
                                        <div class="video-item__title">
                                            <?php echo $lecture['lecture_title'] ?>
                                        </div>
                                        <div class="video-item__time">

                                            <?php

                                            echo CommonHelper::convertDuration($lecture['lecture_duration'], true, false);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- ] -->
                        <?php } ?>
                    <?php } else { ?>
                        <!-- video thumb -->
                        <div class="more-videos__item">
                            <div class="video-item">
                                <div class="video-item__content">
                                    <div class="video-item__title">
                                        <?php echo Label::getLabel('LBL_NO_FREE_VIDEOS_AVAILABLE') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
</div>