<?php

/**
 * This class is used to handle Meta Tags
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class MetaTag extends MyAppModel
{

    const DB_TBL = 'tbl_meta_tags';
    const DB_TBL_PREFIX = 'meta_';
    const DB_LANG_TBL = 'tbl_meta_tags_lang';
    const DB_LANG_TBL_PREFIX = 'metalang_';
    const META_GROUP_DEFAULT = -1;
    const META_GROUP_OTHER = 0;
    const META_GROUP_TEACHER = 1;
    const META_GROUP_GRP_CLASS = 2;
    const META_GROUP_CMS_PAGE = 3;
    const META_GROUP_BLOG_CATEGORY = 4;
    const META_GROUP_BLOG_POST = 5;
    const META_GROUP_COURSE = 6;
    const META_GROUP_COURSE_CERTIFICATE = 7;
    const META_GROUP_QUIZ_CERTIFICATE = 8;
    const META_GROUP_COURSE_EVALUATION_CERTIFICATE = 9;
    const META_GROUP_TEACH_LANGUAGE = 10;

    /**
     * Initialize MetaTag
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'meta_id', $id);
    }

    /**
     * Get Tabs Array
     * 
     * @return array
     */
    public static function getTabsArr(): array
    {
        $metaArr = [
            static::META_GROUP_DEFAULT => ['name' => Label::getLabel('METALBL_Default'), 'controller' => 'Default', 'action' => 'Default'],
            static::META_GROUP_OTHER => ['name' => Label::getLabel('METALBL_Others'), 'controller' => '', 'action' => ''],
            static::META_GROUP_TEACHER => ['name' => Label::getLabel('METALBL_Teachers'), 'controller' => 'Teachers', 'action' => 'view'],
            static::META_GROUP_CMS_PAGE => ['name' => Label::getLabel('METALBL_CMS_Page'), 'controller' => 'Cms', 'action' => 'view'],
            static::META_GROUP_BLOG_CATEGORY => ['name' => Label::getLabel('METALBL_Blog_Categories'), 'controller' => 'Blog', 'action' => 'category'],
            static::META_GROUP_BLOG_POST => ['name' => Label::getLabel('METALBL_Blog_Posts'), 'controller' => 'Blog', 'action' => 'postDetail'],
            static::META_GROUP_TEACH_LANGUAGE => ['name' => Label::getLabel('METALBL_TEACH_LANGUAGE'), 'controller' => 'teachers', 'action' => 'languages'],
        ];
        if (Course::isEnabled()) {
            $metaArr[static::META_GROUP_COURSE] = ['name' => Label::getLabel('METALBL_Courses'), 'controller' => 'Courses', 'action' => 'view'];
        }
        if (GroupClass::isEnabled()) {
            $metaArr[static::META_GROUP_GRP_CLASS] = ['name' => Label::getLabel('METALBL_Group_Classes'), 'controller' => 'GroupClasses', 'action' => 'view'];
        }
        return $metaArr;
    }
    
    public static function getTypes(int $key = null)
    {
        $arr = [
            static::META_GROUP_DEFAULT => Label::getLabel('METALBL_Default'),
            static::META_GROUP_OTHER => Label::getLabel('METALBL_Others'),
            static::META_GROUP_TEACHER => Label::getLabel('METALBL_Teachers'),
            static::META_GROUP_CMS_PAGE => Label::getLabel('METALBL_CMS_Page'),
            static::META_GROUP_BLOG_CATEGORY => Label::getLabel('METALBL_Blog_Categories'),
            static::META_GROUP_BLOG_POST => Label::getLabel('METALBL_Blog_Posts'),
            static::META_GROUP_TEACH_LANGUAGE => Label::getLabel('METALBL_TEACH_LANGUAGE'),
        ];
        if (GroupClass::isEnabled()) {
            $metaArr[static::META_GROUP_GRP_CLASS] = Label::getLabel('METALBL_Group_Classes');
        }
        return AppConstant::returArrValue($arr, $key);
    }

    /**
     * Get Original URL From Components
     * 
     * @param array $row
     * @return boolean
     */
    public static function getOrignialUrlFromComponents(array $row)
    {
        if (empty($row) || $row['meta_controller'] == '') {
            return false;
        }
        $url = '';
        foreach ([$row['meta_controller'], $row['meta_action'], $row['meta_record_id']] as $value) {
            if ($value != '0' && $value != '') {
                $url .= $value . '/';
            }
        }
        return rtrim($url, '/');
    }

    public static function getMetaTag(int $type, $recordId)
    {
        $srch = new SearchBase(static::DB_TBL, 'meta');
        $srch->addFld('meta_id');
        $srch->addCondition('meta_record_id', '=', $recordId);
        $srch->addCondition('meta_type', '=', $type);
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    public function addMetaTag(int $type, string $recordId, string $identifier)
    {
        $tabsArr = MetaTag::getTabsArr();
        $data = [
            'meta_controller' => $tabsArr[$type]['controller'],
            'meta_action' => $tabsArr[$type]['action'],
            'meta_type' => $type,
            'meta_record_id' => $recordId,
            'meta_identifier' => $identifier
        ];
        $this->assignValues($data);
        return $this->save();
    }

    public function updateTeacherMetaKeyword(int $userId): bool
    {
        $teachLangIds = (new UserTeachLanguage($userId))->getUserTechLandIds();
        if (empty($teachLangIds)) {
            return true;
        }
        $languages = Language::getAllNames();
        $teachLangName = TeachLanguage::getNamesByLangIds(array_keys($languages), $teachLangIds);
        foreach ($languages as $langId => $langName) {
            $data = [
                'metalang_meta_id' => $this->getMainTableRecordId(),
                'metalang_lang_id' => $langId,
                'meta_keywords' => implode(', ', array_column($teachLangName, $langId)),
            ];
            if (!$this->updateLangData($langId, $data)) {
                return false;
            }
        }
        return true;
    }

    public function updateTeacherDes(int $langId, string $description): bool
    {
        $data = [
            'metalang_meta_id' => $this->getMainTableRecordId(),
            'metalang_lang_id' => $langId,
            'meta_og_description' => $description,
            'meta_description' => $description,
        ];
        return $this->updateLangData($langId, $data);
    }

    public function updateClassLangData(int $langId, array $classData)
    {
        $data = [
            'metalang_meta_id' => $this->getMainTableRecordId(),
            'metalang_lang_id' => $langId,
            'meta_title' => $classData['grpcls_title'],
            'meta_og_title' => $classData['grpcls_title'],
            'meta_og_description' => $classData['grpcls_description'],
            'meta_description' => $classData['grpcls_description']
        ];
        return $this->updateLangData($langId, $data);
    }

    public function updateCoursesData(int $langId, array $courseData)
    {
        $tags = json_decode($courseData['course_srchtags']);
        $data = [
            'metalang_meta_id' => $this->getMainTableRecordId(),
            'metalang_lang_id' => $langId,
            'meta_title' => $courseData['course_title'],
            'meta_og_title' => $courseData['course_title'],
            'meta_og_description' => $courseData['course_subtitle'],
            'meta_description' => $courseData['course_subtitle'],
            'meta_keywords' => implode(', ', $tags),
            'meta_og_url' => MyUtility::makeFullUrl('Courses', 'view', [$courseData['course_slug']], CONF_WEBROOT_FRONTEND)
        ];
        return $this->updateLangData($langId, $data);
    }

    public function updateTeachLangugesData(int $langId, array $langData)
    {
        $data = [
            'metalang_meta_id' => $this->getMainTableRecordId(),
            'metalang_lang_id' => $langId,
            'meta_title' => $langData['meta_title'],
            'meta_og_url' => MyUtility::makeFullUrl('Teachers', 'languages', [$langData['tlang_slug']], CONF_WEBROOT_FRONTEND)
        ];
        return $this->updateLangData($langId, $data);
    }
}
