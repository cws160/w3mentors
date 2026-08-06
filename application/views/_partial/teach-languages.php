<ul class="<?php echo isset($class) ? $class : 'list-vertical' ?> ">
    <?php
    if (count($teachLanguages) > 0) {
        foreach ($teachLanguages as $id => $language) {
            $listClass = (count($language['children']) > 0) ? 'has-child' : '';
            if ($language['tlang_available'] == 1) {
                $langName = CommonHelper::renderHtml($language['tlang_name']);
                $langClass = ($langPage) ? 'categorySelectOptJs' : '';
    ?>
                <li class="<?php echo $listClass; ?> list-items-js">
                    <label class="form-check">
                        <?php if (!$langPage) { ?>
                            <input class="form-check-input" type="checkbox" name="teachs[]" value="<?php echo $id; ?>" <?php echo (in_array($id, $values)) ? "checked='checked'" : ''; ?>>
                            </span>
                        <?php } else { ?>
                            <input class="d-none" type="checkbox" name="teachs[]" value="<?php echo $id; ?>" <?php echo (in_array($id, $values)) ? "checked='checked'" : ''; ?> onClick="selectLanguage('<?php echo $language['tlang_slug']; ?>', '<?php echo $id; ?>')">
                        <?php } ?>
                        <span class="form-check-label optionItemJs">
                            <?php echo CommonHelper::renderHtml($langName); ?>
                        </span>
                    </label>
                    <?php
                    if (count($language['children']) > 0) {
                        $this->includeTemplate('_partial/teach-languages.php', ['teachLanguages' => $language['children'], 'values' => $values, 'class' => 'list-vertical-child', 'langPage' => $langPage]);
                    }
                    ?>
                </li>
    <?php
            }
        }
    }
    ?>
</ul>