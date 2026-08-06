<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
            <div class="action-toolbar">
                <a href="javascript:void(0);" onclick="exportCSV();" class="btn btn-primary"><?php echo Label::getLabel('LBL_EXPORT'); ?></a>
            </div>
        </div>
        <div class="grid-layout">
            <div class="grid-layout-left">
                <div class="card card-sticky">
                    <nav class="tab tab-vertical tabs-nav-js">
                        <ul>
                            <?php
                            $itr = 0;
                            foreach ($tabsArr as $metaType => $metaDetail) {
                            ?>
                                <li><a class="<?php echo ($activeTab == $metaType) ? 'active' : '' ?>" href="javascript:void(0)" onClick="listMetaTags(<?php echo "'$metaType'"; ?>)"><?php echo $metaDetail['name']; ?></a></li>
                            <?php
                                $itr++;
                            }
                            ?>
                        </ul>
                    </nav>
                </div>
            </div>
            <div class="grid-layout-right">
                <div id="frmBlock"></div>
            </div>
        </div>
    </div>
</main>
<script>
    $('.js--filter-trigger').click(function() {
        $(this).toggleClass("active");
        $('.js--filter-target').slideToggle();
    });
</script>