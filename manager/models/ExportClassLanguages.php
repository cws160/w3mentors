<?php

class ExportClassLanguages extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::CLASS_LANGUAGES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'language' => Label::getLabel('LBL_LANGUAGE'),
            'scheduled' => Label::getLabel('LBL_SCHEDULED'),
            'completed' => Label::getLabel('LBL_COMPLETED'),
            'cancelled' => Label::getLabel('LBL_CANCELLED'),
            'totalsold' => Label::getLabel('LBL_TOTAL_SOLD'),
        ];
        return [
            'IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as language',
            'COUNT(IF(ordcls_status = ' . OrderClass::SCHEDULED . ', 1, NULL)) AS scheduled',
            'COUNT(IF(ordcls_status = ' . OrderClass::COMPLETED . ', 1, NULL)) AS completed',
            'COUNT(IF(ordcls_status = ' . OrderClass::CANCELLED . ', 1, NULL)) AS cancelled',
            'COUNT(grpcls_tlang_id) AS totalsold', 'tlang_id'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $tlangs = TeachLanguage::getNames($this->langId);
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data = [
                'language' => $tlangs[$row['tlang_id']] ?? Label::getLabel('LBL_NA'),
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
