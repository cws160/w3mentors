<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>

<?php
if ($reviews) {
    foreach ($reviews as $review) { ?>
        <div class="review-row">
            <div class="review-profile">
                <div class="avtar avtar--md avtar--round" data-title="<?php echo CommonHelper::getFirstChar($review['user_first_name']); ?>">
                    <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $review['ratrev_user_id'], Afile::SIZE_SMALL]); ?>" alt="<?php echo $review['user_first_name']; ?>">
                </div>
                <div class="user-info">
                    <h6><?php echo $review['user_first_name'] . ' ' . $review['user_last_name']; ?></h6>
                    <p><?php echo MyDate::showDate($review['ratrev_created'], true); ?></p>
                </div>
            </div>
            <div class="review-content">
                <div class="review-content__head">
                    <div class="ratings mb-2">
                        <svg class="icon icon--rating">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#rating"></use>
                        </svg>
                        <span class="value"><?php echo FatUtility::convertToType($review['ratrev_overall'], FatUtility::VAR_FLOAT); ?></span>
                    </div>
                    <h6><?php echo $review['ratrev_title']; ?></h6>
                </div>
                <div class="review-content__body">
                    <p><?php echo nl2br($review['ratrev_detail']); ?></p>
                </div>
            </div>
        </div>
    <?php } ?>
    <?php if ($post['pageno'] < $pageCount) { ?>
        <?php $nextPage = $post['pageno'] + 1; ?>
        <div class="reviews-wrapper__foot show-more-container">
            <button class="btn btn--grey btn--block btn--show" onclick="loadReviews(<?php echo $post['course_id']; ?>,<?php echo $nextPage; ?>)"><?php echo Label::getLabel('Lbl_SHOW_MORE'); ?></button>
        </div>
    <?php } ?>
<?php } else {
    echo Label::getLabel('LBL_NO_REVIEWS_POSTED');
} ?>