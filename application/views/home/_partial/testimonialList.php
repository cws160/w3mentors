<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if ($testmonialList) { ?>
    <section class="section bg-grey">
        <div class="container container--xxl">
            <div class="section__header" data-aos="fade-up" data-aos-duration="1000">
                <h2><?php echo Label::getLabel('LBL_WHAT_OUR_CLIENTS_SAY'); ?></h2>
            </div>
            <div class="section__body" data-aos="fade-up" data-aos-duration="1000">
                <div class="testimonial-wrapper">
                    <!-- Thumbnail slider -->
                    <div class="testimonials-thumb js--testimonials-thumb">
                    <?php foreach ($testmonialList as $testmonialDetail) { ?>
                        <div class="testimonial">
                            <div class="testimonial-user">
                                <div class="testimonial-user__pic">
                                    <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_TESTIMONIAL_IMAGE, $testmonialDetail['testimonial_id'], Afile::SIZE_LARGE]), CONF_DEF_CACHE_TIME); ?>" alt="<?php echo $testmonialDetail['testimonial_user_name']; ?>">
                                </div>
                                <div class="testimonial-user__details">
                                    <div class="name">
                                        <?php echo $testmonialDetail['testimonial_user_name']; ?>
                                    </div>
                                    <!-- <span>Founder & CPO</span> -->
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                    </div>
                    <!-- Main slider -->
                    <div class="testimonials-main js--testimonials-main">
                    <?php foreach ($testmonialList as $testmonialDetail) { ?>
                        <div class="testimonial">
                            <div class="testimonial-content">
                                <?php echo CommonHelper::htmlEntitiesDecode(nl2br($testmonialDetail['testimonial_text'] ?? '')); ?>
                            </div>
                        </div>
                    <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php }
