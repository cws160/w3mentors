<?php

/**
 * This class is used to handle User Speak Language
 * 
 * @package W3Mentors
 * @author Fatbit Team
 */
class UserSpeakLanguage extends FatModel
{

    const DB_TBL = 'tbl_user_speak_languages';
    const DB_TBL_PREFIX = 'uslang_';

    private $userId = 0;

    /**
     * Initialize User Speak Language
     * 
     * @param int $userId
     */
    public function __construct(int $userId = 0)
    {
        $this->userId = $userId;
    }

    /**
     * Save Language
     * 
     * @param int $langId
     * @param int $proficiency
     * @return bool
     */
    public function saveLang(int $langId, int $proficiency): bool
    {
        if (empty($this->userId)) {
            $this->error = Label::getLabel('LBL_INVALID_REQUEST');
            return false;
        }
        $data = [
            'uslang_slang_id' => $langId,
            'uslang_user_id' => $this->userId,
            'uslang_proficiency' => $proficiency
        ];
        $record = new TableRecord(static::DB_TBL);
        $record->assignValues($data);
        if (!$record->addNew([], $data)) {
            $this->error = $record->getError();
            return false;
        }
        return true;
    }

    /**
     * Remove Speak Languages
     * 
     * @param array $langIds
     * @return bool
     */
    public function removeSpeakLang(array $langIds = []): bool
    {
        $db = FatApp::getDb();
        $query = 'DELETE  FROM ' . self::DB_TBL . ' WHERE  1 = 1';
        if (!empty($this->userId)) {
            $query .= ' and uslang_user_id = ' . $this->userId;
        }
        if (!empty($langIds)) {
            $langIds = implode(",", $langIds);
            $query .= ' and uslang_slang_id IN (' . $langIds . ')';
        }
        if (!$db->query($query)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

    /**
     * Remove Speak Languages Proficiency
     * 
     * @param array $langIds
     * @return bool
     */
    public function removeSpeakLangLevel(array $langIds = []): bool
    {
        $db = FatApp::getDb();
        $query = 'UPDATE ' . self::DB_TBL . ' SET `uslang_proficiency`= 0' . ' WHERE  1 = 1';
        if (!empty($this->userId)) {
            $query .= ' and uslang_user_id = ' . $this->userId;
        }
        if (!empty($langIds)) {
            $langIds = implode(",", $langIds);
            $query .= ' and uslang_proficiency IN (' . $langIds . ')';
        }
        if (!$db->query($query)) {
            $this->error = $db->getError();
            return false;
        }
        return true;
    }

}
