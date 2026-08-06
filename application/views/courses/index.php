<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$keyword = $srchFrm->getField('keyword');
$keyword->setFieldTagAttribute('title', Label::getLabel('LBL_BY_COURSE_NAME,_TEACHER_NAME,_TAGS'));
$keyword->setFieldTagAttribute('class', 'keyword-field-js');
$priceSorting = $srchFrm->getField('price_sorting');
$category = $srchFrm->getField('course_cate_id');
$level = $srchFrm->getField('course_level');
$ratings = $srchFrm->getField('course_ratings');
$language = $srchFrm->getField('course_clang_id');
$priceFrom = $srchFrm->getField('price_from');
$priceFrom->setFieldTagAttribute('placeholder', Label::getLabel('LBL_PRICE_FROM'));
$priceFrom->setFieldTagAttribute('class', 'price-from-js');
$priceTill = $srchFrm->getField('price_till');
$priceTill->setFieldTagAttribute('placeholder', Label::getLabel('LBL_PRICE_TILL'));
$priceTill->setFieldTagAttribute('class', 'price-till-js');
$maxPrice = ceil(MyUtility::formatMoney($priceRange['maxPrice'], false));
$minPrice = floor(MyUtility::formatMoney($priceRange['minPrice'], false));
$type = $srchFrm->getField('type');
$sortOptions = $priceSorting->options;
?>
<section class="section bg-gradiant section--page-header text-center">
    <div class="container container--xl">
        <h2><?php echo Label::getLabel('LBL_COURSE_LISTING_HEADING'); ?></h2>
        <div class="main-search">
            <div class="main-search__field">
                <?php echo $keyword->getHtml(); ?>
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
                                <?php echo str_replace('{recordcount}', $recordCount ?? 0, Label::getLabel('LBL_FOUND_THE_BEST_{recordcount}_ONLINE_COURSES_FOR_YOU')); ?>
                            </h3>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="sorting-options">
                            <div class="sorting-options__item">
                                <div class="sorting-action">
                                    <div class="sorting-action__trigger sort-trigger-js switch-filter" onclick="toggleSort();">
                                        <span class="sorting-action__label"><?php echo Label::getLabel('LBL_SORT'); ?>:</span>
                                        <span class="sorting-action__value"><?php echo empty($priceSorting->value) ? current($sortOptions) : $sortOptions[$priceSorting->value]; ?></span>
                                    </div>
                                    <div class="sorting-action__target sort-target-js" style="display: none;">
                                        <div class="filter-dropdown">
                                            <div class="select-list select-list--vertical select-list--scroll">
                                                <ul>
                                                    <?php foreach ($priceSorting->options as $id => $name) { ?>
                                                        <li>
                                                            <label class="select-option">
                                                                <input class="select-option__input" type="radio" name="sorts" value="<?php echo $id; ?>" <?php echo ($id == $priceSorting->value) ? 'checked' : ''; ?> onclick="priceSortSearch(this);" />
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
                            <button class="icon-close d-xl-none js-filter-close" type="button" onclick="closeFilter();"></button>
                            <div class="sidebar-filters__head">
                                <h6><?php echo Label::getLabel('LBL_FILTERS'); ?></h6>
                                <a href="javascript:void(0)" class="link" onclick="clearAllFilters();"><?php echo Label::getLabel('LBL_Clear_all_Filters'); ?></a>
                            </div>
                            <div class="sidebar-filters__body" id="accordionParent">
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#all-subjects" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg?q=d#icon-category'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_CATEGORIES'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="all-subjects">
                                        <div class="filter-widget__inner">
                                            <div class="search-form mb-4">
                                                <div class="search-form__field">
                                                    <input type="text" name="category" onkeyup="onkeyupCategory()" placeholder="<?php echo Label::getLabel('LBL_SEARCH_CATEGORIES'); ?>" />
                                                </div>
                                            </div>
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php
                                                    $options = $category->options;
                                                    if (count($options) > 0) { ?>
                                                        <?php foreach ($options as $id => $option) {
                                                            $listClass = (count($option['sub_categories']) > 0) ? 'has-child' : '';
                                                            $catName = CommonHelper::renderHtml($option['name']);
                                                        ?>
                                                            <li class="<?php echo $listClass; ?> list-items-js">
                                                                <label class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="course_cate_id[]" <?php echo (in_array($id, $category->value)) ? "checked='checked'" : ''; ?> value="<?php echo $id; ?>">
                                                                    <span class="form-check-label">
                                                                        <?php echo $catName; ?>
                                                                    </span>
                                                                </label>
                                                                <?php if (count($option['sub_categories']) > 0) { ?>
                                                                    <ul class="list-vertical-child">
                                                                        <?php foreach ($option['sub_categories'] as $sid => $name) { ?>
                                                                            <li class="list-items-js">
                                                                                <label class="form-check">
                                                                                    <input class="form-check-input" type="checkbox" <?php echo (in_array($sid, $category->value)) ? "checked='checked'" : ''; ?> name="course_cate_id[]" value="<?php echo $sid; ?>">
                                                                                    <span class="form-check-label">
                                                                                        <?php echo CommonHelper::renderHtml($name); ?>
                                                                                    </span>
                                                                                </label>
                                                                            </li>
                                                                        <?php } ?>
                                                                    </ul>
                                                                <?php } ?>
                                                            </li>
                                                    <?php }
                                                    } ?>
                                                </ul>
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
                                                                <input type="text" class="priceSliderValue" name="price_from" data-index="0" placeholder="<?php echo Label::getLabel('LBL_PRICE_FROM'); ?>" onkeyup="searchByPrice();" value="<?php echo $priceFrom->value ?>">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="field-set">
                                                                <input type="text" class="priceSliderValue" name="price_till" data-index="1" placeholder="<?php echo Label::getLabel('LBL_PRICE_TILL'); ?>" onkeyup="searchByPrice();" value="<?php echo $priceTill->value ?>">
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
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-ratings" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-teacher-level'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_RATING'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-ratings">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php $options = $ratings->options;
                                                    if (count($options) > 0) {
                                                        foreach ($options as $id => $option) { ?>
                                                            <li>
                                                                <label class="form-check">
                                                                    <input class="form-check-input" type="radio" name="course_ratings" value="<?php echo $id; ?>" <?php echo $id == $ratings->value ? 'checked' : ''; ?>>
                                                                    <span class="form-check-label">
                                                                        <span class="d-flex align-items-center">
                                                                            <svg class="rating__media">
                                                                                <use xlink:href="<?php echo CONF_WEBROOT_URL ?>images/sprite.svg#rating"></use>
                                                                            </svg>
                                                                            <span><?php echo strtolower($option) ?></span>
                                                                        </span>
                                                                    </span>
                                                                </label>
                                                            </li>
                                                    <?php
                                                        }
                                                    } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#filter-course-levels" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-lesson-included'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_COURSE_LEVELS'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="filter-course-levels">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php
                                                    $options = $level->options;
                                                    if (count($options) > 0) {
                                                        foreach ($options as $id => $option) { ?>
                                                            <li>
                                                                <label class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="course_level[]" value="<?php echo $id; ?>" <?php echo in_array($id, $level->value) ? 'checked' : ''; ?> onclick="searchByFilters();">
                                                                    <span class="form-check-label">
                                                                        <?php echo $option; ?>
                                                                    </span>
                                                                </label>
                                                            </li>
                                                    <?php }
                                                    } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- filter-widget -->
                                <div class="filter-widget filter-js">
                                    <div class="filter-widget__head" data-bs-toggle="collapse" data-bs-target="#course-languages" aria-expanded="false">
                                        <span>
                                            <svg class="icon">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-globe-2'; ?>"></use>
                                            </svg>
                                        </span>
                                        <span><?php echo Label::getLabel('LBL_COURSE_LANGUAGE'); ?> <span class='count-filter-js'></span></span>
                                    </div>
                                    <div class="filter-widget__body collapse" data-bs-parent="#accordionParent" id="course-languages">
                                        <div class="filter-widget__inner">
                                            <div class="filters-scroll options-filter-js">
                                                <ul class="list-vertical">
                                                    <?php
                                                    $options = $language->options;
                                                    if (count($options) > 0) {
                                                        foreach ($options as $id => $option) { ?>
                                                            <li>
                                                                <label class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="course_clang_id[]" value="<?php echo $id; ?>" <?php echo in_array($id, $language->value) ? 'checked' : ''; ?> onclick="searchByFilters();">
                                                                    <span class="form-check-label">
                                                                        <?php echo $option; ?>
                                                                    </span>
                                                                </label>
                                                            </li>
                                                    <?php }
                                                    } ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="price_sorting" value="<?php echo $priceSorting->value; ?>" />
                        <input type="hidden" name="keyword" value="<?php echo $keyword->value; ?>" />
                        <input type="hidden" name="maxPrice" value="<?php echo $maxPrice; ?>" />
                        <input type="hidden" name="minPrice" value="<?php echo $minPrice; ?>" />
                        <?php echo $srchFrm->getFieldHtml('record_id'); ?>
                        <?php echo $srchFrm->getFieldHtml('type'); ?>
                        <?php echo $srchFrm->getFieldHtml('search_keyword'); ?>
                        <?php echo $srchFrm->getFieldHtml('pageno'); ?>
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
<script>
    var priceLbl = "<?php echo Label::getLabel('LBL_ALL_PRICE'); ?>";
    var ratingLbl = "<?php echo Label::getLabel('LBL_ALL_RATINGS'); ?>";
</script>
<script src="//www.youtube.com/player_api"></script>