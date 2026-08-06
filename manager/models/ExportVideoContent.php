<?php

class ExportVideoContent extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::VIDEO_CONTENT;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'biblecontent_title' => Label::getLabel('LBL_IDENTIFIER'),
            'biblecontentlang_biblecontent_title' => Label::getLabel('LBL_TITLE'),
            'biblecontent_url' => Label::getLabel('LBLVIDEO_LINK'),
            'biblecontent_active' => Label::getLabel('LBL_Status'),
        ];
        return [
            'biblecontentlang_biblecontent_title', 'biblecontent_title',
            'biblecontent_url', 'biblecontent_active'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'biblecontent_title' => $row['biblecontent_title'],
                'biblecontentlang_biblecontent_title' => $row['biblecontentlang_biblecontent_title'],
                'biblecontent_url' => $row['biblecontent_url'],
                'biblecontent_active' => AppConstant::getActiveArr($row['biblecontent_active']),
            ]);
            $count++;
        }
        return $count;
    }

}
