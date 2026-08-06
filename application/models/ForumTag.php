<?php

/**
 * This class is used for Forum Tags
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class ForumTag extends MyAppModel
{

    private $userId;
    private $langId;

    public const DB_TBL = 'tbl_forum_tags';
    public const DB_TBL_PREFIX = 'ftag_';
    public const DB_TBL_SUBSCRIBED_TAGS = 'tbl_forum_subscribed_tags';
    public const DB_TBL_SUBSCRIBED_TAGS_PREFIX = 'fsubsctag_';
    public const DB_TBL_TAGS_TO_QUESTION = 'tbl_forum_tags_to_question';
    public const DB_TBL_TAGS_TO_QUESTION_PREFIX = 'ftagque_';
    public const DB_TBL_REQUESTS = 'tbl_forum_tag_requests';
    public const DB_TBL_REQUESTS_PREFIX = 'ftagreq_';
    public const REQUEST_PENDING = 0;
    public const REQUEST_APPROVED = 1;
    public const REQUEST_REJECTED = 2;
    public const TAGS_BINDING_LIMIT_WITH_QUE = 5;

    /**
     * Initialize ForumTag Class
     *
     * @param int $forumTagId
     */
    public function __construct(int $forumTagId = 0, int $userId = 0, int $langId = 0)
    {
        parent::__construct(static::DB_TBL, 'ftag_id', $forumTagId);
        $this->userId = $userId;
        $this->langId = $langId;
    }

    /**
     * Delete Tag
     */
    public function deleteTag()
    {
        if (1 > $this->mainTableRecordId) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST_ID');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, ['ftag_deleted' => AppConstant::YES],
                        ['smt' => 'ftag_id = ?', 'vals' => [$this->mainTableRecordId]])) {
            $this->error = $db->getError();
            return false;
        }
        if (1 > $db->rowsAffected()) {
            $this->error = Label::getLabel('ERR_Zero_Record_Deleted');
            return false;
        }
        return true;
    }

    /**
     * Restore Deleted Tag
     */
    public function restoreTag()
    {
        if (1 > $this->mainTableRecordId) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST_ID');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->updateFromArray(static::DB_TBL, ['ftag_deleted' => AppConstant::NO],
                        ['smt' => 'ftag_id = ?', 'vals' => [$this->mainTableRecordId]])) {
            $this->error = $db->getError();
            return false;
        }
        if (1 > $db->rowsAffected()) {
            $this->error = Label::getLabel('ERR_Zero_Record_Restored');
            return false;
        }
        return true;
    }

    /**
     * Unsubscribe user from a tag
     */
    public function unSubscribe()
    {
        if (1 > $this->mainTableRecordId || 1 > $this->userId) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->deleteRecords(static::DB_TBL_SUBSCRIBED_TAGS,
                        ['smt' => 'fsubsctag_ftag_id = ? AND fsubsctag_user_id = ?',
                            'vals' => [$this->mainTableRecordId, $this->userId]])) {
            $this->error = Label::getLabel('ERR_Something_Went_wrong_while_unsubscribing_tag');
            return false;
        }
        if (1 > $db->rowsAffected()) {
            $this->error = Label::getLabel('ERR_Something_Went_wrong_while_unsubscribing_tag');
            return false;
        }
        return true;
    }

    /**
     * Unsubscribe user from All Subscribed tags
     */
    public function unSubscribeAll()
    {
        if (1 > $this->userId) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        $delSmt = $db->prepareStatement('DELETE stags.* FROM ' . static::DB_TBL_SUBSCRIBED_TAGS . ' AS stags INNER JOIN ' . static::DB_TBL . ' AS ftags ON ftags.ftag_id = stags.fsubsctag_ftag_id WHERE ftags.ftag_language_id = ? AND stags.fsubsctag_user_id = ?');
        $delSmt->bindParameters('ii', $this->langId, $this->userId);
        if (!$delSmt->execute()) {
            $this->error = Label::getLabel('ERR_Something_Went_wrong_while_unsubscribing_tags');
            return false;
        }
        if (1 > $db->rowsAffected()) {
            $this->error = Label::getLabel('ERR_Something_Went_wrong_while_unsubscribing_tags');
            return false;
        }
        return true;
    }

    /**
     * Subscribe user for a tag
     */
    public function subscribe()
    {
        if (1 > $this->mainTableRecordId || 1 > $this->userId) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->insertFromArray(static::DB_TBL_SUBSCRIBED_TAGS,
                        ['fsubsctag_ftag_id' => $this->mainTableRecordId, 'fsubsctag_user_id' => $this->userId])) {
            $this->error = Label::getLabel('ERR_Something_Went_wrong_while_subscribing_tag_May_be_already_in_your_list');
            return false;
        }
        return true;
    }

    /**
     * Get list of tags
     */
    public function getList($keyword = '')
    {
        if (1 > $this->mainTableRecordId || 1 > $this->userId) {
            $this->error = Label::getLabel('ERR_INVALID_REQUEST');
            return false;
        }
        $db = FatApp::getDb();
        if (!$db->insertFromArray(static::DB_TBL_SUBSCRIBED_TAGS,
                        ['fsubsctag_ftag_id' => $this->mainTableRecordId, 'fsubsctag_user_id' => $this->userId])) {
            $this->error = Label::getLabel('ERR_Something_Went_wrong_while_subscribing_tag');
            return false;
        }
        return true;
    }

    /**
     * Get All Names
     * @param bool $assoc
     * @param int $active
     * @param int $deleted
     * @param int $limit
     * @param string $keyword
     * @param int $langId
     * @return array
     */
    public static function getAllTags(bool $assoc = true, int $active = 1, int $deleted = 0, int $limit = 0, string $keyword = '', int $langId = 0): array
    {
        $srch = new SearchBase(static::DB_TBL);
        if (!empty($keyword)) {
            $srch->addCondition('ftag_name', 'LIKE', '%' . $keyword . '%');
        }
        $srch->addMultipleFields(array(static::tblFld('id'), static::tblFld('name')));
        $srch->addOrder(static::tblFld('name'));
        $active = FatUtility::int($active);
        $deleted = FatUtility::int($deleted);
        if (1 === $active) {
            $srch->addCondition('ftag_active', '=', 'mysql_func_' . AppConstant::ACTIVE, 'AND', true);
        }
        if (0 === $deleted) {
            $srch->addCondition('ftag_deleted', '=', 'mysql_func_' . AppConstant::NO, 'AND', true);
        }
        if ($langId > 0) {
            $srch->addCondition(static::tblFld('language_id'), '=', 'mysql_func_' . FatUtility::int($langId), 'AND', true);
        }
        $srch->doNotCalculateRecords();
        $limit = FatUtility::int($limit);
        if (0 < $limit) {
            $srch->setPageSize($limit);
        } else {
            $srch->doNotLimitRecords();
        }
        if ($assoc) {
            return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        } else {
            return FatApp::getDb()->fetchAll($srch->getResultSet(), static::tblFld('id'));
        }
    }

    /**
     * Get Tag detail by name
     */
    public static function getTagByName(string $keyword, int $langId, int $active = 1, int $deleted = 0): array
    {
        $srch = new SearchBase(static::DB_TBL);
        $srch->addCondition('ftag_name', '=', $keyword);
        $srch->addCondition('ftag_language_id', '=', 'mysql_func_' . $langId, 'AND', true);
        if (1 === $active) {
            $srch->addCondition('ftag_active', '=', 'mysql_func_' . AppConstant::ACTIVE, 'AND', true);
        }
        if (0 === $deleted) {
            $srch->addCondition('ftag_deleted', '=', 'mysql_func_' . AppConstant::NO, 'AND', true);
        }
        $srch->addMultipleFields(array(static::tblFld('id'), static::tblFld('name')));
        $srch->doNotCalculateRecords();
        $srch->setPageSize(1);
        $row = FatApp::getDb()->fetch($srch->getResultSet());
        if (!is_array($row)) {
            return [];
        }
        return $row;
    }

    public static function sanitizeName($name)
    {
        //Clean up multiple dashes or whitespaces
        $name = preg_replace("/[\s-]+/", " ", $name);
        //Convert whitespaces and underscore to dash
        $name = preg_replace("/[\s_]/", "-", $name);
        return strtolower($name);
    }

    public static function allowedSpecialCharacters()
    {
        return '.+#-';
    }

    public static function getSubscribedUsersList(array $tagIds): array
    {
        if (1 > count($tagIds)) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL_SUBSCRIBED_TAGS, 'subsctags');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'ftag.ftag_id = subsctags.fsubsctag_ftag_id', 'ftag');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = subsctags.fsubsctag_user_id', 'user');
        $tagIds = array_map(function ($tagIdVals) {
            return 'mysql_func_' . $tagIdVals;
        }, $tagIds);
        $srch->addCondition('subsctags.fsubsctag_ftag_id', 'IN', $tagIds, 'AND', true);
        $srch->addCondition('ftag.ftag_active', '=', AppConstant::ACTIVE, 'AND', true);
        $srch->addCondition('ftag.ftag_deleted', '=', AppConstant::NO, 'AND', true);
        $srch->addDirectCondition('user.user_deleted IS NULL');
        $srch->addDirectCondition('user.user_verified IS NOT NULL');
        $srch->addCondition('user.user_active', '=', AppConstant::ACTIVE, 'AND', true);
        $srch->addGroupBy('subsctags.fsubsctag_user_id');
        $srch->addMultipleFields(['user_id', 'user_first_name', 'user_last_name', 'user_email', 'user_is_teacher', 'user_lang_id']);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'user_id');
    }

    public static function getSubscribedTagsList(int $userId, int $langId): array
    {
        if (1 > $userId || 1 > $langId) {
            return [];
        }
        $srch = new SearchBase(static::DB_TBL_SUBSCRIBED_TAGS, 'subsctags');
        $srch->joinTable(static::DB_TBL, 'INNER JOIN', 'ftag.ftag_id = subsctags.fsubsctag_ftag_id', 'ftag');
        $srch->joinTable(User::DB_TBL, 'INNER JOIN', 'user.user_id = subsctags.fsubsctag_user_id', 'user');
        $srch->addDirectCondition('user.user_deleted IS NULL');
        $srch->addDirectCondition('user.user_verified IS NOT NULL');
        $srch->addCondition('user.user_active', '=', 'mysql_func_' . AppConstant::ACTIVE, 'AND', true);

        $srch->addCondition('subsctags.fsubsctag_user_id', '=', 'mysql_func_' . $userId, 'AND', true);
        $srch->addCondition('ftag.ftag_language_id', '=', 'mysql_func_' . $langId, 'AND', true);
        $srch->addCondition('ftag.ftag_active', '=', AppConstant::ACTIVE, 'AND', true);
        $srch->addCondition('ftag.ftag_deleted', '=', AppConstant::NO, 'AND', true);
        $srch->addMultipleFields([
            'user_id',
            'ftag.ftag_id',
            'ftag.ftag_name',
        ]);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetchAll($srch->getResultSet(), 'ftag_id');
    }

}
