<?php

class ExportLessonStats extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::LESSON_STATS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'user_full_name' => Label::getLabel('LBL_USER_NAME'),
            'user_email' => Label::getLabel('LBL_USER_EMAIL'),
            'user_type' => Label::getLabel('LBL_USER_TYPE'),
            'rescheduledCount' => Label::getLabel('LBL_RESCHEDULED'),
            'cancelledCount' => Label::getLabel('LBL_CANCELLED')
        ];
        return [
            'CONCAT(user_first_name, " ", user_last_name) as user_full_name',
            'user_email', 'user_is_teacher',
            'SUM(IF(sesslog_changed_status = ' . Lesson::SCHEDULED . ' and sesslog_prev_status = ' . Lesson::SCHEDULED . ',1, 0)) as rescheduledCount',
            'SUM(IF(sesslog_changed_status = ' . Lesson::CANCELLED . ',1, 0)) as cancelledCount',
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $payins = PaymentMethod::getPayins();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'user_full_name' => $row['user_full_name'],
                'user_email' => $row['user_email'],
                'user_type' => ($row['user_is_teacher'] == 1) ? Label::getLabel('LBL_LEARNER_|_TEACHER') : Label::getLabel('LBL_LEARNER'),
                'rescheduledCount' => $row['rescheduledCount'],
                'cancelledCount' => $row['cancelledCount']
            ]);
            $count++;
        }
        return $count;
    }

}
