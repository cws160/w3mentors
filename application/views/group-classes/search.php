<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (count($classes)) { ?>
    <div class="row g-4">
        <?php
        foreach ($classes as $class) {
            $classData = ['class' => $class, 'siteUserId' => $siteUserId, 'bookingBefore' => $bookingBefore, 'cardClass' => 'col-xl-4 col-sm-6'];
            $this->includeTemplate('group-classes/card.php', $classData, false);
        }
        ?>
    </div>
    <?php
    $pagingArr = [
        'page' => $post['pageno'],
        'pageSize' => $post['pagesize'],
        'recordCount' => $recordCount,
        'pageCount' => ceil($recordCount / $post['pagesize']),
    ];
    echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmSearchPaging']);
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
    ?>
<?php } else { ?>
    <div class="page-listing__body">
        <div class="box -padding-30" style="margin-bottom: 30px;">
            <div class="message-display">
                <div class="message-display__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 408">
                        <path d="M488.468,408H23.532A23.565,23.565,0,0,1,0,384.455v-16.04a15.537,15.537,0,0,1,15.517-15.524h8.532V31.566A31.592,31.592,0,0,1,55.6,0H456.4a31.592,31.592,0,0,1,31.548,31.565V352.89h8.532A15.539,15.539,0,0,1,512,368.415v16.04A23.565,23.565,0,0,1,488.468,408ZM472.952,31.566A16.571,16.571,0,0,0,456.4,15.008H55.6A16.571,16.571,0,0,0,39.049,31.566V352.891h433.9V31.566ZM497,368.415a0.517,0.517,0,0,0-.517-0.517H287.524c0.012,0.172.026,0.343,0.026,0.517a7.5,7.5,0,0,1-7.5,7.5h-48.1a7.5,7.5,0,0,1-7.5-7.5c0-.175.014-0.346,0.026-0.517H15.517a0.517,0.517,0,0,0-.517.517v16.04a8.543,8.543,0,0,0,8.532,8.537H488.468A8.543,8.543,0,0,0,497,384.455h0v-16.04ZM63.613,32.081H448.387a7.5,7.5,0,0,1,0,15.008H63.613A7.5,7.5,0,0,1,63.613,32.081ZM305.938,216.138l43.334,43.331a16.121,16.121,0,0,1-22.8,22.8l-43.335-43.318a16.186,16.186,0,0,1-4.359-8.086,76.3,76.3,0,1,1,19.079-19.071A16,16,0,0,1,305.938,216.138Zm-30.4-88.16a56.971,56.971,0,1,0,0,80.565A57.044,57.044,0,0,0,275.535,127.978ZM63.613,320.81H448.387a7.5,7.5,0,0,1,0,15.007H63.613A7.5,7.5,0,0,1,63.613,320.81Z"></path>
                    </svg>
                </div>
                <h5><?php echo Label::getLabel('LBL_NO_CLASS_FOUND!'); ?></h5>
            </div>
        </div>
    </div>
<?php } ?>
<script>
    CLASS_COUNT = '<?php echo $recordCount; ?>';
    COUNT_LABEL_TXT = '<?php echo Label::getLabel('LBL_FOUND_THE_BEST_{recordcount}_CLASSES_FOR_YOU'); ?>';
    COUNT_LABEL_TXT = COUNT_LABEL_TXT.replace('{recordcount}', CLASS_COUNT);
    $('.record-count-header').text(COUNT_LABEL_TXT);
</script>