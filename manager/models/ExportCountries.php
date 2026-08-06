<?php

class ExportCountries extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::COUNTRIES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'country_identifier' => Label::getLabel('LBL_IDENTIFIER'),
            'country_name' => Label::getLabel('LBL_Name'),
            'country_code' => Label::getLabel('LBL_Code'),
            'country_dial_code' => Label::getLabel('LBL_DIAL_CODE'),
            'country_active' => Label::getLabel('LBL_Status'),
        ];
        return [
            'c.country_identifier', 'country_name',
            'country_code', 'country_dial_code', 'country_active'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'country_identifier' => $row['country_identifier'],
                'country_name' => $row['country_name'],
                'country_code' => $row['country_code'],
                'country_dial_code' => $row['country_dial_code'],
                'country_active' => AppConstant::getActiveArr($row['country_active']),
            ]);
            $count++;
        }
        return $count;
    }

}
