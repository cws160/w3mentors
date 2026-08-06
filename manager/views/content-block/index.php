<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php
reset($blockTypes);
$key = key($blockTypes);
?>
<main class="main">
    <div class="container">
        <div class="breadcrumb-wrap">
            <?php $this->includeTemplate('_partial/header/header-breadcrumb.php'); ?>
        </div>
        <div class="grid-layout">
            <div class="grid-layout-left">
                <div class="card card-sticky">
                    <div class="tab tab-vertical tabs-nav-js">
                        <ul class="tabs-nav-js blocksTabJs"> 
                            <?php $count = 1; ?>
                            <?php if (!empty($blockTypes)) { ?>
                                <?php foreach ($blockTypes as $type => $label) { ?>

                                    <li>
                                        <a class="<?php echo ($key == $type) ? 'active' : ''; ?>" data-type="<?php echo $type; ?>" href="javascript:void(0);" onclick="searchBlocks(<?php echo $type; ?>)">
                                            <?php echo $label; ?>
                                        </a>
                                    </li>
                                <?php } ?>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="grid-layout-right">
                <div class="card">            
                    <div class="card-table">
                        <div id="frmBlock">
                            <div class="table-responsive"> </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
       
    </div>
</main>
<script type="text/javascript">
    var type = <?php echo $key; ?>;
</script>