<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="col-xl-10 col-lg-10">
    <div class="reviews-section">
        <div class="reviews-section__head">
            <div class="reviews-stats">
                <div class="row justify-content-between">
                    <div class="col-4 col-sm-2">
                        <div class="reviews-total">
                            <div class="reviews-media">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 110 110">
                                    <g transform="translate(-28.999 -29)">
                                        <path d="M892.348,2341l17.582,31.851,35.759,6.861L920.8,2406.26l4.518,36.091-32.967-15.445-32.968,15.445,4.518-36.091-24.892-26.546,35.759-6.861L892.348,2341" transform="translate(-808.008 -2308.001)" />
                                    </g>
                                </svg>
                                <span class="reviews-count"><?php echo $course['course_ratings'] ?> </span>
                            </div>
                            <div class="reviews-value">
                                <?php echo $course['course_reviews'] ?>
                                <?php echo Label::getLabel('LBL_REVIEWS'); ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-8 col-sm-6">
                        <div class="reviews-counter">
                            <?php foreach ($reviews as $review) { ?>
                                <div class="reviews-counter__item">
                                    <div class="reviews-progress">
                                        <div class="reviews-progress__value"><?php echo $review['rating']; ?></div>
                                        <div class="reviews-progress__content">
                                            <div class="progress progress--small progress--round">
                                                <?php if ($review['percent'] > 0) { ?>
                                                    <div class="progress__bar bg-yellow" role="progressbar" style="width:<?php echo $review['percent'] ?>%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                        <div class="reviews-progress__value">
                                            <?php
                                            if ($review['count'] > 0) {
                                                echo '(' . $review['count'] . ')';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </div>
                    <div class="col-sm-3 col-md-4 col-xl-3">

                    </div>
                </div>
            </div>
        </div>
        <div class="reviews-section__body">
            <?php
            $fld = $frm->getField('sorting');
            $fld->setFieldTagAttribute('onchange', 'searchReviews()');
            ?>
            <div class="reviews-sorting reviewsListJs">
                <div class="row justify-content-between align-items-center">
                    <div class="col-sm-auto">
                        <p class="m-0 pagingLblJs">
                            <?php
                            $label = Label::getLabel('LBL_DISPLAYING_REVIEWS_{start-count}_TO_{end-count}_OF_{total}');
                            $label = str_replace(
                                ['{start-count}', '{end-count}', '{total}'],
                                [0, 0, 0],
                                $label
                            );
                            echo $label;
                            ?>
                        </p>
                    </div>
                    <div class="col-sm-auto">
                        <div class="reviews-sort">
                            <?php
                            echo $frm->getFormTag();
                            echo $fld->getHtml();
                            echo $frm->getFieldHtml('pageno');
                            echo $frm->getFieldHtml('course_id');
                            ?>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>