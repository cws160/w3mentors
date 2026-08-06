<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if ($lecture) {
    $containerClass = (count($resources) > 0) ? 'col-xl-7' : 'col-xl-12';
    $lectures = json_decode($progData['crspro_covered'], true);
    $lectures = ($lectures) ? $lectures : [];
?>
    <div class="row justify-content-between">
        <div class="<?php echo $containerClass; ?>">
            <div class="cms-container">
                <div class="editor-content iframe-content">
                    <iframe onload="resetIframe(this);" src="<?php echo MyUtility::makeUrl('Tutorials', 'frame', [$lecture['lecture_id']]); ?>" style="border:none; width:100%; height:30px;"></iframe>
                </div>
            </div>
        </div>
        <?php if (count($resources) > 0) { ?>
            <div class="col-xl-5 d-flex justify-content-xl-end align-items-xl-start">
                <div class="box-outlined">
                    <div class="box-outlined__head mb-4">
                        <h6><?php echo Label::getLabel('LBL_LECTURE_RESOURCES') . ' (' . count($resources) . ')' ?></h6>
                    </div>
                    <div class="box-outlined__body">
                        <div class="lecture-attachment">
                            <?php foreach ($resources as $resource) { ?>
                                <a href="<?php echo MyUtility::makeUrl('Tutorials', 'downloadResource', [$progressId, $resource['lecsrc_id']]); ?>" target="_blank" class="lecture-attachment__item">
                                    <figure class="lecture-attachment__media">
                                        <svg class="attached-media">
                                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#<?php echo Resource::getFileIcon($resource['resrc_type']); ?>">
                                            </use>
                                        </svg>
                                    </figure>
                                    <span class="lecture-attachment__content">
                                        <p class="mb-0 color-black"><?php echo $resource['resrc_name']; ?></p>
                                    </span>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
    <div class="page-directions border-top">
        <div class="row justify-content-between">
            <div class="col-sm-6">
                <?php if (empty($videoUrl)) { ?>
                    <a href="javascript:void(0);" id="btnComplete<?php echo $lecture['lecture_id']; ?>" onclick="markComplete('<?php echo $lecture['lecture_id']; ?>', '<?php echo AppConstant::YES; ?>')" class="btn btn--primary btn--sm-block  <?php echo (!in_array($lecture['lecture_id'], $lectures)) ? '' : 'btn--disabled' ?>">
                        <?php echo Label::getLabel('LBL_MARK_LECTURE_COMPLETE'); ?>
                    </a>
                <?php } ?>
            </div>
            <div class="col-sm-auto">
                <div class="btn-actions">
                    <?php $display = ($previousLecture) ? '' : 'btn--disabled'; ?>
                    <a href="javascript:void(0);" last-record='<?php echo ($previousLecture) ? 0 : 1 ?>' class="btn btn--primary-bordered me-1 getPrevJs <?php echo $display; ?>">
                        <svg class="icon icon--arrow icon--xsmall me-2">
                            <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#prev"></use>
                        </svg>
                        <?php echo Label::getLabel('LBL_PREV') ?>
                    </a>
                    <?php if (!empty($quizLinkId)) { ?>
                        <a href="javascript:void(0);" last-record='0' onclick="openQuiz('<?php echo $quizLinkId; ?>')" class="btn btn--primary-bordered ms-1 quizNavJs">
                            <?php echo Label::getLabel('LBL_NEXT') ?>
                            <svg class="icon icon--arrow icon--xsmall ms-2">
                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#next"></use>
                            </svg>
                        </a>
                    <?php } else { ?>
                        <?php $display = ($nextLecture) ? '' : 'btn--disabled'; ?>
                        <a href="javascript:void(0);" last-record='<?php echo ($nextLecture) ? 0 : 1 ?>' class="btn btn--primary-bordered ms-1 getNextJs <?php echo $display; ?>">
                            <?php echo Label::getLabel('LBL_NEXT') ?>
                            <svg class="icon icon--arrow icon--xsmall ms-2">
                                <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#next"></use>
                            </svg>
                        </a>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>
<?php } else { ?>
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="message-display no-skin">
                <div class="message-display__media">
                    <svg>
                        <use xlink:href="<?php echo CONF_WEBROOT_DASHBOARD ?>images/sprite.svg#stuck"></use>
                    </svg>
                </div>
                <h4 class="mb-3"><?php echo stripslashes(Label::getLabel("LBL_YOU_HAVE_COMPLETED_THE_LAST_LECTURE_IN_THIS_COURSE")); ?></h4>
                <?php if ((int) $progData['crspro_progress'] < 100) { ?>
                    <p><?php echo Label::getLabel('LBL_LAST_LECTURE_COMPLETED_SHORT_DESCRIPTION'); ?></p>
                    <a href="javascript:void(0);" onclick="goToPendingLecture();" class="btn btn--secondary">
                        <?php echo Label::getLabel('LBL_GO_TO_PENDING_LECTURES'); ?>
                    </a>
                <?php } ?>
            </div>
        </div>
    </div>
<?php } ?>