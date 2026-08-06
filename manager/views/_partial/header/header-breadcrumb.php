<?php defined('SYSTEM_INIT') or die('Invalid usage'); ?>
<ul class="breadcrumb">
    <li class="breadcrumb-item"><a href="<?php echo MyUtility::makeUrl('') ?>"><?php echo Label::getLabel('LBL_Home'); ?></a></li>
    <?php
    if (!empty($this->variables['nodes'])) {
        foreach ($this->variables['nodes'] as $nodes) {
    ?>
            <?php if (!empty($nodes['href'])) { ?>
                <li class="breadcrumb-item">
                    <a href="<?php echo $nodes['href']; ?>" <?php echo (!empty($nodes['other'])) ? $nodes['other'] : ''; ?>>
                        <?php
                        echo $nodes['title'];
                        ?>
                    </a>
                </li>
            <?php } else { ?>
                <li class="breadcrumb-item">
                    <?php echo $nodes['title']; ?>
                </li>
    <?php
            }
        }
    }
    ?>
</ul>