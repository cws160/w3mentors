<?php

class ExportTeacherRequests extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::TEACHER_REQUESTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'tereq_reference' => Label::getLabel('LBL_REFERENCE_NUMBER'),
            'tereq_full_name' => Label::getLabel('LBL_NAME'),
            'user_email' => Label::getLabel('LBL_EMAIL'),
            'tereq_comments' => Label::getLabel('LBL_COMMENTS'),
            'tereq_date' => Label::getLabel('LBL_REQUESTED_ON'),
            'tereq_status' => Label::getLabel('LBL_STATUS'), 
        ];
        return [
            'CONCAT(tereq_first_name, " ", tereq_last_name) as tereq_full_name',
            'tereq_reference', 'user_email', 'tereq_comments', 'tereq_date', 'tereq_status'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'tereq_reference' => $row['tereq_reference'],
                'tereq_full_name' => $row['tereq_full_name'],
                'user_email' => $row['user_email'],
                'tereq_comments' => $row['tereq_comments'],
                'tereq_date' => MyDate::formatDate($row['tereq_date']),
                'tereq_status' => TeacherRequest::getStatuses($row['tereq_status']),
            ]);
            $count++;
        }
        return $count;
    }

}
