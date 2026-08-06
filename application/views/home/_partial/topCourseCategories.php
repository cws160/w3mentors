<?php defined('SYSTEM_INIT') or die('Invalid Usage.'); ?>
<?php if (!empty($categories)) { ?>
    <?php foreach ($categories as $row) { ?>
        <div class="colum-grid__item">
            <a href="<?php echo MyUtility::generateUrl('Courses', 'index') . '?catg=' . $row['cate_id'] ?>" class="colum-tile">
                <figure class="colum-tile__media">
                    <img src="<?php echo FatCache::getCachedUrl(MyUtility::makeUrl('Image', 'show', [Afile::TYPE_CATEGORY_IMAGE, $row['cate_id'], Afile::SIZE_LARGE], CONF_WEBROOT_FRONT_URL), CONF_DEF_CACHE_TIME); ?>" alt="">
                </figure>
                <span class="colum-tile__content">
                    <h6>
                        <?php
                        $string = CommonHelper::renderHtml($row['cate_name']);
                        echo (strlen($string) > 30) ? mb_substr($string, 0, 30, 'utf-8') . '...' : $string;
                        ?>
                    </h6>
                </span>
            </a>
        </div>
    <?php } ?>
<?php } ?>