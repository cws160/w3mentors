<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if ($blogPostsList) { ?>
    <section class="section section--cardslider">
        <div class="container container--xxl">
            <div class="section__header" data-aos="fade-up" data-aos-duration="1000">
                <h2><?php echo Label::getLabel('LBL_LATEST_BLOGS'); ?></h2>
            </div>
            <div class="section__body" data-aos="fade-up" data-aos-duration="1000">
                <div class="blog-wrapper">
                    <div class="slider slider--onehalf slider-onethird-js">
                        <?php foreach ($blogPostsList as $postDetail) { ?>
                            <div class="slider__item">
                                <div class="blog-card">
                                    <div class="blog-card__head">
                                        <div class="blog-card-media ratio ratio--3by2">
                                            <a href="<?php echo MyUtility::makeUrl('Blog', 'PostDetail', [$postDetail['post_id']]); ?>">
                                                <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_BLOG_POST_IMAGE, $postDetail['post_id'], Afile::SIZE_MEDIUM]), CONF_DEF_CACHE_TIME) ?>" alt="<?php echo $postDetail['post_title']; ?>">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="blog-card__body">
                                        <div class="blog-card-details">
                                            <div class="blog-card-tags">
                                                <div class="blog-card-category">
                                                    <?php echo ucfirst($postDetail['bpcategory_name'] ?? $postDetail['bpcategory_identifier']); ?>
                                                </div>
                                                <div class="blog-card-date">
                                                    <span><?php echo MyDate::showDate($postDetail['post_published_on']); ?></span>
                                                </div>
                                            </div>
                                            <div class="blog-card-title">
                                                <h3><?php echo CommonHelper::renderHtml($postDetail['post_title']) ?></h3>
                                            </div>
                                            <a href="<?php echo MyUtility::makeUrl('Blog', 'PostDetail', [$postDetail['post_id']]); ?>" class="btn btn--primary-bordered"><?php echo Label::getLabel('LBL_VIEW_BLOG'); ?></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
            <div class="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
                <a href="<?php echo MyUtility::makeUrl('Blog'); ?>" class="text-button"><?php echo Label::getLabel('LBL_VIEW_ALL'); ?><span class="circle-arrow"></span></a>
            </div>
        </div>
    </section>
<?php
}
