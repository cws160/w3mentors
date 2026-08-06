<?php

class ExportSpeakLanguageLevels extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::SPEAK_LANGUAGE_LEVELS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'slanglvl_identifier' => Label::getLabel('LBL_LANGUAGE_LEVEL_IDENTIFIER'),
            'slanglvl_name' => Label::getLabel('LBL_LANGUAGE_LEVEL_NAME'),
            'slanglvl_active' => Label::getLabel('LBL_STATUS'),
        ];
        return ['slanglvl_identifier', 'slanglvl_name', 'slanglvl_active'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'slanglvl_identifier' => $row['slanglvl_identifier'],
                'slanglvl_name' => $row['slanglvl_name'],
                'slanglvl_active' => AppConstant::getActiveArr($row['slanglvl_active']),
            ]);
            $count++;
        }
        return $count;
    }

}
