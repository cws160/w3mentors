<?php
defined('SYSTEM_INIT') or die('Invalid Usage.');
$speakLanguagesArr = explode(",", $spoken_language_names);
$totalSpeakLanguages = count($speakLanguagesArr) - 1;
$speakLanguagesProficiencyArr = explode(",", $spoken_languages_proficiency);
?>
<?php
foreach ($speakLanguagesArr as $index => $spokenLangName) { ?>
    <?php if (isset($proficiencyArr[$speakLanguagesProficiencyArr[$index] ?? '']) && count($proficiencyArr) > 0) { ?>
        <span class="txt-inline__tag"><?php echo trim($spokenLangName); ?><strong>(<?php echo $proficiencyArr[$speakLanguagesProficiencyArr[$index]]; ?>)</strong></span><?php echo ($index < $totalSpeakLanguages) ? ',' : '';
    } else { ?>
        <span class="txt-inline__tag"><?php echo trim($spokenLangName); ?></span><?php echo ($index < $totalSpeakLanguages) ? ',' : '';
    }
}
?>