<?php

/**
 * This class is used to handle Teach Languages
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class TeachLanguage extends MyAppModel
{

    const DB_TBL = 'tbl_teach_languages';
    const DB_TBL_LANG = 'tbl_teach_languages_lang';
    const DB_TBL_PREFIX = 'tlang_';
    const PROFICIENCY_TOTAL_BEGINNER = 1;
    const PROFICIENCY_BEGINNER = 2;
    const PROFICIENCY_UPPER_BEGINNER = 3;
    const PROFICIENCY_INTERMEDIATE = 4;
    const PROFICIENCY_UPPER_INTERMEDIATE = 5;
    const PROFICIENCY_ADVANCED = 6;
    const PROFICIENCY_UPPER_ADVANCED = 7;
    const PROFICIENCY_NATIVE = 8;
    const ROOT_PARENT_ID = 0;
    const MAX_LEVEL = 2;
    /**
     * Initialize Teach Language
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'tlang_id', $id);
    }

    /**
     * Add or update row
     * 
     * @param array $post
     * @return bool
     */
    public function setup($post)
    {
        if (!$this->validate($post)) {
            return false;
        }
        $db = FatApp::getDb();
        $db->startTransaction();

        if(!$this->setupMetaData($post)){
            $db->rollbackTransaction();
            return false;
        }
        $this->assignValues($post);
        if(!$this->save()){
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->setParentsAndLevels($post['tlang_parent'])) {
            $db->rollbackTransaction();
            return false;
        }
        /* if (!$this->setVisibility($post['tlang_active'])) {
            return false;
        } */
        if (!$this->setVisibility($post['tlang_active'])) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->updateSubCatCount()) {
            $db->rollbackTransaction();
            return false;
        }
        $this->setupUserTeachLangs($post);
        
        $db->commitTransaction();
        return true;
    }

    /**
     * Setup Meta data for teach languges
     *
     * @param array $post
     */
    private function setupMetaData($post)
    {
        $metaData = MetaTag::getMetaTag(MetaTag::META_GROUP_TEACH_LANGUAGE, $post['tlang_slug']);
        $meta = new MetaTag($metaData['meta_id'] ?? 0);
        $post['meta_title'] = $identifier = str_replace('-', ' ', $post['tlang_slug']).' '.Label::getLabel('LBL_TEACH_LANGUAGE');
        
        if (!$meta->addMetaTag(MetaTag::META_GROUP_TEACH_LANGUAGE, $post['tlang_slug'], $identifier)) {
            $this->error = $meta->getError();
            return false;
        }
        $languages = Language::getAllNames();
        foreach ($languages as $langId => $value) {
            if (!$meta->updateTeachLangugesData($langId, $post)) {
                $this->error = $meta->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Setup teacher languages stats data
     *
     * @param array $post
     */
    private function setupUserTeachLangs(array $post)
    {
        $teachLang = new UserTeachLanguage();
        if ($post['tlang_parent'] > 0) {
            $teachLang->removeTeachLang([$post['tlang_parent']]);
        }
        if ($post['tlang_active'] == AppConstant::NO) {
            $teachLang->removeTeachLang([$this->getMainTableRecordId()]);
        } elseif (FatApp::getConfig('CONF_MANAGE_PRICES')) {
            $teachLang->updateLangPrice($this->getMainTableRecordId(), $post['tlang_hourly_price'] ?? 0);
        }
        (new TeacherStat(0))->setTeachLangPricesBulk();
    }

    /**
     * Update parent ids and level of the language
     *
     * @param int $post
     * @return bool
     */
    private function setParentsAndLevels(int $parentId)
    {
        /* get all sub language ids */
        $subLanguages = static::getTeachLanguages(0, false, ['tlang_parentids' => $this->getMainTableRecordId()]);

        /* array of current language, its parent and all child languages */
        $tlangIds = array_merge([$this->getMainTableRecordId(), $parentId], array_keys($subLanguages));
        $db = FatApp::getDb();
        foreach ($tlangIds as $tlangId) {
            $parentIds = [];
            $langlevel = $this->getLangLevel($tlangId, $parentIds);
            if ($langlevel > static::MAX_LEVEL) {
                $errorMsg = Label::getLabel('LBL_YOU_CAN_ONLY_ADD_MAXIMUM_{systemlevel}_LEVEL_CATEGORIES');
                $this->error = str_replace("{systemlevel}", (static::MAX_LEVEL + 1), $errorMsg);
                return false;
            }

            $data = ['tlang_level' => $langlevel, 'tlang_parentids' => implode(",", $parentIds)];
            if(!$db->updateFromArray(static::DB_TBL, $data, ['smt' => 'tlang_id = ?', 'vals' => [$tlangId]])){
                $this->error = $db->getError();
                return false;
            }
        }
        return true;
    }

    /**
     * Validate Language data
     *
     * @param array $post
     * @return bool
     */
    private function validate(array $post)
    {
        $tlangId = $this->getMainTableRecordId();
        if ($tlangId > 0 && !($data = static::getAttributesById($tlangId, ['tlang_parent', 'tlang_subcategories']))) {
            $this->error = Label::getLabel("LBL_LANGUAGE_NOT_FOUND");
            return false;
        }
        if (!$this->checkUnique($post['tlang_identifier'], $post['tlang_parent'])) {
            return false;
        }
        if ($post['tlang_parent'] > 0) {
            if (empty($parentData = static::getAttributesById($post['tlang_parent'], ['tlang_parentids', 'tlang_level']))) {
                $this->error = Label::getLabel("LBL_PARENT_NOT_FOUND");
                return false;
            }
            if ($parentData['tlang_level'] == static::MAX_LEVEL || (($data['tlang_subcategories'] ?? 0) > 0 && $parentData['tlang_level'] == static::MAX_LEVEL - 1)) {
                $errorMsg = Label::getLabel('LBL_YOU_CAN_ONLY_ADD_MAXIMUM_{systemlevel}_LEVEL_CATEGORIES');
                $this->error = str_replace("{systemlevel}", (static::MAX_LEVEL + 1), $errorMsg);
                return false;
            }
            if (!empty($post['tlang_featured'])) {
                $this->error = Label::getLabel("LBL_SUB_LANGUAGES_CANNOT_BE_MARKED_AS_FEATURED");
                return false;
            }
        }
        if ($post['tlang_active'] == AppConstant::INACTIVE && $tlangId > 0) {
            if (!empty(static::getByParentId($tlangId))) {
                $this->error = Label::getLabel("LBL_CANNOT_MARK_INACTIVE_AS_THERE_ARE_ACTIVE_SUB_LANGUAGES_ATTACHED");
                return false;
            }
        }
        return true;
    }

    /**
     * Update status to active/inactive
     *
     * @param int $status
     * @return bool
     */
    public function updateStatus(int $status = AppConstant::ACTIVE)
    {
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, ['tlang_active' => $status], ['smt' => 'tlang_id = ?', 'vals' => [$this->mainTableRecordId]])) {
            $db->rollbackTransaction();
            $this->error = $db->getError();
            return false;
        }
        if (!$this->setVisibility($status)) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Set nodes visibility on the basis of children status
     *
     * @param int $status
     * @return bool
     */
    private function setVisibility(int $status)
    {
        $tlangId = $this->getMainTableRecordId();
        $data = static::getAttributesById($tlangId, ['tlang_parent', 'tlang_subcategories', 'tlang_id']);
        $availability = ($status == AppConstant::ACTIVE) ? AppConstant::YES : AppConstant::NO;
        $availableChildren = [];
        /* handle root level languages */
        if ($data['tlang_parent'] == 0) {
            $langIds = [$tlangId];
            /* check all child status when marked active */
            if ($status == AppConstant::ACTIVE && $data['tlang_subcategories'] > 0) {
                if (!$this->traverseChild([$data], $availableChildren)) {
                    return false;
                }
                if (empty($availableChildren)) {
                    return true;
                }
                $langIds = $langIds + $availableChildren;
            }
            /* update availability */
            if (!$this->updateAvailability($langIds, $availability)) {
                return false;
            }
            return true;
        }
        
        /* Handle languages other than root level */
        
        /* update availability */
        if (!$this->updateAvailability($tlangId, $availability)) {
            return false;
        }
        if ($status == AppConstant::INACTIVE) {
            /* Check same level languages status to mark statuses of parent level */
            if (!$this->traverseSameLevel($data)) {
                return false;
            }
            return true;
        }

        /* update all children status if child exists */
        if ($data['tlang_subcategories'] > 0) {
            if (!$this->traverseChild([$data], $availableChildren)) {
                return false;
            }
        }
        /* if child available then check all parents and update status */
        $isParentAvailable = 0;
        if (!$this->traverseParent([$data], $isParentAvailable)) {
            return false;
        }
        if ($isParentAvailable && $availableChildren) {
            if (!$this->updateAvailability($availableChildren, $isParentAvailable)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Traverse same level records to check and update availability status
     *
     * @param array $data
     * @return bool
     */
    private function traverseSameLevel(array $data)
    {
        /* get all same level records */
        $children = static::getTeachLanguages(0, false, ['tlang_parent' => $data['tlang_parent']]);

        /* return if no child exists */
        if (empty($children)) {
            return true;
        }
        $parentAvailbility = 0;
        /* check available records & mark parent unavailable if no child is available */
        $availableChildren = array_column($children, 'tlang_available');
        if (array_sum($availableChildren) > 0) {
            $parentAvailbility = 1;
        }
        if (!$this->updateAvailability($data['tlang_parent'], $parentAvailbility)) {
            return false;
        }
        /* break if no more parent level exist */
        if ($data['tlang_parent'] == 0) {
            return true;
        }
        /* get parent of current record and execute the same process recursively */
        $parent = static::getAttributesById($data['tlang_parent'], ['tlang_parent']);
        $this->traverseSameLevel($parent);
        return true;
    }

    /**
     * Traverse parents at all levels recursively to check & update status
     *
     * @param array $languages
     * @param int $isParentAvailable
     * @return bool
     */
    private function traverseParent(array $languages, int &$isParentAvailable = 0)
    {
        $isParentAvailable = 1;
        $tlangIds = [];
        foreach ($languages as $data) {
            /* break loop if no parent */
            if ($data['tlang_parent'] == 0) {
                break;
            }
            /* get parent details */
            $parents = static::getTeachLanguages(0, false, ['tlang_ids' => [$data['tlang_parent']]]);
            $parent = current($parents);
            if (empty($parents) || $parent['tlang_active'] == AppConstant::NO) {
                $isParentAvailable = 0;
                /* mark availablity of current record unavailable if parent is not active */
                if (!$this->updateAvailability($data['tlang_id'], AppConstant::NO)) {
                    return false;
                }
                break;
            } else {
                $isParentAvailable = 1;
                $tlangIds[] = $data['tlang_parent'];
            }
            /* reiterate the process to check next parent level */
            $this->traverseParent($parents);
        }
        /* mark parent available */
        if (!empty($tlangIds)) {
            if (!$this->updateAvailability($tlangIds, $isParentAvailable)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Traverse all child levels recursively to check & update status
     *
     * @param array $languages
     * @param mixed $availableChild
     * @return bool
     */
    private function traverseChild(array $languages, &$availableChild)
    {
        foreach ($languages as $data) {
            /* mark unavailable if child exists but not active */
            $children = static::getByParentId($data['tlang_id']);
            if ($data['tlang_subcategories'] > 0 && !$children) {
                $availableChild = [];
                if (!$this->updateAvailability($data['tlang_id'], AppConstant::NO)) {
                    return false;
                }
                break;
            }
            /* create array to mark available */
            $availableChild[] = $data['tlang_id'];

            /* break if last level reached */
            if (!$children) {
                break;
            }
            /* repeat process until all levels processed */
            $this->traverseChild($children, $availableChild);
        }
        return true;
    }

    /**
     * Update availability status
     *
     * @param mixed $id
     * @param int $availability
     * @return bool
     */
    private function updateAvailability($ids, int $availability)
    {
        $db = FatApp::getDb();
        $ids = !is_array($ids) ? [$ids] : $ids;
        $str = trim(str_repeat("?,", count($ids)), ',');
        if (!$db->updateFromArray(static::DB_TBL, ['tlang_available' => $availability], ['smt' => 'tlang_id IN (' . $str . ')', 'vals' => $ids])) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Check unique category
     *
     * @param string $identifier
     * @param int    $parent
     * @return void
     */
    public function checkUnique(string $identifier, int $parent = 0)
    {
        $srch = new SearchBase(static::DB_TBL, 'tlang');
        $srch->addCondition('mysql_func_LOWER(tlang_identifier)', '=', strtolower(trim($identifier)), 'AND', true);
        $srch->addCondition('tlang_parent', '=', $parent);
        if ($this->getMainTableRecordId() > 0) {
            $srch->addCondition('tlang_id', '!=', $this->getMainTableRecordId());
        }
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $category = FatApp::getDb()->fetch($srch->getResultSet());
        if (!empty($category)) {
            $this->error = Label::getLabel('LBL_LANGUAGE_IDENTIFIER_ALREADY_IN_USE');
            return false;
        }
        return true;
    }

    /**
     * Get data by parent id
     *
     * @param int $tlangId
     * @return array
     */
    public static function getByParentId(int $tlangId, $langId = 0, $active = true)
    {
        $srch = static::getSearchObject($langId, $active);
        $srch->addCondition('tlang_parent', '=', $tlangId);
        return FatApp::getDb()->fetchAll($srch->getResultSet());
    }

    /**
     * Recursive function to get language level 
     *
     * @param int $tlangId
     * @param array $parentIds
     * @param integer $level
     * @return int
     */
    private function getLangLevel(int $tlangId, array &$parentIds = [], int $level = 0)
    {
        if ($level > 0) {
            $parentIds[$tlangId] = $tlangId;
        }
        $tlangParentId = TeachLanguage::getAttributesById($tlangId, 'tlang_parent') ?? 0;
        if ($tlangParentId > 0) {
            $level++;
            if ($level > static::MAX_LEVEL) {
                return $level;
            }
            return $this->getLangLevel($tlangParentId, $parentIds, $level);
        }
        return $level;
    }

    /**
     * Remove row by id
     * 
     * @param int $langId
     * @return bool
     */
    public function remove($tLangId)
    {
        $db = FatApp::getDb();
        $db->startTransaction();
        if (!$this->setVisibility(AppConstant::INACTIVE)) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->deleteRecord($tLangId)) {
            $db->rollbackTransaction();
            return false;
        }
        if (!$this->updateSubCatCount()) {
            $db->rollbackTransaction();
            return false;
        }
        $db->commitTransaction();
        return true;
    }

    /**
     * Get Search Object
     * 
     * @param int $langId
     * @param bool $active
     * @return SearchBase
     */
    public static function getSearchObject(int $langId = 0, bool $active = true): SearchBase
    {
        $srch = new SearchBased(static::DB_TBL, 'tlang');
        if ($langId > 0) {
            $srch->joinTable(static::DB_TBL_LANG, 'LEFT OUTER JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id AND tlanglang_lang_id = ' . $langId, 'tlanglang');
        }
        if ($active == true) {
            $srch->addCondition('tlang.tlang_active', '=', AppConstant::ACTIVE);
        }
        return $srch;
    }

    /**
     * Get All Languages
     * 
     * @param int $langId
     * @param bool $active
     * @param bool $lastLevel
     * @return array
     */
    public static function getAllLangs(int $langId, bool $active = false, bool $lastLevel = false)
    {
        $srch = new SearchBase(static::DB_TBL, 'tlang');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id AND tlanglang.tlanglang_lang_id = ' . $langId, 'tlanglang');
        $srch->addMultiplefields(['tlang_id', 'IFNULL(tlang_name, tlang_identifier) as tlang_name']);
        if ($active) {
            $srch->addCondition('tlang_active', '=', AppConstant::YES);
        }
        if ($lastLevel) {
            $srch->addCondition('tlang_subcategories', '=', 0);
        }
        $srch->addOrder('tlang_name', 'ASC');
        $srch->addOrder('tlang_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

    /**
     * Get array with parent child relation
     *
     * @param int $langId
     * @param int $parentId
     * @param array $tlangIds
     * @return array
     */
    public static function getTeachLangsRecursively(int $langId, int $parentId = 0, array $tlangIds = [])
    {
        $teachLanguages = TeachLanguage::getTeachLanguages($langId, true, ['tlang_parent' => $parentId, 'tlang_ids' => $tlangIds]);
        foreach ($teachLanguages as &$langs) {
            if (!empty($tlangIds) && !in_array($langs['tlang_id'], $tlangIds)) {
                continue;
            }
            $langs['children'] = self::getTeachLangsRecursively($langId, $langs['tlang_id'], $tlangIds);
        }
        return $teachLanguages;
    }

    /**
     * Get Lang By Id
     * 
     * @param int $tLangId
     * @param int $langId
     * @return string
     */
    public static function getLangById(int $tLangId, int $langId): string
    {
        $langId = ($langId > 0) ? $langId : MyUtility::getSiteLangId();
        $srch = new SearchBase(static::DB_TBL, 'tlang');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id =' . $langId, 'tlanglang');
        $srch->addMultipleFields(['IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name']);
        $srch->addCondition('tlang_id', '=', $tLangId);
        $srch->doNotCalculateRecords();
        $teachLangs = FatApp::getDb()->fetch($srch->getResultSet());
        return $teachLangs['tlang_name'] ?? '';
    }

    /**
     * Get Names
     * 
     * @param int $langId
     * @param array $teachLangIds
     * @param bool $active
     * @return array
     */
    public static function getNames(int $langId, array $teachLangIds = [], bool $active = true): array
    {
        if (empty($langId)) {
            return [];
        }
        
        $formatedTlangs = TeachLanguage::getTeachLangNames($langId, 0, $active);
        $formatedTlangs = array_column($formatedTlangs, 'tlang_name', 'tlang_id');
        if (empty($teachLangIds)) {
            return $formatedTlangs;
        }
        
        $tlangArr = [];
        $teachLangIds = array_filter(array_unique($teachLangIds));
        foreach ($teachLangIds as $tlangId) {
            $tlangArr[$tlangId] = $formatedTlangs[$tlangId] ?? Label::getLabel('LBL_NA');
        }
        return $tlangArr;
    }

    /**
     * Get Teach Languages
     * 
     * @param int $langId
     * @return array
     */
    public static function getTeachLanguages(int $langId, $active = true, array $data = []): array
    {
        $srch = new SearchBase(static::DB_TBL, 'tlang');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id AND tlanglang.tlanglang_lang_id = ' . $langId, 'tlanglang');
        
        if($active){
            $srch->addCondition('tlang_active', '=', AppConstant::YES);
        }
        if (!empty($data)) {
            if (isset($data['tlang_parent'])) {
                $srch->addCondition('tlang_parent', '=', $data['tlang_parent']);
            }
            if (isset($data['tlang_level'])) {
                $srch->addCondition('tlang_level', '=', $data['tlang_level']);
            }
            if (isset($data['tlang_featured'])) {
                $srch->addCondition('tlang_featured', '=', $data['tlang_featured']);
            }
            if (isset($data['keyword']) && !empty($data['keyword'])) {
                $cond = $srch->addCondition('tlanglang.tlang_name', 'LIKE',  '%' . $data['keyword'] . '%');
                $cond->attachCondition('tlang.tlang_identifier', 'LIKE',  '%' . $data['keyword'] . '%');
            }
            if (isset($data['tlang_parentids'])) {
                $srch->addDirectCondition('FIND_IN_SET(' . $data['tlang_parentids'] . ', tlang_parentids)');
            }
            if(isset($data['pagesize']) && $data['pagesize'] > 0) {
                $srch->setPageSize($data['pagesize']);
            }
            if (isset($data['tlang_ids']) && !empty($data['tlang_ids'])) {
                $srch->addCondition('tlang_id', 'IN', $data['tlang_ids']);
            }
            if (isset($data['available']) && !empty($data['available'])) {
                $srch->addCondition('tlang_available', '=', AppConstant::YES);
            }
            if (isset($data['available']) && !empty($data['available'])) {
                $srch->addCondition('tlang_available', '=', AppConstant::YES);
            }
        }
        $srch->addMultiplefields([
            'tlang_id', 'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name', 'tlang_slug', 'tlang_parent', 'tlang_subcategories', 'tlang_active',
            'tlang_level', 'tlang_parentids', 'tlang_order', 'tlang_available', 'tlang_description'
        ]);
        $srch->addDirectCondition('tlang_slug IS NOT NULL');
        $srch->addOrder('tlang_order', 'ASC');
        $srch->addOrder('tlang_id', 'DESC');
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
    }

    /**
     * Get Popular Languages
     * 
     * @param int $langId
     * @return array
     */
    public static function getPopularLangs(int $langId): array
    {
        $srch = new SearchBase(TeachLanguage::DB_TBL, 'tlang');
        $srch->joinTable(Order::DB_TBL_LESSON, 'INNER JOIN', 'ordles.ordles_tlang_id = tlang.tlang_id', 'ordles');
        $srch->joinTable(Order::DB_TBL, 'INNER JOIN', 'orders.order_id = ordles.ordles_order_id', 'orders');
        $srch->joinTable(TeachLanguage::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id '
                . ' AND tlanglang.tlanglang_lang_id = ' . $langId, 'tlanglang');
        $srch->joinTable(UserTeachLanguage::DB_TBL, 'LEFT JOIN', 'utlang.utlang_tlang_id = tlang.tlang_id', 'utlang');
        $srch->addMultiplefields(['tlang_id', 'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name', 'tlang_slug', 'COUNT(DISTINCT utlang.utlang_user_id) as teacher_count']);
        $srch->addCondition('tlang.tlang_active', '=', AppConstant::YES);
        $srch->addCondition('tlang.tlang_available', '=', AppConstant::YES);
        $srch->addOrder('count(orders.order_id)', 'DESC');
        $srch->addOrder('tlang_name', 'ASC');
        $srch->addGroupBy('tlang_id');
        $srch->doNotCalculateRecords();
        $srch->setPageSize(12);
        $teachingLangs = FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
        if (empty($teachingLangs)) {
            return [];
        }
        return $teachingLangs;
    }

    /**
     * Get Featured Languages
     *
     * @param int $langId
     * @return array
     */
    public static function getFeaturedLangs(int $langId)
    {
        $srch = new SearchBase(static::DB_TBL, 'tlang');
        $srch->joinTable(static::DB_TBL_LANG, 'LEFT JOIN', 'tlanglang.tlanglang_tlang_id = tlang.tlang_id AND tlanglang.tlanglang_lang_id = ' . $langId, 'tlanglang');
        $srch->addMultiplefields(['tlang_id', 'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name', 'tlang_slug']);
        $srch->addCondition('tlang_active', '=', AppConstant::YES);
        $srch->addCondition('tlang_available', '=', AppConstant::YES);
        $srch->addCondition('tlang_featured', '=', AppConstant::YES);
        $srch->addCondition('tlang_parent', '=', 0);
        $srch->addOrder('tlang_order', 'ASC');
        $srch->addOrder('tlang_name', 'ASC');
        $srch->addOrder('tlang_id', 'DESC');
        $srch->setPageSize(12);
        $srch->doNotCalculateRecords();
        $srch->addGroupBy('tlang_id');
        $teachingLangs = FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
        if (empty($teachingLangs)) {
            return [];
        }
        return $teachingLangs;
    }

    /**
     * Get teach language names according to the languages 
     * 
     * @param array $langIds
     * @param array $teachLangIds
     * @return array
     */
    public static function getNamesByLangIds(array $langIds, array $teachLangIds): array
    {
        $teachLangIds = array_filter(array_unique($teachLangIds));
        $langIds = array_filter(array_unique($langIds));
        if (empty($langIds) || empty($teachLangIds)) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL, 'tlang');
        $srch->joinTable(static::DB_TBL_LANG, "LEFT JOIN", "tlanglang.tlanglang_tlang_id = tlang.tlang_id and tlanglang.tlanglang_lang_id IN ('" . implode("','", $langIds) . "')", 'tlanglang');
        $srch->addMultipleFields([
            'tlang.tlang_id', 'CONCAT(tlang_id, "-" ,IFNULL(tlanglang_lang_id, 0)) as tlangKey',
            'tlang.tlang_identifier', 'tlanglang_lang_id',
            'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name'
        ]);
        $srch->addCondition('tlang.tlang_id', 'IN', $teachLangIds);
        $srch->addOrder('tlang_id', 'desc');
        $srch->doNotCalculateRecords();
        $resultSet = $srch->getResultSet();
        $rows = FatApp::getDb()->fetchAll($resultSet, 'tlangKey');
        $identifierArray = array_column($rows, 'tlang_identifier', 'tlang_id');
        $teachLangs = [];
        foreach ($teachLangIds as $teachLangId) {
            foreach ($langIds as $langId) {
                $name = (!empty($rows[$teachLangId . '-' . $langId])) ? $rows[$teachLangId . '-' . $langId]['tlang_name'] : $identifierArray[$teachLangId] ?? null;
                $teachLangs[$teachLangId][$langId] = $name;
            }
        }

        return $teachLangs;
    }

    /**
     * Get teach language as associative array
     * 
     * @param int $langId
     * @param array $ignoreId
     * @param bool $active
     * @return array
     */
    public static function getOptions(int $langId, array $ignoreIds, bool $active = true)
    {
        $teachLanguages = self::getTeachLangNames($langId, 0, $active);
        foreach ($teachLanguages as $key => $langs) {
            if (in_array($langs['tlang_parent'], $ignoreIds) || in_array($langs['tlang_id'], $ignoreIds) || $langs['tlang_level'] >= self::MAX_LEVEL) {
                $ignoreIds[] = $langs['tlang_id'];
                unset($teachLanguages[$key]);
            }
        }
        return array_column($teachLanguages, 'tlang_name', 'tlang_id');
    }

    /**
     * Get languages with parent names.
     *
     * @param int $langId
     * @param int $parentId
     * @param bool $active
     * @param array $array
     * @return array
     */
    public static function getTeachLangNames(int $langId, int $parentId = 0, $active = true, array &$array = [])
    {
        $teachLanguages = self::getTeachLanguages($langId, $active, ['tlang_parent' => $parentId]);
        foreach ($teachLanguages as $langs) {
            if (!empty($array[$langs['tlang_parent']])) {
                $langs['tlang_name'] = $array[$langs['tlang_parent']]['tlang_name'] . " » " . $langs['tlang_name'];
            }
            $array[$langs['tlang_id']]['tlang_name'] = $langs['tlang_name'];
            $array[$langs['tlang_id']]['tlang_id'] = $langs['tlang_id'];
            $array[$langs['tlang_id']]['tlang_parent'] = $langs['tlang_parent'];
            $array[$langs['tlang_id']]['tlang_level'] = $langs['tlang_level'];
            $array[$langs['tlang_id']]['tlang_order'] = count($array);
            if ($langs['tlang_subcategories'] > 0) {
                self::getTeachLangNames($langId, $langs['tlang_id'], $active, $array);
            }
        }
        return $array;
    }

    /**
     * Update count of subcategories
     * @return bool
     */
    public function updateSubCatCount()
    {
        $db = FatApp::getDb();
        if(!$db->query(
            "UPDATE ".static::DB_TBL." tmp 
            LEFT JOIN (SELECT COUNT(*) AS ttl, tlang_parent FROM ".static::DB_TBL." GROUP BY `tlang_parent`) tmp1 
            ON tmp.tlang_id = tmp1.tlang_parent 
            SET tmp.tlang_subcategories = tmp1.ttl WHERE tmp.tlang_level < ".static::MAX_LEVEL
        )){
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Search languages by keyword
     *
     * @param string $keyword
     * @param int $langId
     * @return array
     */
    public static function searchByKeyword(string $keyword, int $langId)
    {
        $srch = TeachLanguage::getSearchObject($langId, true);
        $cond = $srch->addCondition('tlang_identifier', 'LIKE', '%' . $keyword . '%');
        $cond->attachCondition('tlang_name', 'LIKE', '%' . $keyword . '%', 'OR', true);
        $srch->addFld('tlang_id');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'tlang_id');
    }

    /**
     * Function to format all level languages to the single level
     *
     * @param array $languages
     * @return array
     */
    public static function format(array $languages, $lastLevel = false)
    {
        $formattedData = [];
        $order = 1;
        recursion:

        $subArr = [];
        foreach ($languages as $lang) {
            /* add parent to child id array */
            if (!empty($lang['tlang_parent'])) {
                $formattedData[$lang['tlang_id']] = $formattedData[$lang['tlang_parent']];
            }
            
            /* create an array of next level languages */
            if (!empty($lang['children'])) {
                array_map(function ($v) use (&$subArr) {
                    $subArr[] = $v;
                }, $lang['children']);
            }
            
            /* add formatted data */
            $formattedData[$lang['tlang_id']][] = [
                'name' => $lang['tlang_name'], 'id' => $lang['tlang_id'], 'slug' => $lang['tlang_slug'],
                'order' => $order, 'child' => $lang['tlang_subcategories']
            ];
            $order++;
        }
        if (!empty($subArr)) {
            $languages = $subArr;
            goto recursion;
        }
        $arr = [];
        for ($i = 1; $i <= $order; $i++) {
            foreach ($formattedData as $key => $data) {
                if ($lastLevel == true && $data[$key]['child'] > 0) {
                    continue;
                }
                $current = current($data);
                if ($current['order'] == $i) {
                    $arr[$key] = $data;
                }
            }
        }
        return $arr;
    }
}
