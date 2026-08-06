<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
if (count($courses) == 0) {
    $this->includeTemplate('_partial/no-record-found.php');
    return;
}
?>
<div class="course-group">
    <!-- [ COURSE CARD ========= -->
    <?php foreach ($courses as $course) { ?>
        <div class="card-course">
            <div class="card-course__colum card-course__colum--first">
                <div class="card-course__media">
                    <div class="ratio ratio--16by9">
                        <img src="<?php echo MyUtility::makeUrl('Image', 'show', [Afile::TYPE_COURSE_IMAGE, $course['course_id'], 'MEDIUM', $siteLangId], CONF_WEBROOT_FRONT_URL); ?>"
                            alt="">
                    </div>
                </div>
            </div>
            <div class="card-course__colum card-course__colum--second">
                <div class="card-course__head">
                    <small class="card-course__subtitle uppercase color-gray-900">
                        <?php echo $course['cate_name'] ?>
                        <?php echo !empty($course['subcate_name']) ? ' / ' . $course['subcate_name'] : ''; ?>
                    </small>
                    <span class="card-course__title">
                        <?php echo $course['course_title'] ?>
                    </span>
                </div>
                <div class="card-course__body">
                    <div class="course-stats">
                        <span class="course-stats__item">
                            <strong>
                                <?php echo CourseUtility::formatMoney($course['course_price']); ?>
                            </strong>
                        </span>
                        <span class="course-stats__item">
                            <?php echo Label::getLabel('LBL_LECTURES') ?>
                            <strong><?php echo $course['course_lectures'] ?></strong>
                        </span>
                        <?php if ($course['course_type'] > 0) { ?>
                            <span class="course-stats__item">
                                <strong><?php echo $courseTypes[$course['course_type']] ?></strong>
                            </span>
                        <?php } ?>
                        <span class="course-stats__item">
                            <?php echo Label::getLabel('LBL_STUDENTS') ?>
                            <strong><?php echo $course['course_students'] ?></strong>
                        </span>
                        <div class="course-stats__item">
                            <div class="ratings">
                                <svg class="icon icon--rating">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#rating"></use>
                                </svg>
                                <span class="value">
                                    <?php echo $course['course_ratings']; ?>
                                </span>
                                <span class="count">
                                    (<?php echo $course['course_reviews']; ?>)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-course__colum card-course__colum--third">
                <div class="actions-group">
                    <a target="_blank"
                        href="<?php echo MyUtility::makeUrl('Courses', 'view', [$course['course_slug']], CONF_WEBROOT_FRONTEND); ?>"
                        class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                        <svg class="icon icon--enter icon--18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#view-icon"></use>
                        </svg>
                        <div class="tooltip tooltip--top bg-black">
                            <?php echo Label::getLabel('LBL_VIEW'); ?>
                        </div>
                    </a>
                    <a href="javascript:void(0);" onclick="unfavoriteCourse('<?php echo $course['course_id'] ?>');"
                        class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                        <svg class="icon icon--heart icon--18">
                            <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#heart"></use>
                        </svg>
                        <div class="tooltip tooltip--top bg-black">
                            <?php echo Label::getLabel('LBL_UNFAVORITE'); ?>
                        </div>
                    </a>
                    <?php if (!$course['is_purchased']) { ?>
                        <?php if ($course['course_type'] == Course::TYPE_FREE) { ?>
                            <a href="javascript:void(0);" onclick="cart.addFreeCourse('<?php echo $course['course_id'] ?>');"
                                class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--heart icon--18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#enter-now"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black">
                                    <?php echo Label::getLabel('LBL_ENROLL_NOW'); ?>
                                </div>
                            </a>
                        <?php } else { ?>
                            <a href="javascript:void(0);" onclick="cart.addCourse('<?php echo $course['course_id'] ?>');"
                                class="btn btn--bordered btn--shadow btn--equal m-1 is-hover">
                                <svg class="icon icon--heart icon--18">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL; ?>images/sprite.svg#enter-now"></use>
                                </svg>
                                <div class="tooltip tooltip--top bg-black">
                                    <?php echo Label::getLabel('LBL_ENROLL_NOW'); ?>
                                </div>
                            </a>
                        <?php } ?>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
    <!-- ] ========= -->
</div>
<?php
$pagingArr = [
    'page' => $post['pageno'],
    'pageCount' => ceil($recordCount / $post['pagesize']),
    'recordCount' => $recordCount,
    'pageSize' => $post['pagesize'],
    'callBackJsFunc' => 'goToSearchPage',
];

$checkoutForm->setFormTagAttribute('class', 'd-none');
$checkoutForm->setFormTagAttribute('name', 'frmCheckout');
$checkoutForm->setFormTagAttribute('id', 'frmCheckout');
echo $checkoutForm->getFormHtml();

$this->includeTemplate('_partial/pagination.php', $pagingArr, false);
echo FatUtility::createHiddenFormFromData($post, ['name' => 'frmPaging']);
