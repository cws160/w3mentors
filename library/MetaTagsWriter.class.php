<?php

class MetaTagsWriter
{

    static function getMetaTags($controller, $action, $arrParameters)
    {
        $langId = MyUtility::getSiteLangId();
        $websiteName = FatApp::getConfig('CONF_WEBSITE_NAME_' . $langId, FatUtility::VAR_STRING, '');
        $controller = explode('-', FatUtility::camel2dashed($controller));
        array_pop($controller);
        $controllerName = implode('-', $controller);
        $controllerName = ucfirst(FatUtility::dashed2Camel($controllerName));
        $srch = new MetaTagSearch($langId);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $srch->addMultipleFields([
            'meta_id', 'IFNULL(meta_title, meta_identifier) as meta_title', 'meta_type',
            'meta_keywords', 'meta_description', 'meta_other_meta_tags', 'meta_og_title',
            'meta_og_url', 'meta_og_description', 'ogimg.file_name as ogimage'
        ]);
        $defSearch = clone $srch;
        $srch->addCondition('meta_controller', '=', $controllerName);
        $srch->addCondition('meta_action', '=', $action);
        $slug = $arrParameters[0] ?? '';
        if (!empty($slug)) {
            $srch->addCondition('meta_record_id', '=', $arrParameters[0]);
        }
        $rs = $srch->getResultSet();
        if (!$metas = FatApp::getDb()->fetch($rs)) {
            $defSearch->addCondition('meta_type', '=', MetaTag::META_GROUP_DEFAULT);
            $metas = FatApp::getDb()->fetch($defSearch->getResultSet());
        }
        if (empty($metas)) {
            return '<title>' . $websiteName . '</title>';
        }
        /* --Get opengraph image- */
        $title = $metas['meta_title'] . ' | ' . $websiteName;
        echo '<title>' . $title . '</title>' . "\n";
        if (isset($metas['meta_description'])) {
            echo '<meta name="description" content="' . htmlspecialchars($metas['meta_description']) . '" />';
        }
        if (isset($metas['meta_keywords'])) {
            echo '<meta name="keywords" content="' . htmlspecialchars($metas['meta_keywords']) . '" />';
        }
        if (isset($metas['meta_other_meta_tags'])) {
            echo CommonHelper::renderHtml($metas['meta_other_meta_tags'], ENT_QUOTES, 'UTF-8');
        }
        if (isset($metas['meta_og_title'])) {
            echo '<meta property="og:title" content="' . htmlspecialchars($metas['meta_og_title']) . '" />';
        }
        if (isset($metas['meta_og_url'])) {
            echo '<meta property="og:url" content="' . $metas['meta_og_url'] . '" />';
        }
        if (isset($metas['meta_og_description'])) {
            echo '<meta property="og:description" content="' . htmlspecialchars($metas['meta_og_description']) . '" />';
        }
        self::addOGImage($metas['meta_type'], $metas['meta_id'], $metas['ogimage'], $slug);
    }

    private static function addOGImage(int $type, int $metaId, $ogimage, string $slug = '')
    {
        if (CONF_WEBROOT_URL == CONF_WEBROOT_DASHBOARD) {
            return;
        }
        $langId = MyUtility::getSiteLangId();
        $ogext = current(array_reverse(explode(".", $ogimage ?? 'img.png')));
        if (MyUtility::isDemoUrl()) {
            $src = MyUtility::makeFullUrl('Images', 'og.png');
        } elseif ($type == MetaTag::META_GROUP_GRP_CLASS && $class = GroupClass::getClassBySlug($slug)) {
            $src = MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_GROUP_CLASS_BANNER, $class['grpcls_id'], Afile::SIZE_MEDIUM, $langId, uniqid() . '.' . $ogext]);
        } elseif ($type == MetaTag::META_GROUP_TEACHER && $user = User::getByUsername($slug)) {
            $src = MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_USER_PROFILE_IMAGE, $user['user_id'], Afile::SIZE_MEDIUM, $langId, uniqid() . '.' . $ogext]);
        } elseif ($type == MetaTag::META_GROUP_COURSE && $course = Course::getCourseBySlug($slug)) {
            $src = MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_COURSE_IMAGE, $course['course_id'], Afile::SIZE_MEDIUM, 0, uniqid() . '.' . $ogext]);
        } else {
            $src = MyUtility::makeFullUrl('Image', 'show', [Afile::TYPE_OPENGRAPH_IMAGE, $metaId, Afile::SIZE_MEDIUM, $langId, uniqid() . '.' . $ogext]);
        }
        echo '<meta property="og:image" content="' . $src . '" />';
    }
}
