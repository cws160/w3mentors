<?php

class ExportUrlRewriting extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::URL_REWRITING;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'seourl_original' => Label::getLabel('LBL_Original'),
            'seourl_custom' => Label::getLabel('LBL_Custom'),
            'seourl_httpcode' => Label::getLabel('LBL_httpcode'),
            'seourl_lang_id' => Label::getLabel('LBL_Language'),
        ];
        return ['seourl_original', 'seourl_custom', 'seourl_httpcode', 'seourl_lang_id'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $langs = Language::getAllNames();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'seourl_original' => $row['seourl_original'],
                'seourl_custom' => $row['seourl_custom'],
                'seourl_httpcode' => $row['seourl_httpcode'],
                'seourl_lang_id' => $langs[$row['seourl_lang_id']] ?? 'NA',
            ]);
            $count++;
        }
        return $count;
    }

}
