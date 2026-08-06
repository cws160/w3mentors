<?php

class ExportQuizzes extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::QUIZZES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'quiz_title' => Label::getLabel('LBL_TITLE'),
            'quiz_type' => Label::getLabel('LBL_TYPE'),
            'quiz_teacher' => Label::getLabel('LBL_TEACHER'),
            'quiz_questions' => Label::getLabel('LBL_NO._OF_QUESTIONS'),
            'quiz_duration' => Label::getLabel('LBL_DURATION'),
            'quiz_attempts' => Label::getLabel('LBL_ATTEMPTS'),
            'quiz_passmark' => Label::getLabel('LBL_PASS_PERCENT'),
            'quiz_active' => Label::getLabel('LBL_ACTIVE'),
            'quiz_status' => Label::getLabel('LBL_STATUS'),
            'quiz_created' => Label::getLabel('LBL_ADDED_ON')
        ];
        return [
            'quiz_title', 'quiz_type', 'quiz_questions', 'quiz_duration', 'quiz_attempts', 'quiz_passmark', 'quiz_active', 'quiz_status', 'quiz_created',
            'user_first_name as teacher_first_name', 'user_last_name as teacher_last_name'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        $types = Quiz::getTypes();
        $status = Quiz::getStatuses();
        $active = AppConstant::getYesNoArr();
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'quiz_title' => CommonHelper::renderHtml($row['quiz_title']),
                'quiz_type' => $types[$row['quiz_type']],
                'quiz_teacher' => ucwords($row['teacher_first_name'] . ' ' . $row['teacher_last_name']),
                'quiz_questions' => $row['quiz_questions'],
                'quiz_duration' => ($row['quiz_duration']) ? CommonHelper::convertDuration($row['quiz_duration']) : '-',
                'quiz_attempts' => $row['quiz_attempts'],
                'quiz_passmark' => ($row['quiz_passmark']) ? MyUtility::formatPercent($row['quiz_passmark']) : '-',
                'quiz_active' => $active[$row['quiz_active']],
                'quiz_status' => $status[$row['quiz_status']],
                'quiz_created' => MyDate::formatDate($row['quiz_created']),
            ]);
            $count++;
        }
        return $count;
    }
}
