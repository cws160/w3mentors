<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<!-- [ MAIN BODY ========= -->
<section class="forum-body bg-grey">
    <div class="container container--narrow">
        <section class="flex-panel">
            <div class="flex-panel__large">
                <!-- [ ARTICLE DETAILS ========= -->
                <article class="article-panel" id="maindv__js" data-owner_id='<?php echo $data['user_id']; ?>' data-luser_id="<?php echo $siteUserId; ?>">
                    <div class="article-grid">
                        <div class="article-grid__left">
                            <?php
                            $totalReactionCount = abs($data['fstat_likes'] - $data['fstat_dislikes']);
                            $reactClass = '';
                            if (0 < $totalReactionCount && $data['fstat_likes'] > $data['fstat_dislikes']) {
                                $reactClass = "color-success";
                            } elseif ($data['fstat_likes'] < $data['fstat_dislikes']) {
                                $reactClass = "color-danger";
                            }
                            $upVoted = 0;
                            $downVoted = 0;
                            if (array_key_exists($data['fque_id'], $loggedUserReactions) && ForumReaction::REACTION_LIKE == $loggedUserReactions[$data['fque_id']]['freact_reaction']) {
                                $upVoted = 1;
                            } elseif (array_key_exists($data['fque_id'], $loggedUserReactions) && ForumReaction::REACTION_DISLIKE == $loggedUserReactions[$data['fque_id']]['freact_reaction']) {
                                $downVoted = 1;
                            }
                            if (0 < $data['fstat_likes'] || 0 < $data['fstat_dislikes'] || 0 < $totalReactionCount) {
                                $emptyToolTip = 'show';
                                $nonEmptyToolTip = 'hide';
                                $emptyToolTipStyle = '';
                                $nonEmptyToolTipStyle = 'display:none';
                            } else {
                                $emptyToolTip = 'show';
                                $nonEmptyToolTip = 'hide';
                                $nonEmptyToolTipStyle = '';
                                $emptyToolTipStyle = 'display:none';
                            }
                            $votesLbl = '<span id="totupcounts' . ForumReaction::REACT_TYPE_QUESTION . '_' . $data['fque_id'] . '">' . $data['fstat_likes'] . '</span>' . ' ' . Label::getLabel('LBL_Upvotes') . "<br>" . '<span id="totdowncounts' . ForumReaction::REACT_TYPE_QUESTION . '_' . $data['fque_id'] . '">' . $data['fstat_dislikes'] . '</span>' . ' ' . Label::getLabel('LBL_Downvotes');
                            ?>
                            <div class="counts">
                                <span class="counts__up">
                                    <a href="javascript:void(0);" data-upvoted="<?php echo $upVoted; ?>" data-record_id="<?php echo $data['fque_id']; ?>" data-count="<?php echo $data['fstat_likes']; ?>" onClick="upVote(this, <?php echo $data['fque_id']; ?>,<?php echo ForumReaction::REACT_TYPE_QUESTION; ?>)" id="up<?php echo ForumReaction::REACT_TYPE_QUESTION . '_' . $data['fque_id']; ?>" class="vote vote--up is-hover <?php echo (1 == $upVoted ? 'color-success' : ''); ?>">
                                        <svg class="icon icon--upvote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M2 9h3v12H2a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1zm5.293-1.293l6.4-6.4a.5.5 0 0 1 .654-.047l.853.64a1.5 1.5 0 0 1 .553 1.57L14.6 8H21a2 2 0 0 1 2 2v2.104a2 2 0 0 1-.15.762l-3.095 7.515a1 1 0 0 1-.925.619H8a1 1 0 0 1-1-1V8.414a1 1 0 0 1 .293-.707z"></path>
                                        </svg>
                                        <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_Vote_this_question_up'); ?></div>
                                    </a>
                                </span>
                                <span class="counts__middle is-hover">
                                    <span id="tot_counts<?php echo ForumReaction::REACT_TYPE_QUESTION . '_' . $data['fque_id']; ?>" data-count="<?php echo $totalReactionCount; ?>" class="vote-counts <?php echo $reactClass; ?>"><?php echo $totalReactionCount; ?></span>
                                    <div style="<?php echo $emptyToolTipStyle; ?>" id="nonempty_count<?php echo ForumReaction::REACT_TYPE_QUESTION . '_' . $data['fque_id']; ?>" class="tooltip tooltip--right bg-black <?php echo $emptyToolTip; ?>"><?php echo $votesLbl; ?></div>
                                    <div style="<?php echo $nonEmptyToolTipStyle; ?>" id="empty_count<?php echo ForumReaction::REACT_TYPE_QUESTION . '_' . $data['fque_id']; ?>" class="tooltip tooltip--right bg-black <?php echo $nonEmptyToolTip; ?>"><?php echo Label::getLabel('LBL_Awaiting_Best_Answer'); ?></div>
                                </span>
                                <span class="counts__down">
                                    <a href="javascript:void(0);" data-downVoted="<?php echo $downVoted; ?>" data-record_id="<?php echo $data['fque_id']; ?>" data-count="<?php echo $data['fstat_dislikes']; ?>" onClick="downVote(this, <?php echo $data['fque_id']; ?>,<?php echo ForumReaction::REACT_TYPE_QUESTION; ?>)" id="down<?php echo ForumReaction::REACT_TYPE_QUESTION . '_' . $data['fque_id']; ?>" class="vote vote--down is-hover <?php echo (1 == $downVoted ? 'color-danger' : ''); ?>">
                                        <svg class="icon icon--downvote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                            <path d="M22 15h-3V3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1zm-5.293 1.293l-6.4 6.4a.5.5 0 0 1-.654.047L8.8 22.1a1.5 1.5 0 0 1-.553-1.57L9.4 16H3a2 2 0 0 1-2-2v-2.104a2 2 0 0 1 .15-.762L4.246 3.62A1 1 0 0 1 5.17 3H16a1 1 0 0 1 1 1v11.586a1 1 0 0 1-.293.707z"></path>
                                        </svg>
                                        <div class="tooltip tooltip--bottom bg-black"><?php echo Label::getLabel('LBL_Vote_this_question_down'); ?></div>
                                    </a>
                                </span>
                            </div>
                        </div>
                        <div class="article-grid__right">
                            <div class="article-content">
                                <h1 class="article-title mb-3 bold-700"><?php echo $data['fque_title']; ?></h1>
                                <div class="iframe-content">
                                    <iframe onload="resetIframe(this)" src="<?php echo MyUtility::makeUrl('Forum', 'frame', [$data['fque_id']]); ?>" style="border:none;width: 100%;height: 30px;"></iframe>
                                </div>
                            </div>
                            <?php if (!empty($tags)) { ?>
                                <div class="tags">
                                    <div class="tags__overflow">
                                        <?php foreach ($tags as $key => $name) { ?>
                                            <a href="<?php echo MyUtility::makeUrl('Forum') . '?tag=' . $name . '-' . $key; ?>" class="tags__item badge badge--curve color-primary">
                                                <?php echo $name; ?>
                                            </a>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="article-stats">
                        <div class="article-stats__left">
                            <div class="article-author">
                                <figure class="article-author__avatar">
                                    <div class="avtar avtar--xsmall avtar--round bg-gray-500" data-title="J">
                                        <img src="<?php echo MyUtility::makeUrl('Image', 'show', array(Afile::TYPE_USER_PROFILE_IMAGE, $data['user_id'], Afile::SIZE_SMALL)); ?>" alt="<?php echo $data['user_first_name']; ?>" title="<?php echo $data['user_first_name']; ?>">
                                    </div>
                                </figure>
                                <div class="article-author__content">
                                    <span><?php echo Label::getLabel('LBL_Asked_By:'); ?>&nbsp;<strong><?php echo $data['user_first_name'] . ' ' . $data['user_last_name']; ?>,</strong> </span>
                                    <date class="color-gray-1000"> <?php echo MyDate::getDateTimeDifference($data['fque_updated_on'], date('Y-m-d H:i:s'), true); ?></date>
                                </div>
                            </div>
                        </div>
                        <div class="article-stats__right">
                            <nav class="article-actions">
                                <ul>
                                    <?php if ($siteUserId != $data['user_id'] && empty($reportedData)) { ?>
                                        <li class='spam-lnk-js'>
                                            <a class="article-actions__trigger" href="javascript:void(0);" onClick="reportQuestion(<?php echo $data['fque_id']; ?>);" ?>
                                                <svg class="icon icon--report me-1">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/sprite.svg#icon-report"></use>
                                                </svg>
                                                <span><?php echo Label::getLabel('LBL_Report'); ?></span>
                                            </a>
                                        </li>
                                    <?php }
                                    if (0 < $data['fstat_comments'] || 1 == $data['fque_comments_allowed']) { ?>
                                        <li>
                                            <button data-target="#_comments" class="goto-comments-js article-actions__trigger">
                                                <span class="view-comments-section-js" title="<?php echo Label::getLabel('LBL_ANSWERS'); ?>">
                                                    <svg class="icon icon--chat">
                                                        <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/sprite.svg#icon-chat"></use>
                                                    </svg>
                                                    <span><?php echo nl2br($data['fstat_comments']); ?></span>
                                                </span>
                                            </button>
                                        </li>
                                    <?php } ?>
                                    <li>
                                        <span class="article-actions__trigger" title="<?php echo Label::getLabel('LBL_VIEWS'); ?>">
                                            <svg class="icon icon--views">
                                                <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/sprite.svg#icon-views"></use>
                                            </svg>
                                            <span><?php echo $data['fstat_views']; ?></span>
                                        </span>
                                    </li>
                                    <li>
                                        <div class="share">
                                            <a href="#share-target" class="share__trigger trigger-js">
                                                <svg class="icon icon--share">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/sprite.svg#icon-share"></use>
                                                </svg>
                                                <span class="ms-1"><?php echo Label::getLabel('LBL_SHARE'); ?></span>
                                            </a>
                                            <div id="share-target" class="share__target">
                                                <ul class="social--share clearfix">
                                                    <li class="social--fb"><a class='st-custom-button' data-network="facebook" displayText='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>' title='<?php echo Label::getLabel('LBL_FACEBOOK'); ?>'><img alt="<?php echo Label::getLabel('LBL_FACEBOOK'); ?>" src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/social_01.svg" alt="<?php echo Label::getLabel('LBL_FACEBOOK'); ?>"></a></li>
                                                    <li class="social--tw"><a class='st-custom-button' data-network="twitter" displayText='<?php echo Label::getLabel('LBL_X'); ?>' title='<?php echo Label::getLabel('LBL_X'); ?>'><img alt="<?php echo Label::getLabel('LBL_X'); ?>" src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/social_02.svg" alt="<?php echo Label::getLabel('LBL_X'); ?>"></a></li>
                                                    <li class="social--pt"><a class='st-custom-button' data-network="pinterest" displayText='<?php echo Label::getLabel('LBL_PINTEREST'); ?>' title='<?php echo Label::getLabel('LBL_PINTEREST'); ?>'><img alt="<?php echo Label::getLabel('LBL_PINTEREST'); ?>" src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/social_05.svg" alt="<?php echo Label::getLabel('LBL_PINTEREST'); ?>"></a></li>
                                                    <li class="social--mail"><a class='st-custom-button' data-network="email" displayText='<?php echo Label::getLabel('LBL_EMAIL'); ?>' title='<?php echo Label::getLabel('LBL_EMAIL'); ?>'><img alt="<?php echo Label::getLabel('LBL_EMAIL'); ?>" src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/social_06.svg" alt="<?php echo Label::getLabel('LBL_EMAIL'); ?>"></a></li>
                                                </ul>
                                            </div>
                                        </div>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </article>
                <!-- ] -->
                <?php if (ForumQuestion::FORUM_QUE_PUBLISHED == $data['fque_status'] && AppConstant::YES == $data['fque_comments_allowed']) { ?>
                    <!-- [ ARTICLE COMMENTS ========= -->
                    <article class="article-panel">
                        <div class="article-comment">
                            <?php if (0 < $siteUserId) { ?>
                                <div class="article-comment__left">
                                    <figure class="avtar avtar--xsmall avtar--round bg-gray-500" data-title="<?php echo $siteUser['user_first_name'][0]; ?>">
                                        <img src="<?php echo MyUtility::makeUrl('Image', 'show', array(Afile::TYPE_USER_PROFILE_IMAGE, $siteUserId, Afile::SIZE_SMALL)); ?>" alt="<?php echo $siteUser['user_first_name'] . ' ' . $siteUser['user_last_name']; ?>">
                                    </figure>
                                </div>
                                <div class="article-comment__right">
                                    <div class="comment-panel">
                                        <a href="javascript:void(0)" class="comment-panel__trigger comment-trigger-js"><?php echo Label::getLabel('LBL_Add_a_comment_follow_up_question_or_thank_you_note'); ?></a>
                                        <div id="commentBox" class="comment-panel__target comment-target-js" style="display:none;">
                                            <?php
                                            $commFrmObj->setFormTagAttribute('onSubmit', 'addComment(this); return false;');
                                            $commFrmObj->setFormTagAttribute('class', 'form');
                                            $commFrmObj->developerTags['colClassPrefix'] = 'col-md-';
                                            $commFrmObj->developerTags['fld_default_col'] = 12;
                                            $fld = $commFrmObj->getField('fcomm_comment');
                                            $fld->setFieldTagAttribute('placeholder', Label::getLabel('LBL_Add_a_comment_follow_up_question_or_thank_you_note'));
                                            $fld->setFieldTagAttribute('id', 'fcomm_comment');
                                            $fld = $commFrmObj->getField('btn_submit');
                                            $fld->htmlAfterField = '<a href="javascript:void(0);" class="btn btn--primary-bordered comment-trigger-js">' . Label::getLabel('LBL_cancel') . '</a>';
                                            ?>
                                            <div class="form">
                                                <?php echo $commFrmObj->getFormTag(); ?>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="field-set">
                                                            <div class="field-wraper">
                                                                <div class="field_cover field-count">
                                                                    <?php echo $commFrmObj->getFieldHtml('fcomm_comment'); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-auto">
                                                        <div class="step-actions">
                                                            <?php echo $commFrmObj->getFieldHtml('fcomm_fque_id'); ?>
                                                            <?php echo $commFrmObj->getFieldHtml('btn_submit'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } else { ?>
                                <div class="article-comment__left">
                                    <figure class="avtar avtar-round avtar--xsmall bg-white">
                                        <img src="<?php echo CONF_WEBROOT_FRONT_URL; ?>images/forum/guest-user.png" alt="<?php echo Label::getLabel('LBL_Guest_User_image'); ?>">
                                    </figure>
                                </div>
                                <div class="article-comment__right">
                                    <div class="comment-panel">
                                        <a href="javascript:void(0)" class="comment-panel__trigger" onclick="showSigninForm();"><?php echo Label::getLabel('LBL_Add_a_comment_follow_up_question_or_thank_you_note'); ?></a>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </article>
                <?php } elseif (0 < $data['fstat_comments'] && 1 != $data['fque_comments_allowed']) { ?>
                    <article class="article-panel">
                        <div class="article-comment">
                            <?php echo Label::getLabel('LBL_Forum_No_Further_Comments_allowed'); ?>
                        </div>
                    </article>
                <?php } ?>
                <!-- ] -->
                <!-- [ ARTICLE RESULT ========= -->
                <?php if (1 == $data['fque_comments_allowed'] || 0 < $data['fstat_comments']) { ?>
                    <div class="article-result">
                        <div class="article-result__head" id="_comments">
                            <div>
                                <h4><?php echo Label::getLabel('LBL_Forum_question_Comments') . '&nbsp;<span id="comments-count">' . $data['fstat_comments'] . '</span>'; ?></h4>
                            </div>
                            <div class="sorting" id="sorting-js">
                                <a href="javascript:void(0)" class="sorting__trigger sorting-trigger-js">
                                    <svg class="icon icon--small svg-icon" viewBox="0 0 16 12.632">
                                        <path d="M7.579 9.263v1.684H0V9.263zm1.684-4.211v1.684H0V5.053zM7.579.842v1.684H0V.842zM13.474 12.632l-2.527-3.789H16z"></path>
                                        <path d="M12.632 2.105h1.684v7.579h-1.684z"></path>
                                        <path d="M13.473 0L16 3.789h-5.053z"></path>
                                    </svg>
                                    <span class="sorting__label"><?php echo Label::getLabel('LBL_Sort_By'); ?>:</span>
                                    <span class="sorting__value"></span>
                                </a>
                                <div class="sorting__target sorting-target-js" style="display:none;">
                                    <div class="filter-dropdown">
                                        <div class="select-list select-list--vertical select-list--scroll">
                                            <ul id="sort_radio_list_js">
                                                <li>
                                                    <label class="select-option">
                                                        <input class="select-option__input" type="radio" name="sort_option" value="latest" checked="">
                                                        <span id="latest_js" class="select-option__item sorting-js default-js" data-que_id="<?php echo $data['fque_id']; ?>" data-option="latest"><?php echo Label::getLabel('LBL_Newest_First'); ?></span>
                                                    </label>
                                                </li>
                                                <li>
                                                    <label class="select-option">
                                                        <input class="select-option__input" type="radio" name="sort_option" value="most_liked">
                                                        <span id="most_liked_js" class="select-option__item sorting-js" data-que_id="<?php echo $data['fque_id']; ?>" data-option="most_liked"><?php echo Label::getLabel('LBL_Most_Liked'); ?></span>
                                                    </label>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- ] -->
                        <!-- [ ARTICLE ANSWERS ========= -->
                        <div class="article-result__body">
                            <div class="article-list">
                                <div id="comments--listing"></div>
                            </div>
                        </div>
                        <!-- ] -->
                    </div>
                <?php } ?>
            </div>
            <?php
            $vars = ['topRatedTeachers' => $topRatedTeachers, 'popularTags' => $popularTags, 'recommendedPosts' => $recommendedPosts];
            echo $this->includeTemplate('forum/right-side-bar.php', $vars, false);
            ?>
        </section>
    </div>
</section>
<script>
    var errs = {
        not_owner_of_question: '<?php echo Label::getLabel('LBL_Sorry_You_are_not_the_owner_of_this_question') ?>',
    };
    var queUserId = <?php echo $data['user_id']; ?>;
    var loggedUserId = <?php echo $siteUserId; ?>;
</script>
<?php echo $this->includeTemplate('_partial/shareThisScript.php'); ?>