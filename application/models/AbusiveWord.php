<?php

/**
 * This class is used to filter abusive content from blog posts
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class AbusiveWord extends MyAppModel
{
    const DB_TBL = 'tbl_abusive_words';
    const DB_TBL_PREFIX = 'abusive_';

    /**
     * Initialize Abusive Class
     * 
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'abusive_id', $id);
    }

    /**
     * Validate Content
     * 
     * @param string $str Text to checked for abusive content
     */
    public static function validateContent(string $str)
    {
        if (!$str) {
            return true;
        }
        $strArr = preg_split("/[\s\p{P}_]+/u", strtolower($str));
        $strArr = array_filter($strArr);
        $strArr = array_unique($strArr);
        if (empty($strArr)) {
            return true;
        }

        $srch = new SearchBase(static::DB_TBL, 'abusive');
        $srch->addMultipleFields(['abusive_id', 'abusive_keyword']);
        $srch->addCondition('mysql_func_LOWER(abusive_keyword)', 'IN', $strArr, 'AND', true);
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();

        $abusiveArr = FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
        if(!empty($abusiveArr)){
            $err = Label::getLabel('LBL_Word_{abusiveword}_is/are_not_allowed_to_post');
            $err = str_replace("{abusiveword}", '"' . implode(", ", $abusiveArr) .'"' , $err);
            FatUtility::dieJsonError($err);
        }
        return true;
    }
}
