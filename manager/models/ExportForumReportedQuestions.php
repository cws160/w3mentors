<?php

class ExportForumReportedQuestions extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::FORUM_REPORTED_QUESTIONS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'fquerep_title' => Label::getLabel('LBL_Report_Title'),
            'fque_title' => Label::getLabel('LBL_QUESTION'),
            'fque_user' => Label::getLabel('LBL_Reported_By'),
            'fquerep_status' => Label::getLabel('LBL_STATUS'),
            'fquerep_added_on' => Label::getLabel('LBL_ADDED_ON')
        ];
        return [
            'IFNULL(frireason_name, frireason_identifier) as fquerep_title',
            'fque_title', 'CONCAT(user_first_name, " ", user_last_name) AS user_name',
            'fquerep_status', 'fquerep_added_on'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'fquerep_title' => $row['fquerep_title'],
                'fque_title' => $row['fque_title'],
                'fque_user' => $row['user_name'],
                'fquerep_status' => ForumQuestion::getReportStatusArray($row['fquerep_status']),
                'fquerep_added_on' => MyDate::formatDate($row['fquerep_added_on'])
            ]);
            $count++;
        }
        return $count;
    }

}
