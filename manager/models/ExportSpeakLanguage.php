<?php

class ExportSpeakLanguage extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::SPEAK_LANGUAGE;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'slang_identifier' => Label::getLabel('LBL_SPEAK_LANGUAGE_IDENTIFIER'),
            'slang_name' => Label::getLabel('LBL_SPEAK_LANGUAGE_NAME'),
            'slang_active' => Label::getLabel('LBL_STATUS'),
        ];
        return ['slang_identifier', 'slang_name', 'slang_active'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'slang_identifier' => $row['slang_identifier'],
                'slang_name' => $row['slang_name'],
                'slang_active' => AppConstant::getActiveArr($row['slang_active']),
            ]);
            $count++;
        }
        return $count;
    }

}
