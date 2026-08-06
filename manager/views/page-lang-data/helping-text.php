<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (isset($this->variables['pageText']['pageHelpingText']) && !empty($this->variables['pageText']['pageHelpingText'])) { ?>
    <div id="helpCenterJs">
        <button class="help-btn btn btn-primary" data-bs-toggle="modal" data-bs-target="#help">
            <span class="help_label"><?php echo Label::getLabel('LBL_HELP', $siteLangId); ?></span>
        </button>

        <div class="modal fixed-right fade" id="help" tabindex="-1" role="dialog" aria-labelledby="help" aria-hidden="true">
            <div class="modal-dialog modal-dialog-vertical" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                        <?php echo $this->variables['pageText']['pageTitle']; ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="cms help-data">
                        <?php echo CommonHelper::renderHtml($this->variables['pageText']['pageHelpingText']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php } ?>