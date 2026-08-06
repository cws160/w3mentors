<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<section class="section bg-grey section--blogs pb-5">
    <div class="container container--narrow">
        <div class="row g-3 g-md-5">
            <?php $this->includeTemplate('_partial/blogSidePanel.php'); ?>
            <div class="col-xl-9">
                <div id="listItem" class="blog-search-results">
                    <!--Post details start here-->
                    <div class="blog-panel">
                        <div class="blog-panel__head">
                            <div class="slider-single slider-single-js">
                                <?php foreach ($post_images as $post_image) { ?>
                                    <div>
                                        <div class="blog__media"><img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('image', 'showById', [$post_image['file_id'], Afile::SIZE_LARGE]), CONF_DEF_CACHE_TIME, '.' . current(array_reverse(explode(".", $post_image['file_name'])))); ?>" alt="<?php echo $blogPostData['post_title']; ?>"></div>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                        <div class="blog-panel__body padding-6">
                            <span class="blog-cate color-primary">
                                <?php
                                $categoryIds = !empty($blogPostData['categoryIds']) ? explode(',', $blogPostData['categoryIds']) : array();
                                $categoryNames = !empty($blogPostData['categoryNames']) ? explode('~', $blogPostData['categoryNames']) : array();
                                $categories = array_combine($categoryIds, $categoryNames);
                                if (!empty($categories)) {
                                    foreach ($categories as $id => $name) {
                                        if ($name == end($categories)) {
                                ?>
                                            <a href="<?php echo MyUtility::makeUrl('Blog', 'category', array($id)); ?>" class="text--dark"><?php echo $name; ?></a>
                                        <?php
                                            break;
                                        }
                                        ?>
                                        <a href="<?php echo MyUtility::makeUrl('Blog', 'category', array($id)); ?>" class="text--dark"><?php echo $name; ?></a>,
                                <?php
                                    }
                                }
                                ?>
                            </span>
                            <h1 class="blog-h1"><?php echo $blogPostData['post_title']; ?></h1>
                            <hr>
                            <div class="row row--cols align-items-center justify-content-between">
                                <div class="col-xl-6 col-lg-5 col-md-5 col-sm-5">
                                    <span class="blog__author -display-inline">
                                        <div class="avtar avtar--xsmall avtar--round -display-inline" data-title="<?php echo CommonHelper::getFirstChar($blogPostData['post_author_name']); ?>">
                                        </div>
                                        <strong><?php echo Label::getLabel('Lbl_By'); ?></strong> <a href="javascript:void(0)" class="text--dark"><?php echo $blogPostData['post_author_name']; ?></a>
                                    </span>
                                </div>
                                <div class="col-xl-6 col-lg-7 col-md-7  col-sm-7 -align-right">
                                    <div class="blog__actions">
                                        <span>
                                            <?php echo MyDate::showDate($blogPostData['post_published_on']); ?>
                                        </span>
                                        <?php if (($blogPostData['post_comment_opened'] == AppConstant::YES && $siteUserId > 0) || $commentsCount > 0) { ?>
                                            &nbsp;&nbsp; | &nbsp;&nbsp;
                                            <a href="javascript:void(0)" class="blog__action -display-inline goto-comments-js" data-target="#comments-section">
                                                <span class="inline-icon -display-inline -color-fill">
                                                    <span class="svg-icon">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="17" height="17" viewBox="0 0 57 57">
                                                            <path d="M37.4,30.28H19.594a1.781,1.781,0,1,0,0,3.562H37.4A1.781,1.781,0,1,0,37.4,30.28Zm3.562-10.685H16.033a1.781,1.781,0,1,0,0,3.562H40.965A1.781,1.781,0,1,0,40.965,19.595ZM28.5,0C12.763,0,0,11.167,0,24.937c0,7.87,4.176,14.876,10.685,19.446v12.61L23.172,49.42a32.715,32.715,0,0,0,5.327.451c15.738,0,28.495-11.163,28.495-24.933S44.237,0,28.5,0Zm0,46.3a28.819,28.819,0,0,1-6.019-.654L14.095,50.7l0.112-8.26C7.78,38.569,3.566,32.179,3.566,24.937c0-11.8,11.163-21.371,24.933-21.371s24.933,9.569,24.933,21.371S42.269,46.309,28.5,46.309Z" />
                                                        </svg>
                                                    </span>
                                                </span>
                                                <?php echo $commentsCount; ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                            <hr />
                            <div class="cms-container">
                                <?php echo FatUtility::decodeHtmlEntities($blogPostData['post_description']); ?>
                            </div>
                        </div>
                        <div class="blog-panel__footer padding-6 border-top">
                            <ul class="social--share clearfix">
                                <li class="social--fb"><a class='st-custom-button' data-network="facebook" displayText='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>' title='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_01.svg" alt="<?php echo Label::getLabel('LBL_FACEBOOK'); ?>"></a></li>
                                <li class="social--tw"><a class='st-custom-button' data-network="twitter" displayText='<?php echo Label::getLabel('LBL_X'); ?>' title='<?php echo Label::getLabel('LBL_X'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_02.svg" alt="<?php echo Label::getLabel('LBL_X'); ?>"></a></li>
                                <li class="social--pt"><a class='st-custom-button' data-network="pinterest" displayText='<?php echo Label::getLabel('LBL_PINTEREST'); ?>' title='<?php echo Label::getLabel('LBL_PINTEREST'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_05.svg" alt="<?php echo Label::getLabel('LBL_PINTEREST'); ?>"></a></li>
                                <li class="social--mail"><a class='st-custom-button' data-network="email" displayText='<?php echo Label::getLabel('LBL_EMAIL'); ?>' title='<?php echo Label::getLabel('LBL_EMAIL'); ?>'><img src="<?php echo CONF_WEBROOT_URL; ?>images/social_06.svg" alt="<?php echo Label::getLabel('LBL_EMAIL'); ?>"></a></li>
                            </ul>
                        </div>
                    </div>
                    <!--Post details end here-->
                    <!--Post comments start here-->
                    <?php if (($blogPostData['post_comment_opened'] && $siteUserId > 0) || $commentsCount > 0) { ?>
                        <div class="blog-panel" id="comments-section">
                            <div class="blog-panel__head padding-6 border-bottom">
                                <h5><?php echo ($commentsCount) ? sprintf(Label::getLabel('Lbl_Comments(%s)'), $commentsCount) : Label::getLabel('LBL_COMMENTS'); ?></h5>
                                <?php echo $srchCommentsFrm->getFormHtml(); ?>
                            </div>
                            <div class="blog-panel__body padding-6">
                                <?php if ($commentsCount > 0) { ?>
                                    <div class="container--repeated">
                                        <div id="comments--listing"></div>
                                    </div>
                                <?php } ?>
                                <?php if ($blogPostData['post_comment_opened'] && $siteUserId > 0) { ?>
                                    <div id="form-comments" class="form--comments">
                                        <h4><?php echo Label::getLabel('Lbl_Post_your_comments'); ?></h4>
                                        <?php
                                        $postCommentFrm->setFormTagAttribute('class', 'form');
                                        $postCommentFrm->setFormTagAttribute('onsubmit', 'setupPostComment(this);return false;');
                                        $postCommentFrm->developerTags['colClassPrefix'] = 'col-md-';
                                        $postCommentFrm->developerTags['fld_default_col'] = '12';
                                        $nameFld = $postCommentFrm->getField('bpcomment_author_name');
                                        $nameFld->addFieldTagAttribute('readonly', true);
                                        $nameFld->developerTags['col'] = 6;
                                        $emailFld = $postCommentFrm->getField('bpcomment_author_email');
                                        $emailFld->addFieldTagAttribute('readonly', true);
                                        $emailFld->developerTags['col'] = 6;
                                        echo $postCommentFrm->getFormHtml();
                                        ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <!--Post comments end here-->
        </div>
    </div>
    </div>
</section>
<script>
    var boolLoadComments = (<?php echo FatUtility::int($blogPostData['post_comment_opened']); ?>) ? true : false;
    <?php if ($commentsCount > 0) { ?>
        $(document).ready(function() {
            searchComments(<?php echo $blogPostData['post_id']; ?>, 1);
        });
    <?php } ?>
    /* BLOG SLIDER */
    $('.slider-single-js').slick({
        dots: false,
        arrows: true,
        autoplay: true,
        adaptiveHeight: true,
        rtl: langLbl.layoutDirection == 'rtl',
        prevArrow: '<button data-role="none" class="slick-prev" aria-label="previous"></button>',
        nextArrow: '<button data-role="none" class="slick-next" aria-label="next"></button>'
    });
    /* FOR LEFT LINKS */
    $('.link--toggle-js').click(function() {
        if ($(this).hasClass('is-active')) {
            $(this).removeClass('is-active');
            $(this).next('.nav--toggled-js > ul > li ul').find('.link--toggle-js').removeClass('is-active');
            $(this).next('.nav--toggled-js > ul > li ul').slideUp();
            $(this).next('.nav--toggled-js > ul > li ul').find('.nav--toggled-js > ul > li ul').slideUp();
            return false;
        }
        $('.link--toggle-js').removeClass('is-active');
        $(this).addClass("is-active");
        $(this).parents('ul').each(function() {
            $(this).siblings('span').addClass('is-active');
        });
        $(this).closest('ul').find('li .nav--toggled-js > ul > li ul').slideUp();
        $(this).next('.nav--toggled-js > ul > li ul').slideDown();
    });
    /* FOR BLOG FILTERS */
    $('.blog-toggle-js').click(function() {
        $(this).toggleClass("is-active");
        $('html').toggleClass("show-categories-js");
    });

    $('.goto-comments-js').click(function() {
        let target = $(this).attr('data-target');
        if ($(target).length > 0) {
            $('html, body').animate({
                scrollTop: $(target).offset().top - 100
            }, 1000);
        }
    });
</script>
<style>
    .box--blog a {
        color: #0037B4;
    }
</style>
<?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>