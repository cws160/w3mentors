<?php
$row = 1;
$total = count($languages);
foreach ($languages as $key => $tlang) {
    if ($row == 1) {
        echo '<ul class="' . (($tlang['tlang_level'] > 0) ? 'is-dropdown' : 'parentDropdownJs') . '">';
    }
    if ($tlang['tlang_available'] == 1) {?>
        <li class="<?php echo ($tlang['tlang_subcategories'] > 0) ? 'is-child' : ''; ?>">
            <?php if (count($tlang['children']) > 0) { ?>
                <span class="trigger accordion-header"><?php echo CommonHelper::renderHtml($tlang['tlang_name']); ?></span>
                <?php
                /* added this because in recursive call special chars decoding is not working. */
                array_walk_recursive($tlang['children'], function (&$value, $key) {
                        if ($key == 'tlang_name') {
                            $value = CommonHelper::renderHtml($value);
                        }
                });
                ?>
                <?php $this->includeTemplate('teacher-request/languages.php', ['languages' => $tlang['children'], 'values' => $values]); ?>
            <?php } else { ?>
                <label class="accordion-trigger">
                    <input type="checkbox" value="<?php echo $key; ?>" name="tereq_teach_langs[]" <?php echo in_array($key, $values) ? 'checked' : ''; ?> >
                    <span class="accordion-trigger-action">
                        <span class="accordion-trigger-label  accordion-header"><?php echo $tlang['tlang_name']; ?></span>
                        <span class="accordion-trigger-icon"></span>
                    </span>
                </label>
            <?php } ?>
        </li>
        <?php
    }
    if ($row == $total) {
        echo '</ul>';
    }
    $row++;
}