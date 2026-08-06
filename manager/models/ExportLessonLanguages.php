<?php

class ExportLessonLanguages extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::LESSON_LANGUAGES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'language' => Label::getLabel('LBL_LANGUAGE'),
            'unscheduled' => Label::getLabel('LBL_UNSCHEDULED'),
            'scheduled' => Label::getLabel('LBL_SCHEDULED'),
            'completed' => Label::getLabel('LBL_COMPLETED'),
            'cancelled' => Label::getLabel('LBL_CANCELLED'),
            'totalsold' => Label::getLabel('LBL_TOTAL_SOLD'),
        ];
        return [
            'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as language',
            'COUNT(IF(ordles_status = ' . Lesson::UNSCHEDULED . ', 1, NULL)) AS unscheduled',
            'COUNT(IF(ordles_status = ' . Lesson::SCHEDULED . ', 1, NULL)) AS scheduled',
            'COUNT(IF(ordles_status = ' . Lesson::COMPLETED . ', 1, NULL)) AS completed',
            'COUNT(IF(ordles_status = ' . Lesson::CANCELLED . ', 1, NULL)) AS cancelled',
            'COUNT(ordles_tlang_id) AS totalsold', 'ordles_tlang_id'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $techLanguges = TeachLanguage::getNames($this->langId);
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data = [
                'language' => $techLanguges[$row['ordles_tlang_id']] ?? Label::getLabel('LBL_NA'),
                'unscheduled' => $row['unscheduled'],
                'scheduled' => $row['scheduled'],
                'completed' => $row['completed'],
                'cancelled' => $row['cancelled'],
                'totalsold' => $row['totalsold'],
            ];
            fputcsv($fh, $data);
            $count++;
        }
        return $count;
    }

}
