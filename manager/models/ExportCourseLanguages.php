<?php

class ExportCourseLanguages extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::COURSE_LANGUAGES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'clang_identifier' => Label::getLabel('LBL_COURSE_LANGUAGE_IDENTIFIER'),
            'clang_name' => Label::getLabel('LBL_COURSE_LANGUAGE_NAME'),
            'clang_active' => Label::getLabel('LBL_STATUS'),
        ];
        return ['clang_identifier', 'clang_name', 'clang_active'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'clang_identifier' => $row['clang_identifier'],
                'clang_name' => $row['clang_name'],
                'clang_active' => AppConstant::getActiveArr($row['clang_active']),
            ]);
            $count++;
        }
        return $count;
    }

}
