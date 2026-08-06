<div class="menu-group">
    <h6 class="heading-6"><?php echo label::getLabel('LBL_PROFILE'); ?></h6>
    <nav class="menu menu--primary">
        <ul>
            <li class="menu__item <?php echo ($controllerName == "Affiliate" && $action == "index") ? 'is-active' : ''; ?> ">
                <a href="<?php echo MyUtility::makeUrl('Affiliate'); ?>">
                    <svg class="icon icon--dashboard me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#dashboard'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_DASHBOARD'); ?></span>
                </a>
            </li>
            <li class="menu__item <?php echo ($controllerName == "Account") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Account', 'ProfileInfo'); ?>">
                    <svg class="icon icon--settings me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#settings'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_ACCOUNT_SETTINGS'); ?></span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<div class="menu-group">
    <h6 class="heading-6"><?php echo Label::getLabel('LBL_REFERRALS'); ?></h6>
    <nav class="menu menu--primary">
        <ul>
            <li class="menu__item <?php echo ($controllerName == "Refer") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Refer'); ?>">
                    <svg class="icon icon--refer-earn me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#refer-earn'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_REFER_&_EARN'); ?></span>
                </a>
            </li>
        </ul>
    </nav>
</div>
<div class="menu-group">
    <h6 class="heading-6"><?php echo Label::getLabel('LBL_HISTORY'); ?></h6>
    <nav class="menu menu--primary">
        <ul>
            <li class="menu__item <?php echo ($controllerName == "Wallet" && $action == "index") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Wallet'); ?>">
                    <svg class="icon icon--wallet me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#wallet'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_WALLET'); ?></span>
                </a>
            </li>
            <li class="menu__item <?php echo ($controllerName == "Wallet" && $action == "withdrawRequests") ? 'is-active' : ''; ?>">
                <a href="<?php echo MyUtility::makeUrl('Wallet/WithdrawRequests'); ?>">
                    <svg class="icon icon--wallet me-2">
                        <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#withdrawal-request'; ?>"></use>
                    </svg>
                    <span><?php echo Label::getLabel('LBL_WITHDRAWS'); ?></span>
                </a>
            </li>
            <ul>
    </nav>
</div>