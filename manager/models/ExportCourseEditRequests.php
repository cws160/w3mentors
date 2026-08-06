<?php

class ExportCourseEditRequests extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::COURSE_EDIT_REQUESTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'course_title' => Label::getLabel('LBL_COURSE_NAME'),
            'user_name' => Label::getLabel('LBL_TEACHER_NAME'),
            'coedre_status' => Label::getLabel('LBL_STATUS'),
            'coedre_created' => Label::getLabel('LBL_REQUESTED_ON'),
            'coedre_expired' => Label::getLabel('LBL_EXPIRED_ON'),
        ];
        return [
            'crsdetails.course_title as course_title',
            'coedre_status',
            'coedre_created',
            'coedre_updated',
            'coedre_duration',
            'CONCAT(user_first_name, " ", user_last_name) as teacher_name'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $row['coedre_expired'] = ($row['coedre_status'] == Course::EDIT_REQUEST_APPROVED) ? date('Y-m-d H:i:s', strtotime($row['coedre_updated'] . ' + ' . $row['coedre_duration'] . ' days')) : '';
            fputcsv($fh, [
                'course_title' => $row['course_title'],
                'teacher_name' => $row['teacher_name'],
                'coedre_status' => Course::getEditRequestStatuses($row['coedre_status']),
                'coedre_created' => MyDate::formatDate($row['coedre_created']),
                'coedre_expired' => MyDate::formatDate($row['coedre_expired']),
            ]);
            $count++;
        }
        return $count;
    }
}
