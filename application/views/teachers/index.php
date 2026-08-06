<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$keyword = $srchFrm->getField('keyword');
$teachs = $srchFrm->getField('teachs');
$days = $srchFrm->getField('days');
$slots = $srchFrm->getField('slots');
$gender = $srchFrm->getField('gender');
$locations = $srchFrm->getField('locations');
$speaks = $srchFrm->getField('speaks');
$accents = $srchFrm->getField('accents');
$levels = $srchFrm->getField('levels');
$lessonType = $srchFrm->getField('lesson_type');
$tests = $srchFrm->getField('tests');
$ageGroup = $srchFrm->getField('age_group');
$sorting = $srchFrm->getField('sorting');
$offlineSessions = $srchFrm->getField('user_offline_sessions');
$jslabels = json_encode([
    'allLanguages' => Label::getLabel('LBL_ALL_TEACH_LANGUAGES'),
    'selectTiming' => Label::getLabel('LBL_SELECT_TIMING'),
    'allPrices' => Label::getLabel('LBL_ALL_PRICES'),
]);
$sortOptions = $sorting->options;
$maxPrice = ceil(MyUtility::formatMoney($priceRange['maxPrice'], false));
$minPrice = floor(MyUtility::formatMoney($priceRange['minPrice'], false));
$address = $srchFrm->getField('formatted_address');
$sortSelectedOpt = empty($sorting->value) ? current($sortOptions) : $sortOptions[$sorting->value];
$lastseen = $srchFrm->getField('user_lastseen');
$featured = $srchFrm->getField('user_featured');
?>
<script>
    LABELS = <?php echo $jslabels; ?>;
