<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (!empty($featuredLanguages)) { ?>
    <?php foreach ($featuredLanguages as $language) { ?>
        <?php $langName = CommonHelper::renderHtml($language['tlang_name']); ?>
        <div class="flag__box">
            <div class="flag__media">
                <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_TEACHING_LANGUAGES, $language['tlang_id'], Afile::SIZE_LARGE]), CONF_IMG_CACHE_TIME); ?>" alt="<?php echo $language['tlang_name']; ?>">
            </div>
            <div class="flag__name">
                <span><?php echo $langName ?></span>
            </div>
            <span class="link__arrow"></span>
            <a class="flag__action" aria-label='<?php echo htmlspecialchars($langName); ?>' href="<?php echo MyUtility::makeUrl('Teachers', 'languages', [$language['tlang_slug']]); ?>"></a>
        </div>
    <?php } ?>
<?php } ?>