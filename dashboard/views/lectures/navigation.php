<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<nav class="card-controls-tabs controls-tabs-js">
    <ul>
        <li class="<?php echo ($active == 'description') ? 'is-active' : ''; ?>">
            <a href="javascript:void(0)" <?php if ($lectureId > 0) { ?> onclick="lectureForm('<?php echo $sectionId; ?>', '<?php echo $lectureId; ?>')" <?php } ?>>
                <?php echo Label::getLabel('LBL_DESCRIPTION'); ?>
            </a>
        </li>
        <li class="<?php echo ($active == 'media') ? 'is-active' : ''; ?>">
            <a href="javascript:void(0)" <?php if ($lectureId > 0) { ?> onclick="lectureMediaForm('<?php echo $lectureId; ?>')" <?php } ?>>
                <?php echo Label::getLabel('LBL_MEDIA'); ?>
            </a>
        </li>
        <li class="<?php echo ($active == 'resrc') ? 'is-active' : ''; ?>">
            <a href="javascript:void(0)" <?php if ($lectureId > 0) { ?> onclick="lectureResourceForm('<?php echo $lectureId; ?>')" <?php } ?>>
                <?php echo Label::getLabel('LBL_RESOURCES'); ?>
            </a>
        </li>
    </ul>
</nav>