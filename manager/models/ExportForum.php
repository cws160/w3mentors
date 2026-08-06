<?php

class ExportForum extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::FORUM;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'fque_title' => Label::getLabel('LBL_Title'),
            'fque_user' => Label::getLabel('LBL_USER'),
            'fque_lang_id' => Label::getLabel('LBL_Language'),
            'fque_status' => Label::getLabel('LBL_STATUS'),
            'fque_added_on' => Label::getLabel('LBL_ADDED_ON')
        ];
        return [
            'fque_title', 'fque_status', 'fque_added_on', 'fque_lang_id',
            'CONCAT(user_first_name, " ", user_last_name) AS user_name',
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $languages = Language::getAllNames();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'fque_title' => $row['fque_title'],
                'fque_user' => $row['user_name'],
                'fque_lang_id' => $languages[$row['fque_lang_id']] ?? Label::getLabel('LBL_NA'),
                'fque_status' => ForumQuestion::getQuestionStatusArray($row['fque_status']),
                'fque_added_on' => MyDate::formatDate($row['fque_added_on'])
            ]);
            $count++;
        }
        return $count;
    }

}
