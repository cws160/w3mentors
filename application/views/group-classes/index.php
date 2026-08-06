<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$keyword = $srchFrm->getField('keyword');
$teachs = $srchFrm->getField('teachs');
$classtype = $srchFrm->getField('classtype');
$duration = $srchFrm->getField('duration');
$pageno = $srchFrm->getField('pageno');
$grpclsOffline = $srchFrm->getField('grpcls_offline');
$lat = $srchFrm->getField('user_lat');
$lng = $srchFrm->getField('user_lng');
$address = $srchFrm->getField('formatted_address');
$jslabels = json_encode([
    'allLanguages' => Label::getLabel('LBL_ALL_TEACH_LANGUAGES'),
    'allClassTypes' => Label::getLabel('LBL_All_CLASS_TYPES'),
    'allDurations' => Label::getLabel('LBL_All_DURATIONS')
]);
$address = $srchFrm->getField('formatted_address');
?>

<script>
    LABELS = <?php echo $jslabels; ?>;
</script>
<section class="section bg-gradiant section--page-header text-center">
    <div class="container container--xl">
        <h2><?php echo Label::getLabel('LBL_CLASS_SEARCH_HEADLINE'); ?></h2>
        <div class="main-search">
            <div class="main-search__field">
                <input type="text" name="keyword_search" id="keyword_search" placeholder="<?php echo Label::getLabel('LBL_BY_KEYWORD'); ?>" data-field-caption="<?php echo Label::getLabel('LBL_BY_KEYWORD'); ?>" class="keyword-field-js" value="<?php echo $keyword->value ?>">
            </div>
            <div class="main-search__action">
                <a class="main-search__submit" onclick="searchKeyword();" title="Search">
                    <svg class="icon icon--search">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                    </svg>
                </a>
                <div class="main-search__reset" onclick="clearKeyword();" style="display: none;" title="Reset">
                    <span class="close"></span>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container container--lg">
        <div class="page">
            <div class="page-header mb-xl-5 mb-4">
                <div class="row g-3 justify-content-between align-items-center">
                    <div class="col-auto">
                        <div class="search-result">
                            <h3 class="record-count-header">
                                <?php echo str_replace('{recordcount}', $recordCount ?? 0, Label::getLabel('LBL_FOUND_THE_BEST_{recordcount}_CLASSES_FOR_YOU')); ?>
                            </h3>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="sorting-options">
                            <div class="sorting-options__item">
                                <div class="btn--filters" onclick="openFilter()">
                                    <span class="svg-icon">
                                        <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 402.577 402.577" style="enable-background:new 0 0 402.577 402.577;" xml:space="preserve">
                                            <g>
                                                <path d="M400.858,11.427c-3.241-7.421-8.85-11.132-16.854-11.136H18.564c-7.993,0-13.61,3.715-16.846,11.136
                                    c-3.234,7.801-1.903,14.467,3.999,19.985l140.757,140.753v138.755c0,4.955,1.809,9.232,5.424,12.854l73.085,73.083
                                    c3.429,3.614,7.71,5.428,12.851,5.428c2.282,0,4.66-0.479,7.135-1.43c7.426-3.238,11.14-8.851,11.14-16.845V172.166L396.861,31.413
                                    C402.765,25.895,404.093,19.231,400.858,11.427z"></path>
                                            </g>
                                        </svg>
                                    </span>
                                    <?php echo Label::getLabel('LBL_FILTERS'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
                <div class="page-panel">
                    <div class="page-panel__small scrolling" id="STICKY">
                        <?php echo $srchFrm->getFormTag(); ?>
                        <div class="sidebar-filters" id="filter-panel">
                            <button class="icon-close d-xl-none js-filter-close" type="button" onclick="closeFilter();"></button>
                            <div class="sidebar-filters__head">
                                <h6><?php echo Label::getLabel('LBL_FILTERS'); ?></h6>
                                <a href="javascript:void(0)" class="link" onclick="clearAllFilters();"><?php echo Label::getLabel('LBL_Clear_all_Filters'); ?></a>
                            </div>
                            <div class="sidebar-filters__body" id="accordionParent">
                                <?php if (User::offlineSessionsEnabled()) { ?>
                                    <div class="filter-widget border-0">
                                        <div class="switch-options">
                                            <label class="switch-action is-hover switch-filter">
                                                <span class="switch-action-label no-wrap"><?php echo Label::getLabel('LBL_Offline_Sessions'); ?></span>
                                                <span class="switch switch--small">
                                                    <input class="switch__label" type="checkbox" name="ch_offline" onclick="searchOfflineClasses(this.checked);" <?php echo empty($grpclsOffline->value) ? '' : 'checked'; ?> />
                                                    <i class="switch__handle bg-green"></i>
                                                </span>
                                                <span class="tooltip tooltip--top bg-black">
                                                    <span class="tooltip__content"><?php echo Label::getLabel('LBL_CLASSES_THAT_ARE_AVAILABLE_OFFLINE'); ?></span>
                                                </span>
                                            </label>
                                            <div class="geo-location--js" style="display: none;">
                                                <div class="geo-location">
                                                    <div class="geo-location__field">
                                                        <input class="geo-location_input pac-target-input" id="google-autocomplete" size="50" placeholder="Address" type="search" name="address" value="<?php echo $address->value ?>" autocomplete="off" data-address="<?php echo $userAddress['formatted_address'] ?? ''; ?>" data-lat="<?php echo $userAddress['usradd_latitude'] ?? ''; ?>" data-lng="<?php echo $userAddress['usradd_longitude'] ?? ''; ?>">
                                                        <span class="close btn-close" id="btnCloseJs" onclick="clearLocation();" style="display:none"></span>
                                                    </div>
                                                    <button class="geo-location__btn btn btn--primary gap-2 btn--block" type="button" onclick="getLocation();">
                                                        <svg class="icon icon--18">
                                                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#icon-detect"></use>
                                                        </svg>
                                                        <span class="txt"><?php echo Label::getLabel('LBL_DETECT_MY_LOCATION'); ?></span>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php } ?>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#all-subjects" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-globe-2'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_ALL_LANGUAGE'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="all-subjects">
                                        <div class="filter-widget__inner">
                                            <div class="search-form mb-4">
                                                <div class="search-form__field">
                                                    <input type="text" name="teach_language" onkeyup="onkeyupLanguage()" placeholder="<?php echo Label::getLabel('LBL_SEARCH_LANGUAGE'); ?>" />
                                                </div>
                                            </div>
                                            <div class="filters-scroll options-filter-js">
                                                <?php $this->includeTemplate('_partial/teach-languages.php', ['teachLanguages' => $teachs->options, 'values' => $teachs->value, 'langPage' => false]); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-class-type" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-test-preparations'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_CLASS_TYPE'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-class-type">
                                        <div class="filter-widget__inner options-filter-js">
                                            <ul class="list-vertical">
                                                <?php foreach ($classtype->options as $id => $name) { ?>
                                                    <li>
                                                        <label class="form-check">
                                                            <input class="form-check-input classtype-filter-js" type="checkbox" name="classtype[]" value="<?php echo $id; ?>" <?php echo in_array($id, $classtype->value) ? 'checked' : ''; ?> />
                                                            <span class="form-check-label"><?php echo $name; ?></span>
                                                        </label>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-duration" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-clock'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_CLASS_DURATION'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-duration">
                                        <div class="filter-widget__inner options-filter-js">
                                            <ul class="list-vertical">
                                                <?php foreach ($duration->options as $id => $name) { ?>
                                                    <li>
                                                        <label class="form-check">
                                                            <input class="form-check-input duration-filter-js" type="checkbox" name="duration[]" value="<?php echo $id; ?>" <?php echo in_array($id, $duration->value) ? 'checked' : ''; ?> />
                                                            <span class="form-check-label"><?php echo $name; ?></span>
                                                        </label>
                                                    </li>
                                                <?php } ?>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="text" name="pageno" value="<?php echo $pageno->value; ?>" style="display: none;" />
                        <input type="text" name="keyword" value="<?php echo $keyword->value; ?>" style="display: none;" />
                        <input type="hidden" name="grpcls_offline" value="<?php echo $grpclsOffline->value; ?>" />
                        <input type="hidden" name="user_lat" value="<?php echo $lat->value; ?>" />
                        <input type="hidden" name="user_lng" value="<?php echo $lng->value; ?>" />
                        <input type="hidden" name="formatted_address" value="<?php echo $address->value; ?>" />
                        </form>
                    </div>
                    <div class="page-panel__large">
                        <div class="page-listing" id="listing"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- ] -->
<?php if (User::offlineSessionsEnabled()) { ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo FatApp::getConfig('CONF_GOOGLE_API_KEY', FatUtility::VAR_STRING, '') ?>&libraries=places&v=weekly" defer></script>
<?php } ?>
<script>
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
</script>