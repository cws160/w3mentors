<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($arrListing)) {
    foreach ($arrListing as $data) {
        $totalReactionCount = abs($data['fstat_likes'] - $data['fstat_dislikes']);
        $reactClass = '';
        if (0 < $totalReactionCount && $data['fstat_likes'] > $data['fstat_dislikes']) {
            $reactClass = "color-success";
        } elseif ($data['fstat_likes'] < $data['fstat_dislikes']) {
            $reactClass = "color-danger";
        }
?>
        <?php
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
        <div class="article-list">
            <!-- [ ARTICLE  ========= -->
            <article class="article">
                <div class="article__left">
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
                            <div style="<?php echo $nonEmptyToolTipStyle; ?>" id="empty_count<?php echo ForumReaction::REACT_TYPE_QUESTION . '_' . $data['fque_id']; ?>" class="tooltip tooltip--right bg-black <?php echo $nonEmptyToolTip; ?>"><?php echo Label::getLabel('LBL_FORUM_QUESTION_NO_REACTIONS'); ?></div>
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
                <div class="article__right">
                    <div class="article-content">
                        <h4 class="article-title mb-2 bold-700"><a href="<?php echo MyUtility::makeUrl('Forum', 'View', [$data['fque_slug']], CONF_WEBROOT_FRONT_URL); ?>" class="snakeline-hover"><?php echo $data['fque_title']; ?></a></h4>
                        <div class="article-shortdesc mt-3 mb-4">
                            <div class="article-more">
                                <div class="article-more__content" data-link="<?php echo MyUtility::makeUrl('Forum', 'view', [$data['fque_slug']]); ?>">
                                    <div class="cms-container forum-editor">
                                        <?php echo CommonHelper::renderHtml($data['fque_description']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (isset($quesTags[$data['fque_id']]) && !empty($quesTags[$data['fque_id']])) { ?>
                        <div class="tags">
                            <div class="tags__overflow">
                                <?php foreach ($quesTags[$data['fque_id']] as $tagId => $name) { ?>
                                    <a href="<?php echo MyUtility::makeUrl('Forum') . '?tag=' . $name . '-' . $tagId; ?>" class="tags__item color-primary badge badge--curve"><?php echo $name; ?></a>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                    <div class="article-stats">
                        <div class="article-stats__left">
                            <div class="article-author">
                                <figure class="article-author__avatar">
                                    <div class="avtar avtar--xsmall avtar--round bg-gray-500" data-title="J"><img src="<?php echo MyUtility::makeUrl('Image', 'show', array(Afile::TYPE_USER_PROFILE_IMAGE, $data['user_id'], Afile::SIZE_SMALL)); ?>" alt="<?php echo $data['user_first_name'] . " " . $data['user_last_name']; ?>"></div>
                                </figure>
                                <div class="article-author__content">
                                    <span><?php echo Label::getLabel('LBL_Asked_By:'); ?> <strong><?php echo $data['user_first_name'] . " " . $data['user_last_name']; ?>,</strong> </span>
                                    <date class="color-gray-1000"> <?php echo MyDate::getDateTimeDifference($data['fque_updated_on'], date('Y-m-d H:i:s'), true); ?></date>
                                </div>
                            </div>
                        </div>
                        <div class="article-stats__right">
                            <nav class="article-actions">
                                <ul>
                                    <?php if (0 < $data['fstat_comments'] || 1 == $data['fque_comments_allowed']) { ?>
                                        <li>
                                            <a href="<?php echo MyUtility::makeUrl('Forum', 'view', [$data['fque_slug']]); ?>#comments" class="article-actions__trigger" title="<?php echo Label::getLabel('LBL_ANSWERS'); ?>">
                                                <svg class="icon icon--chat">
                                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/forum/sprite.svg#icon-chat"></use>
                                                </svg>
                                                <span> <?php echo FatUtility::int($data['fstat_comments']); ?></span>
                                            </a>
                                        </li>
                                    <?php } ?>
                                    <li>
                                        <a href="javascript:void(0);" class="article-actions__trigger" title="<?php echo Label::getLabel('LBL_VIEWS'); ?>">
                                            <svg class="icon icon--views">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/forum/sprite.svg#icon-views"></use>
                                            </svg>
                                            <span> <?php echo FatUtility::int($data['fstat_views']); ?></span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </article>
            <!-- ] -->
        </div>
    <?php
    }
    $pagingArr = [
        'callBackJsFunc' => 'forumSearch.searchPaging',
        'pageSize' => $post['pagesize'],
        'page' => $post['pageno'],
        'recordCount' => $recordCount,
        'pageCount' => ceil($recordCount / $post['pagesize'])
    ];
    $this->includeTemplate('_partial/pagination.php', $pagingArr, false);
} else {
    ?>
    <div class="page-listing__body">
        <div class="box -padding-30" style="margin-bottom: 30px;">
            <div class="message-display">
                <div class="message-display__icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 408">
                        <path d="M488.468,408H23.532A23.565,23.565,0,0,1,0,384.455v-16.04a15.537,15.537,0,0,1,15.517-15.524h8.532V31.566A31.592,31.592,0,0,1,55.6,0H456.4a31.592,31.592,0,0,1,31.548,31.565V352.89h8.532A15.539,15.539,0,0,1,512,368.415v16.04A23.565,23.565,0,0,1,488.468,408ZM472.952,31.566A16.571,16.571,0,0,0,456.4,15.008H55.6A16.571,16.571,0,0,0,39.049,31.566V352.891h433.9V31.566ZM497,368.415a0.517,0.517,0,0,0-.517-0.517H287.524c0.012,0.172.026,0.343,0.026,0.517a7.5,7.5,0,0,1-7.5,7.5h-48.1a7.5,7.5,0,0,1-7.5-7.5c0-.175.014-0.346,0.026-0.517H15.517a0.517,0.517,0,0,0-.517.517v16.04a8.543,8.543,0,0,0,8.532,8.537H488.468A8.543,8.543,0,0,0,497,384.455h0v-16.04ZM63.613,32.081H448.387a7.5,7.5,0,0,1,0,15.008H63.613A7.5,7.5,0,0,1,63.613,32.081ZM305.938,216.138l43.334,43.331a16.121,16.121,0,0,1-22.8,22.8l-43.335-43.318a16.186,16.186,0,0,1-4.359-8.086,76.3,76.3,0,1,1,19.079-19.071A16,16,0,0,1,305.938,216.138Zm-30.4-88.16a56.971,56.971,0,1,0,0,80.565A57.044,57.044,0,0,0,275.535,127.978ZM63.613,320.81H448.387a7.5,7.5,0,0,1,0,15.007H63.613A7.5,7.5,0,0,1,63.613,320.81Z"></path>
                    </svg>
                </div>
                <h5><?php echo Label::getLabel('LBL_NO_RESULT_FOUND!'); ?></h5>
            </div>
        </div>
    </div>
<?php } ?>