<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="grid-layout">
            <div class="grid-layout-left">
                <div class="card card-sticky">
                    <nav class="tab tab-vertical tabs-nav-js">
                        <ul>
                            <?php
                            $count = 1;
                            foreach ($tabs as $formType => $tabName) {
                                $tabsId = 'tabs_' . $count;
                            ?>
                                <?php if ($formType == Configurations::FORM_MEDIA_AND_LOGOS) { ?>
                                    <li><a class="<?php echo ($activeTab == $formType) ? 'active' : '' ?>" rel=<?php echo $tabsId; ?> href="javascript:void(0)" onClick="getLangForm(<?php echo $formType; ?>, <?php echo $siteLangId; ?>, '<?php echo $tabsId; ?>')"><?php echo $tabName; ?></a></li>
                                <?php } else { ?>
                                    <li><a class="<?php echo ($activeTab == $formType) ? 'active' : '' ?>" rel=<?php echo $tabsId; ?> href="javascript:void(0)" onClick="getForm(<?php echo $formType; ?>, '<?php echo $tabsId; ?>')"><?php echo $tabName; ?></a></li>
                            <?php
                                }
                                $count++;
                            }
                            ?>
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="grid-layout-right">
                <div class="card">
                    <div id="frmBlock"></div>
                </div>
            </div>

        </div>
    </div>
</main>
<script>
    var activeTab = <?php echo $activeTab; ?>;
    var YES = <?php echo AppConstant::YES; ?>;
    var NO = <?php echo AppConstant::NO; ?>;
    var pwaFormType = <?php echo Configurations::FORM_PWA_SETTINGS; ?>;
</script>