<?php

class ExportBlogContributions extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::BLOG_CONTRIBUTIONS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'author_name' => Label::getLabel('LBL_Author_Name'),
            'bcontributions_author_email' => Label::getLabel('LBL_Author_Email'),
            'bcontributions_author_phone' => Label::getLabel('LBL_Author_Phone'),
            'bcontributions_status' => Label::getLabel('LBL_Status'),
            'bcontributions_added_on' => Label::getLabel('LBL_Posted_On'),
        ];
        return [
            'concat(bcontributions_author_first_name, " ", bcontributions_author_last_name) author_name',
            'bcontributions_author_email', 'bcontributions_author_phone',
            'bcontributions_status', 'bcontributions_added_on'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'author_name' => $row['author_name'],
                'bcontributions_author_email' => $row['bcontributions_author_email'],
                'bcontributions_author_phone' => $row['bcontributions_author_phone'],
                'bcontributions_status' => BlogPost::getContriStatuses($row['bcontributions_status']),
                'bcontributions_added_on' => MyDate::formatDate($row['bcontributions_added_on']),
            ]);
            $count++;
        }
        return $count;
    }

}
