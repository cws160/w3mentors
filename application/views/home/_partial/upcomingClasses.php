<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (count($classes) > 0) { ?>
<section class="section section--cardslider">
    <div class="container container--xxl">
        <div class="section__header mb-xl-3" data-aos="fade-up" data-aos-duration="1000">
            <h2><?php echo Label::getLabel('LBL_UPCOMING_GROUP_CLASSES'); ?></h2>
        </div>
        <div class="section__body" data-aos="fade-up" data-aos-duration="1000">
            <div class="slider slider-oneforth slider-oneforth-js">
                <?php foreach ($classes as $class) { ?>
                <div class="slider__item">
                    <div class="card-cover">
                        <div class="card-class">
                            <div class="card-class__head">
                                <div class="card-class__media ratio ratio--16by9">
                                    <a href="<?php echo MyUtility::makeUrl('GroupClasses', 'view', [$class['grpcls_slug']]); ?>">
                                        <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_GROUP_CLASS_BANNER, $class['grpcls_id'], Afile::SIZE_MEDIUM]), CONF_IMG_CACHE_TIME); ?>" alt="<?php echo $class['grpcls_title']; ?>">
                                    </a>
                                </div>
                                <div class="card-class__action">
                                    <?php if ($class['grpcls_already_booked']) { ?>
                                    <button tabindex="0" class="btn btn--white btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <p><?php echo Label::getLabel('LBL_ALREADY_BOOKED') ?></p>
                                    <?php } elseif ($class['grpcls_booked_seats'] >= $class['grpcls_total_seats']) { ?>
                                    <button tabindex="0" class="btn btn--white btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <p><?php echo Label::getLabel('LBL_CLASS_ALREADY_FULL') ?></p>
                                    <?php } elseif ($class['grpcls_start_datetime'] < date('Y-m-d H:i:s', strtotime('+' . $bookingBefore . ' minutes', $class['grpcls_currenttime_unix']))) { ?>
                                    <button class="btn btn--white btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <p><?php echo Label::getLabel('LBL_BOOKING_CLOSED') ?></p>
                                    <?php } elseif ($siteUserId == $class['grpcls_teacher_id']) { ?>
                                    <button class="btn btn--white btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <p><?php echo Label::getLabel('LBL_YOU_CANNOT_BOOK_YOUR_OWN_CLASS') ?></p>
                                    <?php } elseif ($class['grpcls_booked_seats'] + $class['grpcls_unpaid_seats'] >= $class['grpcls_total_seats']) { ?>
                                    <button class="btn btn--white btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></bu>
                                        <p><?php echo Label::getLabel('LBL_CLASS_HOLD_INFO') ?></p>
                                        <?php } elseif ($class['grpcls_type'] == GroupClass::TYPE_PACKAGE) { ?>
                                        <button onclick="cart.addPackage(<?php echo $class['grpcls_id']; ?>)" class="btn btn--white"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></bu>
                                            <?php } else { ?>
                                            <button onclick="cart.addClass(<?php echo $class['grpcls_id']; ?>)" class="btn btn--white"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                            <?php } ?>
                                </div>
                                <?php
                                        if ($class['grpcls_type'] == GroupClass::TYPE_REGULAR && !empty($class['class_offer'])) {
                                            echo '<div class="badge--off">' . number_format(round($class['class_offer'], 2), 2, '.', '') . '% ' . Label::getLabel('LBL_OFF') . '</div>';
                                        } elseif ($class['grpcls_type'] == GroupClass::TYPE_PACKAGE && !empty($class['package_offer'])) {
                                            echo '<div class="badge--off">' . number_format(round($class['package_offer'], 2), 2, '.', '') . '% ' . Label::getLabel('LBL_OFF') . '</div>';
                                        }
                                        ?>
                            </div>
                            <div class="card-class__body">
                                <div class="card-flex-group">
                                    <div class="card-flex-group__item">
                                        <span class="card-class__date">
                                            <?php echo MyDate::showDate($class['grpcls_start_datetime']); ?>
                                        </span>
                                    </div>
                                    <?php if ($class['grpcls_offline'] == AppConstant::YES) { ?>
                                    <span class="card-badge badge badge--curve color-primary"> <?php echo Label::getLabel('LBL_OFFLINE'); ?> </span>
                                    <?php } ?>
                                </div>
                                <span class="card-class__subtitle">
                                    <?php
                                            $html = [];
                                            if ($class['grpcls_tlang_name']) {
                                                foreach ($class['grpcls_tlang_name'] as $clsname) {
                                                    $html[] = '<a href="' . MyUtility::makeUrl('GroupClasses', 'index', [$clsname['slug']]) . '">' . $clsname['name'] . '</a>';
                                                }
                                            }
                                            echo implode(' / ', $html);
                                            ?>
                                </span>
                                <div class="card-class__title">
                                    <a href="<?php echo MyUtility::makeUrl('GroupClasses', 'view', [$class['grpcls_slug']]); ?>">
                                        <?php echo $class['grpcls_title']; ?>
                                    </a>
                                </div>
                                <div class="card-element">
                                    <div class="card-element__item">
                                        <span>
                                            <?php echo MyDate::showTime($class['grpcls_start_datetime']); ?>
                                            <?php
                                                    $str = Label::getLabel('LBL_{minutes}_Minutes');
                                                    $str = str_replace('{minutes}', $class['grpcls_duration'], $str);
                                                    echo ($class['grpcls_type'] == GroupClass::TYPE_REGULAR) ?  ' (' . $str . ')' : ' ' . Label::getLabel('LBL_ONWARDS');
                                                    ?>
                                        </span>
                                    </div>
                                    <?php if ($class['grpcls_type'] == GroupClass::TYPE_PACKAGE && $class['grpcls_sub_classes'] > 0) { ?>
                                    <div class="card-element__item">
                                        <span class="card-class__tag"><?php echo $class['grpcls_sub_classes']; ?> <?php echo Label::getLabel('LBL_CLASSES'); ?>
                                        </span>
                                    </div>
                                    <?php } ?>
                                    <div class="card-element__item">
                                        <span>
                                            <?php echo $class['grpcls_total_seats']; ?> <?php echo Label::getLabel('LBL_SEATS'); ?>
                                        </span>
                                    </div>
                                </div>
                                <h4 class="bold-700 price-value">
                                    <?php echo MyUtility::formatMoney($class['grpcls_entry_fee']); ?>
                                </h4>
                            </div>
                            <div class="card-class__footer">
                                <a href="<?php echo MyUtility::makeUrl('Teachers', 'view', [$class['user_username']]) ?>" class="profile-meta d-flex align-items-center">
                                    <div class="profile-meta__media">
                                        <span class="avtar avtar--medium avtar--round" data-title="<?php echo CommonHelper::getFirstChar($class['user_full_name'], true) ?>">
                                            <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $class['grpcls_teacher_id'], Afile::SIZE_SMALL]), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $class['user_full_name'] ?>">
                                        </span>
                                    </div>
                                    <div class="profile-meta__details">
                                        <h6 class="profile-meta__title"> <?php echo $class['user_full_name'] ?> </h6>
                                        <div class="rating">
                                            <svg class="rating__media">
                                                <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#rating'; ?>"></use>
                                            </svg>
                                            <span class="rating__value"><?php echo $class['testat_ratings']; ?></span>
                                            <span class="rating__count"><?php echo '(' . $class['testat_reviewes'] . ')'; ?></span>
                                        </div>
                                    </div>
                                </a>
                                <div class="card-class__btns d-flex d-xl-none gap-2 mt-3 flex-wrap">
                                    <a href="<?php echo MyUtility::makeUrl('GroupClasses', 'view', [$class['grpcls_slug']]); ?>" class="btn btn--primary-bordered d-block d-xl-none" tabindex="0"><?php echo Label::getLabel('LBL_VIEW_DETAILS'); ?></a>
                                    <?php if ($class['grpcls_already_booked']) { ?>
                                    <button title="<?php echo Label::getLabel('LBL_ALREADY_BOOKED') ?>" tabindex="0" class="btn btn--primary btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($class['grpcls_booked_seats'] >= $class['grpcls_total_seats']) { ?>
                                    <button title="<?php echo Label::getLabel('LBL_CLASS_FULL'); ?>" tabindex="0" class="btn btn--primary btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($class['grpcls_start_datetime'] < date('Y-m-d H:i:s', strtotime('+' . $bookingBefore . ' minutes', $class['grpcls_currenttime_unix']))) { ?>
                                    <button title="<?php echo Label::getLabel('LBL_BOOKING_CLOSED') ?>" class="btn btn--primary btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($siteUserId == $class['grpcls_teacher_id']) { ?>
                                    <button title="<?php echo Label::getLabel('LBL_CANNOT_BOOK_OWN_CLASS'); ?>" class="btn btn--primary btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($class['grpcls_booked_seats'] + $class['grpcls_unpaid_seats'] >= $class['grpcls_total_seats']) { ?>
                                    <button title="<?php echo Label::getLabel('LBL_CLASS_HOLD_INFO'); ?>" class="btn btn--primary btn--disabled"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } elseif ($class['grpcls_type'] == GroupClass::TYPE_PACKAGE) { ?>
                                    <button onclick="cart.addPackage(<?php echo $class['grpcls_id']; ?>)" class="btn btn--primary"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } else { ?>
                                    <button onclick="cart.addClass(<?php echo $class['grpcls_id']; ?>)" class="btn btn--primary"><?php echo Label::getLabel("LBL_BOOK_NOW") ?></button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        </div>
        <div class="section__footer text-center" data-aos="fade-up" data-aos-duration="1000">
            <a href="<?php echo MyUtility::makeUrl('GroupClasses'); ?>" class="text-button">
                <?php echo Label::getLabel('LBL_VIEW_ALL'); ?>
                <span class="circle-arrow"></span>
            </a>
        </div>
    </div>
</section>
<?php }