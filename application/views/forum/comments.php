<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($quesComments)) {
    foreach ($quesComments as $comment) {
?>
        <article class="article-panel <?php echo (1 == $comment['fquecom_accepted'] ? 'is-completed' : ''); ?>">
            <div class="article-card">
                <div class="article-card__left">
                    <!-- Best Answer starts-->
                    <?php
                    $bestAnswerLbl = Label::getLabel('LBL_Set_as_best_answer');
                    $bestAnsTikCls = '';
                    if (1 == $comment['fquecom_accepted']) {
                        $bestAnsTikCls = 'is-active';
                        $bestAnswerLbl = Label::getLabel('LBL_Unset_as_best_answer');
                        if ($queUserId != $userId) {
                            $bestAnswerLbl = Label::getLabel('LBL_Question_has_best_answer');
                        }
                    }
                    if (1 == $comment['fquecom_accepted'] || $queUserId == $userId) {
                        $action = 'markAction(this, ' . $comment['fquecom_id'] . ', ' . $comment['fque_id'] . ');';
                        $toolTip = '<div class="tooltip tooltip--top bg-black">' . $bestAnswerLbl . '</div>';
                        $markActionClass = 'article-mark';
                        if ($queUserId != $userId) {
                            $action = '';
                            $markActionClass = 'article-check mt-0';
                        }
                    ?>
                        <a href="javascript:void(0)" onClick="<?php echo $action; ?>" class="order-2 <?php echo $markActionClass; ?> is-hover <?php echo $bestAnsTikCls; ?>">
                            <div class="tooltip tooltip--top bg-black"><?php echo $bestAnswerLbl; ?></div>
                        </a>
                    <?php } ?>
                    <!-- Best Answer ends-->
                    <?php
                    $totalReactionCount = abs($comment['fstat_likes'] - $comment['fstat_dislikes']);
                    $reactClass = '';
                    if (0 < $totalReactionCount && $comment['fstat_likes'] > $comment['fstat_dislikes']) {
                        $reactClass = "color-success";
                    } elseif ($comment['fstat_likes'] < $comment['fstat_dislikes']) {
                        $reactClass = "color-danger";
                    }
                    $upVoted = 0;
                    $downVoted = 0;
                    if (array_key_exists($comment['fquecom_id'], $loggedUserReactions) && ForumReaction::REACTION_LIKE == $loggedUserReactions[$comment['fquecom_id']]['freact_reaction']) {
                        $upVoted = 1;
                    } elseif (array_key_exists($comment['fquecom_id'], $loggedUserReactions) && ForumReaction::REACTION_DISLIKE == $loggedUserReactions[$comment['fquecom_id']]['freact_reaction']) {
                        $downVoted = 1;
                    }
                    if (0 < $comment['fstat_likes'] || 0 < $comment['fstat_dislikes'] || 0 < $totalReactionCount) {
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
                    $votesLbl = '<span id="totupcounts' . ForumReaction::REACT_TYPE_COMMENT . '_' . $comment['fquecom_id'] . '">' . $comment['fstat_likes'] . '</span>' . ' ' . Label::getLabel('LBL_Upvotes') . "<br>" . '<span id="totdowncounts' . ForumReaction::REACT_TYPE_COMMENT . '_' . $comment['fquecom_id'] . '">' . $comment['fstat_dislikes'] . '</span>' . ' ' . Label::getLabel('LBL_Downvotes');
                    ?>
                    <div class="counts order-1">
                        <span class="counts__up">
                            <a href="javascript:void(0);" data-upvoted="<?php echo $upVoted; ?>" data-record_id="<?php echo $comment['fquecom_id']; ?>" data-count="<?php echo $comment['fstat_likes']; ?>" onClick="upVote(this, <?php echo $comment['fquecom_id']; ?>,<?php echo ForumReaction::REACT_TYPE_COMMENT; ?>)" id="up<?php echo ForumReaction::REACT_TYPE_COMMENT . '_' . $comment['fquecom_id']; ?>" class="vote vote--up is-hover <?php echo (1 == $upVoted ? 'color-success' : ''); ?>">
                                <svg class="icon icon--upvote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M2 9h3v12H2a1 1 0 0 1-1-1V10a1 1 0 0 1 1-1zm5.293-1.293l6.4-6.4a.5.5 0 0 1 .654-.047l.853.64a1.5 1.5 0 0 1 .553 1.57L14.6 8H21a2 2 0 0 1 2 2v2.104a2 2 0 0 1-.15.762l-3.095 7.515a1 1 0 0 1-.925.619H8a1 1 0 0 1-1-1V8.414a1 1 0 0 1 .293-.707z"></path>
                                </svg>
                                <div class="tooltip tooltip--top bg-black"><?php echo Label::getLabel('LBL_Vote_this_question_up'); ?></div>
                            </a>
                        </span>
                        <span class="counts__middle is-hover">
                            <span id="tot_counts<?php echo ForumReaction::REACT_TYPE_COMMENT . '_' . $comment['fquecom_id']; ?>" data-count="<?php echo $totalReactionCount; ?>" class="vote-counts <?php echo $reactClass; ?>"><?php echo $totalReactionCount; ?></span>
                            <div style="<?php echo $emptyToolTipStyle; ?>" id="nonempty_count<?php echo ForumReaction::REACT_TYPE_COMMENT . '_' . $comment['fquecom_id']; ?>" class="tooltip tooltip--right bg-black <?php echo $emptyToolTip; ?>"><?php echo $votesLbl; ?></div>
                            <div style="<?php echo $nonEmptyToolTipStyle; ?>" id="empty_count<?php echo ForumReaction::REACT_TYPE_COMMENT . '_' . $comment['fquecom_id']; ?>" class="tooltip tooltip--right bg-black <?php echo $nonEmptyToolTip; ?>"><?php echo Label::getLabel('LBL_Awaiting_Best_Answer'); ?></div>
                        </span>
                        <span class="counts__down">
                            <a href="javascript:void(0);" data-downVoted="<?php echo $downVoted; ?>" data-record_id="<?php echo $comment['fquecom_id']; ?>" data-count="<?php echo $comment['fstat_dislikes']; ?>" onClick="downVote(this, <?php echo $comment['fquecom_id']; ?>,<?php echo ForumReaction::REACT_TYPE_COMMENT; ?>)" id="down<?php echo ForumReaction::REACT_TYPE_COMMENT . '_' . $comment['fquecom_id']; ?>" class="vote vote--down is-hover <?php echo (1 == $downVoted ? 'color-danger' : ''); ?>">
                                <svg class="icon icon--downvote" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M22 15h-3V3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1zm-5.293 1.293l-6.4 6.4a.5.5 0 0 1-.654.047L8.8 22.1a1.5 1.5 0 0 1-.553-1.57L9.4 16H3a2 2 0 0 1-2-2v-2.104a2 2 0 0 1 .15-.762L4.246 3.62A1 1 0 0 1 5.17 3H16a1 1 0 0 1 1 1v11.586a1 1 0 0 1-.293.707z"></path>
                                </svg>
                                <div class="tooltip tooltip--bottom bg-black"><?php echo Label::getLabel('LBL_Vote_this_question_down'); ?></div>
                            </a>
                        </span>
                    </div>
                </div>
                <div class="article-card__right">
                    <div class="article-author">
                        <figure class="article-author__avatar">
                            <div class="avtar avtar--small avtar--round bg-gray-500" data-title="J"><img src="<?php echo MyUtility::makeUrl('Image', 'show', array(Afile::TYPE_USER_PROFILE_IMAGE, $comment['fquecom_user_id'], Afile::SIZE_SMALL)); ?>" alt="<?php echo $comment['user_first_name'] . " " . $comment['user_last_name']; ?>"></div>
                        </figure>
                        <div class="article-author__content">
                            <span><?php echo Label::getLabel('LBL_Posted_By:'); ?>&nbsp;<strong><?php echo $comment['user_first_name'] . ' ' . $comment['user_last_name']; ?></strong>,</span>&nbsp;<date class="color-gray-1000"><?php echo MyDate::getDateTimeDifference($comment['fquecom_added_on'], date('Y-m-d H:i:s'), true); ?></date>
                        </div>
                    </div>
                    <div class="editor-content">
                        <?php echo nl2br($comment['fquecom_comment']); ?>
                    </div>
                </div>
            </div>
        </article>
        <!-- ] -->
    <?php
    }
    $nextPage = $page + 1;
    if ($nextPage <= $pageCount) {
    ?>
        <div>
            <a id="loadMoreBtn" href="javascript:void(0);" onClick="comments(<?php echo $quesId ?>, <?php echo $nextPage; ?>);" class="loadmore btn btn--more"><?php echo Label::getLabel('LBL_Show_More'); ?></a>
        </div>
    <?php
    }
} else {
    ?>
    <div class="message-display">
        <div class="message-display__media">
            <img src="<?php echo CONF_WEBROOT_URL; ?>images/forum/no-comments.svg" alt="" style="max-width:300px;">
        </div>
        <h4 class="mb-2"><?php echo Label::getLabel('MSG_There_have_been_no_answers_to_this_question_yet'); ?></h4>
        <?php if (true == $commentsAllowed) { ?>
            <p><?php echo Label::getLabel('MSG_Become_a_first_user_to_post_comment'); ?></p>
        <?php } ?>
    </div>
<?php } ?>