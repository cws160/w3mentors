<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<ul>
    <?php if (count($courses) > 0 || count($languages) > 0 || count($classes) > 0 || count($teachers) > 0) { ?>
        <?php if (count($courses) > 0) { ?>
            <?php foreach ($courses as $course) { ?>
                <li class="is-suggestion-course">
                    <a href="<?php echo MyUtility::makeUrl('Courses', 'view', [$course['slug']]); ?>">
                        <span class="auto-suggest__item">
                            <span class="auto-suggest__media">
                                <svg class="icon icon--course">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-course-filter'; ?>"></use>
                                </svg>
                            </span>
                            <span class="auto-suggest__content">
                                <?php echo str_ireplace($keyword, '<b>' . $keyword . '</b>', $course['name']); ?>
                            </span>
                        </span>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>
        <?php if (count($languages) > 0) { ?>
            <?php foreach ($languages as $language) { ?>
                <li class="is-suggestion-subject">
                    <a href="<?php echo MyUtility::makeUrl('Teachers', 'languages', [$language['slug']]); ?>">
                        <span class="auto-suggest__item">
                            <span class="auto-suggest__media">
                                <svg class="icon icon--subject">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-subject-filter'; ?>"></use>
                                </svg>
                            </span>
                            <span class="auto-suggest__content">
                                <?php echo str_ireplace($keyword, '<b>' . $keyword . '</b>', ucfirst($language['name'])); ?>
                            </span>
                        </span>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>
        <?php if (count($classes) > 0) { ?>
            <?php foreach ($classes as $class) { ?>
                <li class="is-suggestion-groupclass">
                    <a href="<?php echo MyUtility::makeUrl('GroupClasses', 'view', [$class['slug']]); ?>">
                        <span class="auto-suggest__item">
                            <span class="auto-suggest__media">
                                <svg class="icon icon--subject">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-class-filter'; ?>"></use>
                                </svg>
                            </span>
                            <span class="auto-suggest__content">
                                <?php echo str_ireplace($keyword, '<b>' . $keyword . '</b>', $class['name']); ?>
                            </span>
                        </span>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>
        <?php if (count($teachers) > 0) { ?>
            <?php foreach ($teachers as $teacher) { ?>
                <li class="is-suggestion-teacher">
                    <a href="<?php echo MyUtility::makeUrl('Teachers', 'view', [$teacher['slug']]); ?>">
                        <span class="auto-suggest__item">
                            <span class="auto-suggest__media">
                                <svg class="icon icon--subject">
                                    <use xlink:href="<?php echo CONF_WEBROOT_URL . 'images/sprite.svg#icon-teacher-filter'; ?>"></use>
                                </svg>
                            </span>
                            <span class="auto-suggest__content">
                                <?php echo str_ireplace($keyword, '<b>' . $keyword . '</b>', $teacher['name']); ?>
                            </span>
                        </span>
                    </a>
                </li>
            <?php } ?>
        <?php } ?>
    <?php } else { ?>
        <li class="is-suggestion-course">
            <a>
                <span class="auto-suggest__item">
                    <span class="auto-suggest__media">
                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="20" height="20" viewBox="0 0 32 32" fill="currentColor">
                            <g>
                                <path d="m22.456 23.966 6.837 6.837a1 1 0 0 0 1.414-1.414l-6.836-6.837A12.945 12.945 0 0 0 27 14.096c0-7.175-5.825-13-13-13s-13 5.825-13 13 5.825 13 13 13a12.94 12.94 0 0 0 8.456-3.13zM14 3.096c6.071 0 11 4.929 11 11s-4.929 11-11 11-11-4.929-11-11 4.929-11 11-11z" fill="currecntColor"></path>
                                <path d="m8 11.51-.707.707a1 1 0 0 0 1.414 1.414l.707-.707.707.707a1 1 0 1 0 1.415-1.414l-.708-.707.708-.707a1 1 0 0 0-1.415-1.414l-.707.707-.707-.707a.999.999 0 1 0-1.414 1.414zM16.5 11.51l-.707.707a1 1 0 0 0 1.414 1.414l.707-.707.707.707a1 1 0 1 0 1.415-1.414l-.708-.707.708-.707a1 1 0 0 0-1.415-1.414l-.707.707-.707-.707a.999.999 0 1 0-1.414 1.414zM10.669 19.839c.943-.849 1.872-1.277 2.815-1.262.963.015 1.912.481 2.875 1.286a1 1 0 0 0 1.282-1.535c-1.37-1.145-2.755-1.729-4.125-1.751-1.39-.022-2.795.524-4.185 1.775a1 1 0 0 0 1.338 1.487z" fill="currecntColor"></path>
                            </g>
                        </svg>
                    </span>
                    <span class="auto-suggest__content">
                        <?php echo Label::getLabel('LBL_No_Record_Found'); ?>
                    </span>
                </span>
            </a>
        </li>
    <?php } ?>
</ul>