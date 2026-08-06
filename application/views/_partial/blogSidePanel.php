<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="col-xl-3 order-xl-2">
    <div class="sticky-section" id="STICKY">
        <div class="form-search form-search--blog">
            <div class="d-flex gap-4">
                <form class="flex-1" method="post" onsubmit="searchBlogss(this);return false;">
                    <div class="form__element">
                        <input class="form__input blog-post-keyword" placeholder="<?php echo Label::getLabel('LBL_BLOG_SEARCHS'); ?>" name="keyword" type="text" />
                        <input class="form__input" placeholder="<?php echo Label::getLabel('LBL_BLOG_SEARCHS'); ?>" name="page" value="post-detail" type="hidden" />
                        <span class="form__action-wrap main-search__submit">
                            <input class="form__action" value="" type="submit" />
                            <span class="svg-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14.844" height="14.843" viewBox="0 0 14.844 14.843">
                                    <path d="M251.286,196.714a4.008,4.008,0,1,1,2.826-1.174A3.849,3.849,0,0,1,251.286,196.714Zm8.241,2.625-3.063-3.062a6.116,6.116,0,0,0,1.107-3.563,6.184,6.184,0,0,0-.5-2.442,6.152,6.152,0,0,0-3.348-3.348,6.271,6.271,0,0,0-4.884,0,6.152,6.152,0,0,0-3.348,3.348,6.259,6.259,0,0,0,0,4.884,6.152,6.152,0,0,0,3.348,3.348,6.274,6.274,0,0,0,6-.611l3.063,3.053a1.058,1.058,0,0,0,.8.34,1.143,1.143,0,0,0,.813-1.947h0Z" transform="translate(-245 -186.438)"></path>
                                </svg>
                            </span>
                        </span>
                        <span class="form__action-wrap main-search__reset" style="display: none;" onclick="clearKeyword();">
                            <span class="svg-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M12 10.586l4.95-4.95 1.414 1.414-4.95 4.95 4.95 4.95-1.414 1.414-4.95-4.95-4.95 4.95-1.414-1.414 4.95-4.95-4.95-4.95L7.05 5.636z" />
                                </svg>
                            </span>
                        </span>
                    </div>
                </form>
                <a href="javascript:void(0)" class="blog-toggle blog-toggle-js d-xl-none"><span></span></a>
                <a href="" id="go_back" style="display:none;"><?php echo Label::getLabel('LBL_Go_Back'); ?></a>
            </div>
        </div>
        <div class="box box--cta box--cta-blog padding-8 border align-center mt-4">
            <div class="-hide-mobile">
                <h4 class="-text-bold -color-secondary"><?php echo Label::getLabel('Lbl_Write_For_Us'); ?></h4>
                <p><?php echo Label::getLabel('Lbl_We_are_constantly_looking_for_writers_and_contributors_to_help_us_create_great_content_for_our_blog_visitors.'); ?> </p>
            </div>
            <a href="<?php echo MyUtility::makeUrl('Blog', 'contributionForm'); ?>" class="btn btn--secondary btn--block btn--large">
                <span class="svg-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M11 11V7H13V11H17V13H13V17H11V13H7V11H11ZM12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20Z"></path>
                    </svg>
                </span>
                <?php echo Label::getLabel('Lbl_Contribute'); ?></a>
        </div>
        <div class="blog-sidebar">
            <span class="overlay overlay--blog blog-toggle-js"></span>
            <div class="blog-filters">
                <div class="box border">
                    <div class="box__head border-bottom">
                        <h5><?php echo Label::getLabel('Lbl_Categories'); ?></h5>
                    </div>
                    <div class="box__body">
                        <div class="box-scroller">
                            <nav class="nav nav--toggled nav--toggled-js d-block">
                                <?php if (!empty($categoriesArr)) { ?>
                                    <ul class="nav-vertical-list">
                                        <?php foreach ($categoriesArr as $cat) { ?>
                                            <li class="nav-vertical-item <?php if (count($cat['children'])) {
                                                                                echo 'nav-vertical-has-child';
                                                                            } ?>">
                                                <a class="nav-vertical-link" href="<?php echo MyUtility::makeUrl('Blog', 'category', array($cat['bpcategory_id'])); ?>">
                                                    <?php
                                                    echo $cat['bpcategory_name'];
                                                    echo !empty($cat['countChildBlogPosts']) ? "($cat[countChildBlogPosts])" : '';
                                                    ?></a>
                                                <?php if (count($cat['children'])) { ?>
                                                    <span class="nav-vertical-trigger nav-dropdown-trigger-js"></span>
                                                    <div class="nav-vertical-target nav-dropdown-target-js">
                                                        <ul>
                                                            <?php foreach ($cat['children'] as $children) { ?>
                                                                <li>
                                                                    <a href="<?php echo MyUtility::makeUrl('Blog', 'category', array($children['bpcategory_id'])); ?>">
                                                                        <?php echo $children['bpcategory_name'];
                                                                        echo !empty($children['countChildBlogPosts']) ? "($children[countChildBlogPosts])" : '';
                                                                        ?>
                                                                    </a>
                                                                    <?php if (count($children['children'])) { ?>
                                                                        <ul>
                                                                            <?php foreach ($children['children'] as $subChildren) { ?>
                                                                                <li>
                                                                                    <a href="<?php echo MyUtility::makeUrl('Blog', 'category', array($subChildren['bpcategory_id'])); ?>"><?php echo $subChildren['bpcategory_name']; ?></a>
                                                                                </li>
                                                                            <?php } ?>
                                                                        </ul>
                                                                    <?php } ?>
                                                                </li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>
                                                <?php } ?>
                                            </li>
                                        <?php } ?>
                                    </ul>
                                <?php } ?>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
            <!--blog filters end here-->
        </div>
    </div>
</div>
<script>
    (function() {
        var uri = window.location.pathname;
        var parentCat = null;
        $('ul.nav--vertical-js li').each(function() {
            if ($(this).find('ul').length) {
                parentCat = $(this);
                $(this).find('ul li').each(function() {
                    if ($(this).find('a').attr('href') == uri) {
                        $(this).addClass('is-active');
                        $(parentCat).addClass('is-active');
                    }
                });
            } else {
                if ($(this).find('a').attr('href') == uri) {
                    $(this).addClass('is-active');
                }
            }
        });
    })();



    $('.nav-dropdown-trigger-js').click(function() {
        if ($(this).hasClass('is-active')) {
            $(this).removeClass('is-active');
            $(this).siblings('.nav-dropdown-target-js').slideUp();
            return false;
        }
        $('.nav-dropdown-trigger-js').removeClass('is-active');
        $(this).addClass("is-active");
        $('.nav-dropdown-target-js').slideUp();
        $(this).siblings('.nav-dropdown-target-js').slideDown();
    });
</script>