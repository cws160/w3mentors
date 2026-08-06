<?php

class ExportStates extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::STATES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'state_identifier' => Label::getLabel('LBL_STATE_IDENTIFIER'),
            'state_code' => Label::getLabel('LBL_Code'),
            'state_name' => Label::getLabel('LBL_Name'),
            'state_country' => Label::getLabel('LBL_COUNTRY'),
            'state_active' => Label::getLabel('LBL_Status'),
        ];
        return [
            'stlang.state_name', 'st.state_code', 'c.country_identifier', 'st.state_active', 'st.state_identifier'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'state_identifier' => $row['state_identifier'],
                'state_code' => $row['state_code'],
                'state_name' => $row['state_name'],
                'state_country' => $row['country_identifier'],
                'state_active' => AppConstant::getActiveArr($row['state_active']),
            ]);
            $count++;
        }
        return $count;
    }
}
