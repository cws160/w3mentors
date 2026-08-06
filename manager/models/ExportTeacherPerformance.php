<?php

class ExportTeacherPerformance extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::TEACHER_PERFORMANCE;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $headers = [
            'teacher_name' => Label::getLabel('LBL_TEACHER'),
            'testat_lessons' => Label::getLabel('LBL_LESSONS'),
            'testat_classes' => Label::getLabel('LBL_CLASSES'),
            'testat_courses' => Label::getLabel('LBL_COURSES'),
            'testat_students' => Label::getLabel('LBL_STUDENTS'),
            'testat_reviewes' => Label::getLabel('LBL_REVIEWES'),
            'testat_ratings' => Label::getLabel('LBL_RATINGS')
        ];
        $fields = [
            'CONCAT(user_first_name, " ", user_last_name) as teacher_name', 'testat_lessons',
            'testat_classes', 'testat_courses', 'testat_students', 'testat_reviewes', 'testat_ratings',
        ];
        if (!Course::isEnabled()) {
            unset($headers['testat_courses']);
            unset($fields[array_search('testat_courses', $fields)]);
        }
        if (!GroupClass::isEnabled()) {
            unset($headers['testat_classes']);
            unset($fields[array_search('testat_classes', $fields)]);
        }
        $this->headers = $headers;
        return $fields;
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, $row);
            $count++;
        }
        return $count;
    }

}
