<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); 
if(FatApp::getConfig('CONF_ACTIVE_VIDEO_TOOL') == VideoStreamer::TYPE_VIDEO_CIPHER) { ?>
    <iframe src="<?php echo $url; ?>" style="border:0;width:100%;height:100%" allow="encrypted-media" allowfullscreen></iframe>
<?php } else { ?>
    <script src="https://cdn.jsdelivr.net/npm/@mux/mux-player"></script>
    <mux-player playback-id="<?php echo $url; ?>" metadata-video-title="Placeholder (optional)" metadata-viewer-user-id="Placeholder (optional)" accent-color="#FF0000"></mux-player>
<?php } ?>