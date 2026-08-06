<?php

/**
 * This class is used to handle Lecture notes
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class LectureNote extends MyAppModel
{
    public const DB_TBL = 'tbl_lecture_notes';
    public const DB_TBL_PREFIX = 'lecnote_';

    /**
     * Initialize
     *
     * @param int $id
     */
    public function __construct(int $id = 0)
    {
        parent::__construct(static::DB_TBL, 'lecnote_id', $id);
    }

    /**
     * Add/Edit lecture notes
     *
     * @param array $post
     * @return bool
     */
    public function setup(array $post)
    {
        $db = FatApp::getDb();
        $data = [
            'lecnote_id' => $post['lecnote_id'],
            'lecnote_user_id' => $post['lecnote_user_id'],
            'lecnote_course_id' => $post['lecnote_course_id'],
            'lecnote_ordcrs_id' => $post['lecnote_ordcrs_id'],
            'lecnote_lecture_id' => $post['lecnote_lecture_id'],
            'lecnote_notes' => $post['lecnote_notes'],
        ];
        if ($post['lecnote_id'] < 1) {
            $data['lecnote_created'] = date('Y-m-d H:i:s');
        } else {
            $data['lecnote_updated'] = date('Y-m-d H:i:s');
        }
        if (!$db->insertFromArray(LectureNote::DB_TBL, $data, false, [], $data)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Get lecture data by id
     *
     * @return array|bool
     */
    public function getNotesById()
    {
        $srch = new SearchBase(static::DB_TBL, 'lecnote');
        $srch->joinTable(Lecture::DB_TBL, 'INNER JOIN', 'lecnote.lecnote_lecture_id = lec.lecture_id', 'lec');
        $srch->addCondition('lecnote_id', '=', $this->getMainTableRecordId());
        $srch->addCondition('lecnote_deleted', 'IS', 'mysql_func_NULL', 'AND', true);
        $srch->addMultipleFields(['lecnote_user_id', 'lecnote_notes', 'lecnote_lecture_id']);
        $srch->setPageSize(1);
        $srch->doNotCalculateRecords();
        return FatApp::getDb()->fetch($srch->getResultSet());
    }

    /**
     * Can View Categories
     *
     * @param bool $returnResult
     * @return type
     */
    public function canViewQuestions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_QUESTIONS, static::PRIVILEGE_READ, $returnResult);
    }

    /**
     * Can Edit Categories
     *
     * @param bool $returnResult
     * @return type
     */
    public function canEditQuestions(bool $returnResult = false)
    {
        return $this->checkPermission(static::SECTION_QUESTIONS, static::PRIVILEGE_WRITE, $returnResult);
    }
}
