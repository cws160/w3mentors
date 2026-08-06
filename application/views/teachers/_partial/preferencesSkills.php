<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$preferenceTypeArr = Preference::getPreferenceTypeArr(MyUtility::getSiteLangId());
?>
<div class="box box--toggle">
    <div class="box__head box__head-trigger box__head-trigger-js">
        <h4><?php echo Label::getLabel('LBL_Teaching_Expertise'); ?></h4>
    </div>
    <div class="box__body box__body-target box__body-target-js -padding-30">
        <div class="content-repeated-container">
            <?php
            if ($preferences) {
                foreach ($preferences as $preference) {
                    if (empty($preferenceTypeArr[$preference['prefer_type']])) {
                        continue;
                    }
                    $preferenceTitlesArr = explode(",", $preference['preference_titles']);
            ?>
                    <div class="content-repeated">
                        <div class="row g-2">
                            <div class="col-xl-4 col-lg-4 col-sm-4">
                                <strong><?php echo $preferenceTypeArr[$preference['prefer_type']]; ?></strong>
                            </div>
                            <div class="col-xl-8 col-lg-8 col-sm-8">
                                <div class="tick-listing tick-listing--onehalf">
                                    <ul>
                                        <?php foreach ($preferenceTitlesArr as $preferenceTitle) { ?>
                                            <li><?php echo $preferenceTitle; ?></li>
                                        <?php } ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
            <?php
                }
            }
            ?>
        </div>
    </div>
</div>