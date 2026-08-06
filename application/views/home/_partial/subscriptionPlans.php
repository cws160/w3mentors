<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (isset($subscriptions) && !empty($subscriptions)) { ?>
    <section class="section bg-grey" style="background-color: #F5F6F9;">
        <div class="container container--xxl">
            <div class="section__header" data-aos="fade-up" data-aos-duration="1000">
                <h2><?php echo Label::getLabel('LBL_SCALABLE_SUBSCRIPTION_PLANS_TO_SUPPORT_YOUR_GROWTH') ?></h2>
                <p class="p-large"><?php echo Label::getLabel('LBL_POWERFUL_FEATURES_AND_GREAT_SUPPORT'); ?></p>
            </div>
            <div class="section__body" data-aos="fade-up" data-aos-duration="1000">
                <div class="packages">
                    <?php foreach ($subscriptions as $subscription) { ?>
                        <div class="package-card <?php if (!empty($activePlan) && $subscription['subplan_id'] == $activePlan['ordsplan_plan_id']) {
                                                        echo 'package-card--highlight';
                                                    } ?>">
                            <div class="package-card__head">
                                <div class="package-title"><?php echo $subscription['plan_name'] ?></div>
                                <div class="price">
                                    <?php $currSymbol = MyUtility::getCurrencySymbol(); ?>
                                    <sup class="price__currency"><?php echo $currSymbol; ?></sup>
                                    <span class="price__value"><?php echo str_replace($currSymbol, '', MyUtility::formatMoney($subscription['subplan_price'])); ?></span>
                                </div>
                            </div>
                            <div class="package-card__body">
                                <ul class="list list--tick">
                                    <li>
                                        <?php echo $subscription['subplan_lesson_count'] . ' ' . Label::getLabel('LBL_LESSON(S)_AVAILABLE'); ?>
                                    </li>
                                    <li>
                                        <?php echo  $subscription['subplan_lesson_duration'] . ' ' . Label::getLabel('LBL_Minutes_Duration'); ?>
                                    </li>
                                    <li>
                                        <?php echo $subscription['subplan_validity'] . ' ' . Label::getLabel('LBL_Week(s)') . ' ' . Label::getLabel('LBL_Renew_Cycle'); ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="package-card__footer">
                                <div class="package-card__note">

                                </div>
                                <?php if (!empty($activePlan) && $subscription['subplan_id'] == $activePlan['ordsplan_plan_id']) { ?>
                                    <button class="btn btn--primary-bordered btn--block btn--lg">
                                        <?php echo Label::getLabel('LBL_ACTIVE'); ?>
                                    </button>
                                <?php } else { ?>
                                    <button onclick="cart.addSubscriptionPlan('<?php echo $subscription['subplan_id'] ?>');" class="btn btn--primary-bordered btn--block btn--lg">
                                        <?php echo Label::getLabel('LBL_Buy_Now'); ?>
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="section__footer text-center aos-init aos-animate" data-aos="fade-up" data-aos-duration="1000">
                <a href="<?php echo MyUtility::makeUrl('subscriptionPlans'); ?>" class="text-button">
                    <?php echo Label::getLabel('LBL_View_All'); ?>
                    <span class="circle-arrow"></span>
                </a>
            </div>
        </div>
    </section>
<?php } ?>