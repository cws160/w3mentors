<?php

class ExportHoursTaughtReport extends Export
{
    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::HOURS_TAUGHT_REPORT;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $headers = [
            'user_id' => Label::getLabel('LBL_USER_ID'),
            'user_name' => Label::getLabel('LBL_TEACHER_NAME'),
            'hts_lesson_duration' => Label::getLabel('LBL_TIME_TAUGHT_IN_LESSONS'),
            'hts_class_duration' => Label::getLabel('LBL_TIME_TAUGHT_IN_CLASSES'),
            'total_duration' => Label::getLabel('LBL_TOTAL_TIME_TAUGHT'),
        ];
        $fields = [
             'user_id','CONCAT(user.user_first_name, " ", user.user_last_name) as user_name',
            'SUM(hts.hts_lesson_duration) as hts_lesson_duration',
            'SUM(hts.hts_class_duration) as hts_class_duration',
            'SUM(IFNULL(hts.hts_lesson_duration,0) + IFNULL(hts.hts_class_duration,0)) AS total_duration',
        ];
        $this->headers = $headers;
        return $fields;
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $row['hts_lesson_duration'] = ($row['hts_lesson_duration'] > 0) ? CommonHelper::convertDuration($row['hts_lesson_duration']*60) : Label::getLabel('LBL_N/A');
            $row['hts_class_duration'] = ($row['hts_class_duration'] > 0) ? CommonHelper::convertDuration($row['hts_class_duration']*60) : Label::getLabel('LBL_N/A');
            $row['total_duration'] = ($row['total_duration'] > 0) ? CommonHelper::convertDuration($row['total_duration']*60) : Label::getLabel('LBL_N/A');
            fputcsv($fh, $row);
            $count++;
        }
        return $count;
    }
}