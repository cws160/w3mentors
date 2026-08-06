<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<?php
$image = "background-color:#fff";
if ($bannerBg) {
    $image = "background-image: url(" . FatCache::getCachedUrl(MyUtility::makeUrl('image', 'show', [Afile::TYPE_HOME_BANNER_BACKGROUND, 0, Afile::SIZE_LARGE, $siteLangId]) . '?t=' . strtotime($bannerBg['file_added']), CONF_IMG_CACHE_TIME) . ");";
}
?>
<section class="section section--hero" style="<?php echo $image; ?>">
    <div class="container container--xxl">
        <div class="hero-panel">
            <?php $slide = current($slides); ?>
            <?php if (!empty($slideImages) && isset($slideImages[$slide['slide_id']])) { ?>
                <?php
                $imgUrl = MyUtility::makeUrl('image', 'show', [Afile::TYPE_HOME_BANNER_DESKTOP, $slide['slide_id'], Afile::SIZE_ORIGINAL, $siteLangId]);
                $desktopUrl = FatCache::getCachedUrl($imgUrl, CONF_IMG_CACHE_TIME);
                ?>
                <div class="hero-panel__media" data-aos="fade-up" data-aos-duration="2000">
                    <figure>
                        <img srcset="<?php echo $desktopUrl; ?>" alt="<?php echo $slide['slide_identifier']; ?>" loading="lazy">
                    </figure>
                </div>
            <?php } ?>
            <div class="hero-panel__content" data-aos="fade-up" data-aos-duration="1000">
                <div class="content">
                    <h2><?php echo Label::getLabel('LBL_SLIDER_TITLE_TEXT'); ?></h2>
                    <p><?php echo Label::getLabel('LBL_SLIDER_DESCRIPTION_TEXT'); ?></p>
                    <a href="<?php echo MyUtility::makeUrl('Teachers'); ?>" class="btn btn--primary-bordered"><?php echo Label::getLabel('LBL_FIND_YOUR_TUTORS'); ?></a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php if (!empty($contentBlocks)) { ?>
    <?php
    foreach ($contentBlocks as $sn => $row) {
        switch ($row['epage_block_type']) {
            case ExtraPage::BLOCK_HOME_COURSE_CATEGORIES: ?>
                <?php if (!empty($categories)) { ?>
                    <section class="section" data-aos="fade-up">
                        <div class="container container--xxl">
                            <div class="section__header">
                                <h2><?php echo Label::getLabel('LBL_MOST_DEMANDING_CATEGORIES'); ?></h2>
                            </div>
                            <div class="section__body">
                                <div class="colum-grid">
                                    <?php echo $this->includeTemplate('home/_partial/topCourseCategories.php', ['categories' => $categories], false); ?>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php
                }
                break;
            case ExtraPage::BLOCK_HOME_FEATURED_LANGUAGES:
                if (!empty($featuredLanguages)) { ?>
                    <section class="section">
                        <div class="container container--xl">
                            <div class="section__header" data-aos="fade-up" data-aos-duration="1000">
                                <h2><?php echo Label::getLabel('LBL_POPULAR_LANGUAGES'); ?></h2>
                            </div>
                            <div class="section__body" data-aos="fade-up" data-aos-duration="1000">
                                <div class="flag-wrapper">
                                    <?php echo $this->includeTemplate('home/_partial/featuredLanguages.php', ['featuredLanguages' => $featuredLanguages], false); ?>
                                </div>
                            </div>
                            <div class="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
                                <a href="<?php echo MyUtility::makeUrl('Teachers'); ?>" class="text-button"><?php echo Label::getLabel('LBL_EXPLORE_ALL_SUBJECTS'); ?><span class="circle-arrow"></span></a>
                            </div>
                        </div>
                    </section>
<?php }
                break;
            case ExtraPage::BLOCK_HOME_COURSE_WITH_CATEGORIES:
                echo $this->includeTemplate('home/_partial/coursesWithCategories.php', ['courses' => $coursesCategory, 'siteLangId' => $siteLangId, 'siteUserId' => $siteUserId], false);
                break;
            case ExtraPage::BLOCK_HOME_ONLINE_COURSES:
                echo $this->includeTemplate('home/_partial/popularOnlineCourses.php', ['courses' => $courses, 'siteLangId' => $siteLangId, 'siteUserId' => $siteUserId], false);
                break;
            case ExtraPage::BLOCK_HOME_TOP_RATED_TEACHERS:
                echo $this->includeTemplate('home/_partial/topTeachers.php', ['topRatedTeachers' => $topRatedTeachers, 'siteLangId' => $siteLangId, 'isCourseAvailable' => $isCourseAvailable], false);
                break;
            case ExtraPage::BLOCK_HOME_BROWSE_COURSES:
            case ExtraPage::BLOCK_SERVICES_WE_OFFERING:
            case ExtraPage::BLOCK_HOME_BROWSE_TUTOR:
            case ExtraPage::BLOCK_HOME_CREATING_COMMUNITY:
            case ExtraPage::BLOCK_HOME_HOW_TO_START_LEARNING:
            case ExtraPage::BLOCK_HOME_JOIN_NOW_SECTION:
                echo FatUtility::decodeHtmlEntities($row['epage_content']);
                break;
            case ExtraPage::BLOCK_HOME_CLASSES:
                echo $this->includeTemplate('home/_partial/upcomingClasses.php', ['classes' => $classes, 'bookingBefore' => $bookingBefore, 'siteUserId' => $siteUserId], false);
                break;
            case ExtraPage::BLOCK_HOME_WHY_US:
                echo '<section class="section">';
                echo FatUtility::decodeHtmlEntities($row['epage_content']);
                echo '</section>';
                break;
            case ExtraPage::BLOCK_HOME_TESTIMONIALS:
                echo $this->includeTemplate('home/_partial/testimonialList.php', ['testmonialList' => $testmonialList], false);
                break;
            case ExtraPage::BLOCK_HOME_LATEST_BLOGS:
                echo $this->includeTemplate('home/_partial/latestBlogs.php', ['blogPostsList' => $blogPostsList], false);
                break;
            case ExtraPage::BLOCK_HOME_SUBSCRIPTION_PLANS:
                echo $this->includeTemplate('home/_partial/subscriptionPlans.php', ['subscriptions' => $subscriptions, 'activePlan' => $activePlan], false);
                break;
            default:
                break;
        }
    }
}