</script>
<section class="section bg-gradiant section--page-header text-center">
    <div class="container container--xl">
        <h2><?php echo Label::getLabel('LBL_TEACHER_SEARCH_HEADLINE'); ?></h2>
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
                                <?php echo str_replace('{recordcount}', $recordCount ?? 0, Label::getLabel('LBL_FOUND_THE_BEST_{recordcount}_TEACHERS_FOR_YOU')); ?>
                            </h3>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="sorting-options">
                            <div class="sorting-options__item">
                                <div class="sorting-action">
                                    <div class="sorting-action__trigger sort-trigger-js switch-filter" onclick="toggleSort();">
                                        <span class="sorting-action__label"><?php echo Label::getLabel('LBL_SORT'); ?>:</span>
                                        <span class="sorting-action__value"><?php echo $sortSelectedOpt; ?></span>
                                    </div>
                                    <div class="sorting-action__target sort-target-js" style="display: none;">
                                        <div class="filter-dropdown">
                                            <div class="select-list select-list--vertical select-list--scroll">
                                                <ul>
                                                    <?php foreach ($sortOptions as $id => $name) { ?>
                                                        <li>
                                                            <label class="select-option">
                                                                <input class="select-option__input" type="radio" name="sorts" value="<?php echo $id; ?>" <?php echo ($id == $sorting->value) ? 'checked' : ''; ?> onclick="sortsearch(this);" />
                                                                <span class="select-option__item sortOptTitleJs<?php echo $id; ?>"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                            <button type="button" class="icon-close d-xl-none js-filter-close" onclick="closeFilter();"></button>
                            <div class="sidebar-filters__head">
                                <h6><?php echo Label::getLabel('LBL_FILTERS'); ?></h6>
                                <a href="javascript:void(0)" class="link" onclick="clearFilters();"><?php echo Label::getLabel('LBL_Clear_all_Filters'); ?></a>
                            </div>
                            <div class="sidebar-filters__body" id="accordionParent">
                                <?php if (User::offlineSessionsEnabled()) { ?>
                                    <div class="filter-widget border-0">
                                        <div class="switch-options">
                                            <label class="switch-action is-hover switch-filter">
                                                <span class="switch-action-label no-wrap"><?php echo Label::getLabel('LBL_OFFLINE_SESSIONS'); ?></span>
                                                <span class="switch switch--small">
                                                    <input class="switch__label" type="checkbox" name="ch_offline" onclick="searchOfflineSessions(this.checked);" <?php echo empty($offlineSessions->value) ? '' : 'checked'; ?> />
                                                    <i class="switch__handle bg-green"></i>
                                                </span>
                                                <span class="tooltip tooltip--top bg-black">
                                                    <span class="tooltip__content"><?php echo Label::getLabel('LBL_TEACHERS_WHO_PROVIDE_OFFLINE_LECTURES'); ?></span>
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
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#all-price" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-price'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_ALL_PRICES'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="all-price">
                                        <div class="filter-widget__inner">
                                            <div class="price-filter">
                                                <div class="price-filter__slider">
                                                    <div id="price-slider-noui"></div>
                                                </div>
                                                <div class="price-filter__form mt-3 text-filter-js">
                                                    <div class="row">
                                                        <div class="col-6">
                                                            <div class="field-set">
                                                                <input type="text" class="priceSliderValue" name="price_from" data-index="0" placeholder="<?php echo Label::getLabel('LBL_PRICE_FROM'); ?>" onkeyup="searchPrice();" value="<?php echo $srchFrm->getField('price_from')->value ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="field-set">
                                                                <input type="text" class="priceSliderValue" name="price_till" data-index="1" placeholder="<?php echo Label::getLabel('LBL_PRICE_TILL'); ?>" onkeyup="searchPrice();" value="<?php echo $srchFrm->getField('price_till')->value ?>">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#availability" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-availablity'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_AVAILABILITY'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="availability">
                                        <div class="filter-widget__inner">
                                            <nav class="tabs mb-4 mt-2">
                                                <ul class="nav nav-pills" id="nav-tab" role="tablist">
                                                    <li class="nav-item">
                                                        <button class="nav-link active" id="nav-tab__01" data-bs-toggle="tab" data-bs-target="#tab__01" type="button" role="tab" aria-controls="tab__01">
                                                            <?php echo Label::getLabel('LBL_WEEK_DAYS'); ?>
                                                        </button>
                                                    </li>
                                                    <li class="nav-item">
                                                        <button class="nav-link" id="nav-tab__02" data-bs-toggle="tab" data-bs-target="#tab__02" type="button" role="tab" aria-controls="tab__02">
                                                            <?php echo Label::getLabel('LBL_SLOTS'); ?>
                                                        </button>
                                                    </li>
                                                </ul>
                                            </nav>
                                            <div class="tabs-container">
                                                <div class="tab-pane active" id="tab__01" role="tabpanel">
                                                    <div class="filters-scroll options-filter-js">
                                                        <ul class="list-vertical">
                                                            <?php foreach ($days->options as $id => $name) { ?>
                                                                <li>
                                                                    <label class="form-check">
                                                                        <input class="form-check-input availbility-filter-js" type="checkbox" name="days[]" value="<?php echo $id; ?>" role="checkbox" <?php echo in_array($id, $days->value) ? 'checked' : ''; ?> />
                                                                        <span class="form-check-label"><?php echo $name; ?></span>
                                                                    </label>
                                                                </li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <div class="tab-pane" id="tab__02" role="tabpanel">
                                                    <div class="filters-scroll options-filter-js">
                                                        <ul class="list-vertical">
                                                            <?php foreach ($slots->options as $id => $name) { ?>
                                                                <li>
                                                                    <label class="form-check">
                                                                        <input class="form-check-input availbility-filter-js" type="checkbox" name="slots[]" value="<?php echo $id; ?>" <?php echo in_array($id, $slots->value) ? 'checked' : ''; ?> />
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
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-location" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-location'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_LOCATION'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-location">
                                        <div class="filter-widget__inner">
                                            <div class="search-form mb-4">
                                                <div class="search-form__field">
                                                    <input type="text" name="location_search" onkeyup="onkeyupLocation()" placeholder="<?php echo Label::getLabel('LBL_SEARCH_LOCATION'); ?>" />
                                                </div>
                                                <div class="search-form__action">
                                                    <span class="btn btn--equal btn--transparent color-black">
                                                        <svg class="icon icon--search icon--18">
                                                            <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#search'; ?>"></use>
                                                        </svg>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php foreach ($locations->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="form-check">
                                                                <input class="form-check-input country-filter-js" type="checkbox" name="locations[]" value="<?php echo $id; ?>" <?php echo in_array($id, $locations->value) ? 'checked' : ''; ?> />
                                                                <span class="form-check-label select-location-js"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-gender" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-gender'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_TEACHER_GENDER'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-gender">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php foreach ($gender->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="form-check">
                                                                <input class="form-check-input gender-filter-js" type="checkbox" name="gender[]" value="<?php echo $id; ?>" <?php echo in_array($id, $gender->value) ? 'checked' : ''; ?> />
                                                                <span class="form-check-label"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-speaks" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-speaks'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_TEACHER_SPEAKS'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-speaks">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php foreach ($speaks->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="form-check">
                                                                <input class="form-check-input speak-filter-js" type="checkbox" name="speaks[]" value="<?php echo $id; ?>" <?php echo in_array($id, $speaks->value) ? 'checked' : ''; ?> />
                                                                <span class="form-check-label"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-accents" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-accents'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_TEACHER_ACCENTS'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-accents">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php foreach ($accents->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="form-check">
                                                                <input class="form-check-input accent-filter-js" type="checkbox" name="accents[]" value="<?php echo $id; ?>" <?php echo in_array($id, $accents->value) ? 'checked' : ''; ?> />
                                                                <span class="form-check-label"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-level" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-teacher-level'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_TEACHES_LEVEL'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-level">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php foreach ($levels->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="form-check">
                                                                <input class="form-check-input level-filter-js" type="checkbox" name="levels[]" value="<?php echo $id; ?>" <?php echo in_array($id, $levels->value) ? 'checked' : ''; ?> />
                                                                <span class="form-check-label"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-lessons" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-lesson-included'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_LESSON_INCLUDES'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-lessons">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php foreach ($lessonType->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="form-check">
                                                                <input class="form-check-input include-filter-js" type="checkbox" name="lesson_type[]" value="<?php echo $id; ?>" <?php echo in_array($id, $lessonType->value) ? 'checked' : ''; ?> />
                                                                <span class="form-check-label"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-preparations" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-test-preparations'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_TEST_PREPARATIONS'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-preparations">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php foreach ($tests->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="form-check">
                                                                <input class="form-check-input test-filter-js" type="checkbox" name="tests[]" value="<?php echo $id; ?>" <?php echo in_array($id, $tests->value) ? 'checked' : ''; ?> />
                                                                <span class="form-check-label"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-age-group" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-age-group'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_LEARNER_AGE_GROUP'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-age-group">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php foreach ($ageGroup->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="form-check">
                                                                <input class="form-check-input age-group-filter-js" type="checkbox" name="age_group[]" value="<?php echo $id; ?>" <?php echo in_array($id, $ageGroup->value) ? 'checked' : ''; ?> />
                                                                <span class="form-check-label"><?php echo $name; ?></span>
                                                            </label>
                                                        </li>
                                                    <?php } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-others" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#filter'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_MORE_FILTERS'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-others">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <li>
                                                        <label class="form-check">
                                                            <input class="form-check-input other-filter-js" type="checkbox" name="user_lastseen" value="<?php echo $lastseen->value; ?>" <?php echo empty($lastseen->value ?? '') ? '' : 'checked'; ?> />
                                                            <span class="form-check-label"><?php echo Label::getLabel('LBL_ACTIVE_TEACHERS'); ?></span>
                                                        </label>
                                                    </li>
                                                    <li>
                                                        <label class="form-check">
                                                            <input class="form-check-input other-filter-js" type="checkbox" name="user_featured" value="<?php echo $featured->value; ?>" <?php echo empty($featured->value ?? '') ? '' : 'checked'; ?> />
                                                            <span class="form-check-label"><?php echo Label::getLabel('LBL_FEATURED_TEACHERS'); ?></span>
                                                        </label>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="keyword" value="<?php echo $keyword->value; ?>" />
                        <input type="hidden" name="user_offline_sessions" value="<?php echo $offlineSessions->value ?? 0; ?>" />
                        <input type="hidden" name="maxPrice" value="<?php echo $maxPrice; ?>" />
                        <input type="hidden" name="minPrice" value="<?php echo $minPrice; ?>" />
                        <input type="hidden" name="user_lat" value="<?php echo $srchFrm->getField('user_lat')->value; ?>" />
                        <input type="hidden" name="user_lng" value="<?php echo $srchFrm->getField('user_lng')->value; ?>" />
                        <input type="hidden" name="formatted_address" value="<?php echo $address->value ?>" />
                        <input type="hidden" name="sorting" value="<?php echo $sorting->value; ?>" />
                        <input type="hidden" name="pageno" value="<?php echo $srchFrm->getField('pageno')->value; ?>" />
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
<?php if (User::offlineSessionsEnabled()) { ?>
    <script src="https://maps.googleapis.com/maps/api/js?key=<?php echo FatApp::getConfig('CONF_GOOGLE_API_KEY', FatUtility::VAR_STRING, '') ?>&libraries=places&v=weekly" defer></script>
<?php } ?>
<?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>