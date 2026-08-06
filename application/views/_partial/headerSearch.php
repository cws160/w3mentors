<?php
$keywordFld = $frm->getField('keyword');
$keywordFld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_START_LEARNING_-_FIND_YOUR_NEXT_SESSION_OR_INSTRUCTOR...'));
$keywordFld->setFieldTagAttribute('id', 'homeSearchFld');
$keywordFld->setFieldTagAttribute('autocomplete', 'off');
?>
<div class="main-search">
    <div class="main-search__field">
        <?php echo $keywordFld->getHtml(); ?>
        <div class="main-search__action">
            <span class="main-search__submit srchBtnJs" onclick="keywordSrch();" title="Search">
                <svg class="icon icon--search">
                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                </svg>
            </span>
            <div class="main-search__reset resetBtnJs" onclick="clearKeywordSrch();" style="display: none;" title="Reset">
                <span class="close"></span>
            </div>
        </div>
        <div class="main-search__target search-target-js" style="display:none;">
            <div class="auto-suggest autoSuggestJs"></div>
        </div>
    </div>
    <div class="main-search__dropdown">
        <div class="search-dropdown is-active">
            <?php $filters = AppConstant::getFilterTypes(); ?>
            <span class="cursor-pointer search-dropdown__trigger expand-trigger-js selectedFilterJs">
                <?php echo $filters[AppConstant::FILTER_ALL] ?>
            </span>
            <div class="search-dropdown__target expand-target-js" style="display: none;">
                <div class="selection-listing">
                    <ul class="filterTypeJs">
                        <?php foreach ($filters as $key => $value) { ?>
                            <li>
                                <span class="<?php echo ($key == 0) ? 'is-active' : '' ?> filterLinkJs" data-filter="<?php echo $key; ?>">
                                    <?php echo $value ?>
                                </span>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            </div>
            <?php echo $frm->getFieldHtml('type'); ?>
        </div>
    </div>
</div>