<?php

class ExportPreferences extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::PREFERENCES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'prefer_identifier' => Label::getLabel('LBL_PREFERENCE_IDENTIFIER'),
            'prefer_title' => Label::getLabel('LBL_PREFERENCE_TITLE'),
        ];
        return ['prefer_identifier', 'prefer_title'];
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
