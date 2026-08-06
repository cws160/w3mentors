<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<div class="page-block__left">
    <nav class="steps steps--vertical">
        <ul>
            <li class="steps__item <?php echo ($step == 1) ? 'is-process' : ''; ?>" data-blocks-show="1">
                <a href="javascript:void(0);" <?php echo ($step >= 1 && $step != 5) ? 'onclick="getform(1);"' : ''; ?>>
                    <span class="step__icon"></span>
                    <?php echo Label::getLabel('LBL_PERSONAL_INFO'); ?>
                </a>
            </li>
            <li class="steps__item <?php echo ($step == 2) ? 'is-process' : ''; ?>" data-blocks-show="2">
                <a href="javascript:void(0);" <?php echo ($step >= 2 && $step != 5) ? 'onclick="getform(2);"' : ''; ?>>
                    <span class="step__icon"></span>
                    <?php echo Label::getLabel('LBL_PROFILE_MEDIA'); ?>
                </a>
            </li>
            <li class="steps__item <?php echo ($step == 3) ? 'is-process' : ''; ?>" data-blocks-show="3">
                <a href="javascript:void(0);" <?php echo ($step >= 3 && $step != 5) ? 'onclick="getform(3);"' : ''; ?>>
                    <span class="step__icon"></span>
                    <?php echo Label::getLabel('LBL_TEACHER_LANGUAGES'); ?>
                </a>
            </li>
            <li class="steps__item <?php echo ($step == 4) ? 'is-process' : ''; ?>" data-blocks-show="4">
                <a href="javascript:void(0);" <?php echo ($step >= 4 && $step != 5) ? 'onclick="getform(4);"' : ''; ?>>
                    <span class="step__icon"></span>
                    <?php echo Label::getLabel('LBL_RESUME'); ?>
                </a>
            </li>
            <li class="steps__item <?php echo ($step == 5) ? 'is-process' : ''; ?>" data-blocks-show="5">
                <a href="javascript:void(0);">
                    <span class="step__icon"></span>
                    <?php echo Label::getLabel('LBL_CONFIRMATION'); ?>
                </a>
            </li>
        </ul>
    </nav>
</div>