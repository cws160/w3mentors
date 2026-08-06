<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php $bpCategoryId = isset($bpCategoryId) ? $bpCategoryId : 0; ?>

<section class="forum-header section bg-gradiant section--page-header text-center">
    <div class="container container--narrow">
        <h1><?php echo Label::getLabel('LBL_Blog'); ?></h1>
        <p class="p-large"><?php echo Label::getLabel('LBL_The_place_where_we_write_some_words'); ?></p>
        <div class="main-search d-flex gap-4">
            <form class="flex-1" method="post" onsubmit="searchBlogs(this);return false;">
                <div class="main-search__field">
                    <input class="blog-keyword" placeholder="<?php echo Label::getLabel('LBL_Blog_Search'); ?>" name="keyword" type="text" />
                </div>
                <span class="main-search__action">
                    <div class="main-search__submit">
                        <input value="" type="submit" />
                        <svg class="icon icon--search">
                            <use xlink:href="<?php echo CONF_WEBROOT_FRONTEND; ?>images/sprite.svg#search"></use>
                        </svg>
                    </div>
                    <div class="main-search__reset" onclick="clearKeyword();" style="display: none;" title="Reset">
                        <span class="close"></span>
                    </div>
                </span>
            </form>
            <a href="javascript:void(0)" class="blog-toggle blog-toggle-js d-xl-none"><span></span></a>
        </div>
    </div>
</section>

<section class="section section--nav p-0">
    <div class="container container--fixed">
        <span class="overlay overlay--blog blog-toggle-js"></span>
        <nav class="nav-categories">
            <?php if (!empty($allcats)) { ?>
                <ul>
                    <li class="<?php echo ($actionName == 'index') ? 'is-active' : ''; ?>"><a href="<?php echo MyUtility::makeUrl('Blog'); ?>"><?php echo Label::getLabel('LBL_All_Blogs'); ?></a></li>
                    <?php
                    foreach ($allcats as $category) {

                        $categoryId = FatUtility::int($category['bpcategory_id']);
                        $blogPostCount =  array_sum(array_column($category['children'], 'countChildBlogPosts')) + $category['countChildBlogPosts'];
                        if ($blogPostCount > 0) {
                    ?>
                            <?php $childBlogPostCount =  array_sum(array_column($category['children'], 'countChildBlogPosts')); ?>
                            <li class="<?php echo (count($category['children']) > 0 && $childBlogPostCount > 0) ? 'has-categories-dropdown' : ''; ?> <?php echo ($bpCategoryId == $categoryId) ? 'is-active' : ''; ?>">
                                <a href="<?php echo MyUtility::makeUrl('Blog', 'category', [$categoryId]); ?>">
                                    <?php
                                    echo $category['bpcategory_name'];
                                    echo ($blogPostCount > 0) ? " (" . $blogPostCount . ")" : '';
                                    ?>
                                </a>

                                <?php if (count($category['children']) > 0 && $childBlogPostCount > 0) { ?>
                                    <span class="categories-touch-trigger cate-trigger-js"></span>
                                    <div class="has-categories-target cate-target-js">
                                        <nav class="nav nav--toggled d-block">
                                            <ul class="nav-vertical-list">
                                                <?php foreach ($category['children'] as $childCat) {
                                                    if ($childCat['countChildBlogPosts'] > 0) {
                                                ?>
                                                        <li class="nav-vertical-item <?php echo (($currCategoryId ?? 0) == $childCat['bpcategory_id']) ? 'is-active' : ''; ?>">
                                                            <a class="nav-vertical-link" href="<?php echo MyUtility::makeUrl('Blog', 'category', [$childCat['bpcategory_id']]); ?>">
                                                                <?php
                                                                echo $childCat['bpcategory_name'];
                                                                echo ($childCat['countChildBlogPosts'] > 0) ? " (" . $childCat['countChildBlogPosts'] . ")" : '';
                                                                ?>
                                                            </a>
                                                        </li>
                                                <?php }
                                                } ?>
                                            </ul>
                                        </nav>
                                    </div>
                                <?php } ?>
                            </li>
                    <?php }
                    } ?>
                </ul>
            <?php } ?>
        </nav>
    </div>
</section>
<section class="section section--blogs pt-0">
    <div class="container container--narrow">
        <div class="row">
            <div class="container">
                <div class="breadcrumb-list pb-0">
                    <ul>
                        <li><a href="<?php echo MyUtility::makeUrl(); ?>"><?php echo Label::getLabel('LBL_Home'); ?></a></li>
                        <li><a href="<?php echo MyUtility::makeUrl('Blog'); ?>"><?php echo Label::getLabel('LBL_Blog'); ?></a></li>
                        <li><?php echo (empty($categoryName) ? Label::getLabel('LBL_All') : $categoryName); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <div id='listing'></div>
    </div>
</section>
<script>
    var bpCategoryId = <?php echo !empty($currCategoryId) ? $currCategoryId : 0; ?>;
</script>
</div>
<script>
    /* FOR BLOG CATEGORIES */
    $('.blog-toggle-js').click(function() {
        $(this).toggleClass("is-active");
        $('html').toggleClass("show-categories-js");
    });


    /* FOR FOOTER TOGGLES */
    $('.cate-trigger-js').click(function() {
        if ($(this).hasClass('is-active')) {
            $(this).removeClass('is-active');
            $(this).siblings('.cate-target-js').slideUp();
            return false;
        }
        $('.cate-trigger-js').removeClass('is-active');
        $(this).addClass("is-active");
        $('.cate-target-js').slideUp();
        $(this).siblings('.cate-target-js').slideDown();
    });
</script>