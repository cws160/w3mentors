<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$srchFrmObj->setFormTagAttribute('id', 'srchQuestionForm');
$srchFrmObj->setFormTagAttribute('onsubmit', 'forumSearch.searchByKeyWord(this); return false;');
$fld = $srchFrmObj->getField('keyword');
$fld->addFieldTagAttribute('placeholder', $fld->getCaption());
$fld->addFieldTagAttribute('id', 'keyword');
$fld = $srchFrmObj->getField('tag_id');
$fld->addFieldTagAttribute('id', 'tag_id');
$fld = $srchFrmObj->getField('pageno');
$fld->addFieldTagAttribute('id', 'pageno');
?>
<!-- [ MAIN BODY ========= -->
<section class="forum-header section bg-gradiant section--page-header text-center">
    <div class="container container--narrow" id="maindv__js" data-luser_id="<?php echo $siteUserId; ?>">
        <hgroup>
            <h1><?php echo Label::getLabel('LBL_Got_a_question?'); ?> </h1>
            <h4>
                <?php
                $lbl = Label::getLabel('LBL_Ask_{tot-tutots-count}+_expert_tutors_from_all_over_the_world!');
                $repVars = ['{tot-tutots-count}' => $totalTutors];
                echo CommonHelper::replaceStringData($lbl, $repVars);
                ?>
            </h4>
        </hgroup>
        <?php echo $srchFrmObj->getFormTag(); ?>
        <div class="main-search">
            <div class="forum-search">
                <form>
                    <div class="main-search__field">
                        <?php echo $srchFrmObj->getFieldHTML('keyword'); ?>
                    </div>
                    <div class="main-search__action">
                        <div class="main-search__submit">
                            <?php echo $srchFrmObj->getFieldHTML('btn_submit'); ?>
                            <svg class="icon icon--search">
                                <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/sprite.svg#search"></use>
                            </svg>
                        </div>
                        <div class="main-search__reset" onclick="forumSearch.resetForumSearch(this);" style="display: none;" title="Reset">
                            <span class="close"></span>
                        </div>
                    </div>
                    <?php
                    echo $srchFrmObj->getFieldHTML('tag_id');
                    echo $srchFrmObj->getFieldHTML('search_type');
                    echo $srchFrmObj->getFieldHTML('pageno');
                    echo $srchFrmObj->getExternalJS();
                    ?>
                </form>
            </div>
        </div>
        <?php if (0 < count($popularTags)) { ?>
            <div class="tags tags--overflow mt-3 d-sm-block d-none">
                <span class="d-block d-sm-inline-flex mb-3"><?php echo Label::getLabel('LBL_Popular_Tags'); ?>:</span>
                <div class="tags__overflow">
                    <?php foreach ($popularTags as $key => $name) { ?>
                        <a href="<?php echo MyUtility::makeUrl('Forum') . '?tag=' . $name . '-' . $key; ?>" class="tags__item badge color-primary badge--curve color-primary"><?php echo $name; ?></a>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

    </div>
</section>
<section class="forum-body">
    <div class="container container--narrow">
        <nav class="tabs-wrapper  mb-5">
            <nav class="tabs text-center tabs-scrollable-js">
                <ul id="srch_type_tabs" class="d-inline-flex">
                    <?php
                    foreach ($srchTypes as $typeId => $typeName) {
                        echo '<li class="srch_type ' . ($srchWithType == $typeId ? 'is-active' : '') . '"><a class="' . (ForumQuestionSearch::TYPE_ALL == $typeId ? 'default_srch_type' : '') . ' search-type" data-search_type="' . $typeId . '" onclick="forumSearch.setSearchByType(this); return false;" href="javascript:void(0);">' . $typeName . '</a></li>';
                    }
                    ?>
                </ul>
            </nav>
        </nav>
        <div class="forum-stat mb-5">
            <div class="forum-stat__content">
                <?php if (1 > $siteUserId) { ?>
                    <h3 class="mb-3"><?php echo Label::getLabel('LBL_Forum_questions_listing_page_guest_user_main_heading'); ?></h3>
                    <p class="mb-5"><?php echo Label::getLabel('LBL_Forum_Questions_Listing_Page_guest_user_sub_heading'); ?></p>
                    <?php $this->includeTemplate('guest-user/_partial/learner-social-media-signup.php', ['isForumPage' => true], false); ?>

                <?php } else { ?>
                    <h3 class="mb-3 bold-700"><?php echo Label::getLabel('LBL_Join_the_biggest_community_of_learners_for_free'); ?></h3>
                    <p class="mb-5">
                        <?php echo Label::getLabel('LBL_Sign_up_to_ask_our_experts_any_questions_and_get_helpful_tips_in_your_inbox'); ?>
                    </p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="<?php echo MyUtility::makeUrl('Teachers'); ?>" class="btn btn--primary"><span><?php echo Label::getLabel('LBL_Find_Community_Experts'); ?></span></a>
                        <a onclick="forum.addNewQuestion('maindv__js');" href="javascript:void(0);" class="btn btn--primary-bordered"><span><?php echo Label::getLabel('LBL_Ask_a_Question'); ?></span></a>
                    </div>
                <?php } ?>
            </div>
            <div class="forum-stat__count">
                <div class="forum-counts">
                    <span class="forum-counts__item">
                        <h5><?php echo $totalQuestions; ?></h5>
                        <p><?php echo Label::getLabel('LBL_questions_asked'); ?></p>
                    </span>
                    <span class="forum-counts__item">
                        <h5><?php echo $totalComments; ?></h5>
                        <p><?php echo Label::getLabel('LBL_tutors_answers'); ?></p>
                    </span>
                    <span class="forum-counts__item">
                        <h5><?php echo $totalTutors; ?></h5>
                        <p><?php echo Label::getLabel('LBL_active_tutors'); ?></p>
                    </span>
                </div>
            </div>
            <div class="forum-stat__media">
                <img src="<?php echo CONF_WEBROOT_URL; ?>images/forum/cta-graphic.svg" alt="CTA Image">
            </div>
        </div>
        <section class="flex-panel">
            <div class="flex-panel__large" id="listing"></div>
            <?php
            $vars = [
                'topRatedTeachers' => $topRatedTeachers,
                'popularTags' => $popularTags,
                'recommendedPosts' => $recommendedPosts,
            ];
            echo $this->includeTemplate('forum/right-side-bar.php', $vars, false);
            ?>
        </section>
    </div>
</section>
<!-- ] -->
<!-- [ SIDE BAR SECONDARY ========= -->
<script>
    $(document).ready(function() {
        $('input[name="keyword"]').on('keyup', function(event) {
            if ((event.keyCode == 13 && $(this).val() != '')) {
                $(".main-search__submit").hide();
                $(".main-search__reset").show();
            }
            if ($(this).val() == '') {
                $(".main-search__submit").show();
                $(".main-search__reset").hide();
                forumSearch.searchByKeyWord(this);
            }

        });
    });
    forumSearch.baseUrl = '<?php echo MyUtility::makeUrl(); ?>';
</script>