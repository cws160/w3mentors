<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="menu-group">
    <h6 class="heading-6"><?php echo Label::getLabel('LBL_Discussion_Forum'); ?></h6>
    <nav class="menu menu--primary">
        <ul>
            <li class="menu__item <?php echo ($controllerName == 'Forum') ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Forum'); ?>">
                    <svg class="icon icon--frm-question me-3 icon--small">
                        <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL . 'images/forum/sprite.svg#icon-frm-question'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_My_Questions'); ?></span>
                </a>
            </li>
            <li class="menu__item <?php echo ($controllerName == "ForumTags" && $action == 'subscribed') ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('ForumTags', 'subscribed'); ?>">
                    <svg class="icon icon--subsc_ftag me-3 icon--small">
                        <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL . 'images/forum/sprite.svg#icon-subsc_ftag'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_Subscribed_Tags'); ?></span>
                </a>
            </li>
            <li class="menu__item <?php echo ($controllerName == "ForumTagRequests" && $action == 'index') ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('ForumTagRequests'); ?>">
                    <svg class="icon icon--icon-add_ftag me-3 icon--small">
                        <use xlink:href="<?php echo CONF_WEBROOT_FRONT_URL . 'images/forum/sprite.svg#icon-add_ftag'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_Requested_Tags'); ?></span>
                </a>
            </li>
        </ul>
    </nav>
</div>