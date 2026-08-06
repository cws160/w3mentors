<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<aside class="flex-panel__small">
    <div class="article-side">
        <!-- [ RECOMMENDED POSTS ========= -->
        <?php if (0 < count($recommendedPosts)) { ?>
            <div class="article-widget border-bottom">
                <div class="article-widget__head">
                    <h5><?php echo Label::getLabel('LBL_Recommended_Posts'); ?></h5>
                </div>
                <div class="article-widget__body p-0">
                    <div class="article-list">
                        <?php foreach ($recommendedPosts as $val) { ?>
                            <div class="mini-article">
                                <p class="mini-article__title mb-2"><a href="<?php echo MyUtility::makeUrl('Forum', 'View', [$val['fque_slug']], CONF_WEBROOT_FRONT_URL); ?>" class="snakeline-hover"><?php echo $val['fque_title']; ?></a></p>
                                <div class="mini-article__stats">
                                    <small class="me-3"><?php echo $val['fstat_likes'] . " " . Label::getLabel('LBL_Upvotes'); ?></small>
                                    <small class="me-3"><?php echo $val['fstat_comments'] . " " . Label::getLabel('LBL_Answers'); ?></small>
                                    <small><?php echo $val['fstat_views'] . " " . Label::getLabel('LBL_Views'); ?></small>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
        <!-- ] -->
        <!-- [ POPULAR TAGS ========= -->
        <?php if (0 < count($popularTags)) { ?>
            <div class="article-widget border-bottom">
                <div class="article-widget__head">
                    <h5><?php echo Label::getLabel('LBL_Popular_Tags'); ?></h5>
                </div>
                <div class="article-widget__body">
                    <div class="tags">
                        <?php foreach ($popularTags as $key => $tag) { ?>
                            <a href="<?php echo MyUtility::makeUrl('Forum') . '?tag=' . $tag . '-' . $key; ?>" class="tags__item badge badge--curve color-primary"><?php echo $tag; ?></a>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
        <!-- ] -->
        <!-- [ POPULAR TUTORS ========= -->
        <?php if (0 < count($topRatedTeachers)) { ?>
            <div class="article-widget border-bottom">
                <div class="article-widget__head">
                    <h5><?php echo Label::getLabel('LBL_Community_Experts'); ?></h5>
                </div>
                <div class="article-widget__body p-0">
                    <div class="article-authors">
                        <?php foreach ($topRatedTeachers as $keyt => $teacher) { ?>
                            <div class="article-authors__item">
                                <a href="<?php echo MyUtility::makeUrl('Teachers', 'view', [$teacher['user_username']]); ?>" class="profile-meta d-flex align-items-start gap-3">
                                    <div class="profile-meta__media">
                                        <span class="avtar avtar--medium avtar--round" data-title="M">
                                            <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $teacher['user_id'], Afile::SIZE_MEDIUM]), CONF_IMG_CACHE_TIME) ?>" alt="<?php echo $teacher['full_name']; ?>">
                                        </span>
                                    </div>
                                    <div class="profile-meta__details">
                                        <span class="bold-600 color-black mb-1 d-block"><?php echo $teacher['full_name']; ?></span>
                                        <div class="rating">
                                            <svg class="rating__media" viewBox="0 0 15.402 14.648" id="rating" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M7.701 11.984l-4.759 2.664L4.005 9.3l-4-3.7 5.408-.647L7.701 0l2.285 4.953 5.416.642-4 3.7 1.063 5.35z" />
                                            </svg>
                                            <span class="rating__value"><?php echo $teacher['testat_ratings']; ?></span>
                                            <span class="rating__count"><?php echo $teacher['testat_reviewes'] . '&nbsp;' . Label::getLabel('LBL_REVIEW(S)'); ?></span>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php } ?>
        <!-- ] -->
    </div>
    <div class="article-side article-side--sticky">
        <div class="article-widget">
            <div class="article-widget__head align-center">
                <h5><?php echo Label::getLabel('LBL_Feeling_Stuck?'); ?></h5>
            </div>
            <div class="article-widget__body">
                <div class="align-center">
                    <img src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/stuck-meda.svg" alt="" style="margin:0 auto;">
                    <div class="m-3 p-2 mb-0">
                        <p><?php echo Label::getLabel('LBL_Ask_our_expert_tutors_a_question'); ?> <span class="color-secondary bold-600"><?php echo Label::getLabel('LBL_Its_free'); ?></span></p>
                    </div>
                </div>
                <a href="javascript:void(0);" onclick="forum.addNewQuestion('maindv__js');" class="btn btn--secondary btn--block">
                    <svg class="icon icon--qmark me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/sprite.svg#q-mark"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_Ask_a_Question'); ?></span>
                </a>
            </div>
        </div>
    </div>
</aside>