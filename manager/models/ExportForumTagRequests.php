<?php

class ExportForumTagRequests extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::FORUM_TAGS_REQUESTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'ftagreq_username' => Label::getLabel('LBL_User'),
            'ftagreq_name' => Label::getLabel('LBL_TAG'),
            'ftagreq_language_id' => Label::getLabel('LBL_language'),
            'ftagreq_status' => Label::getLabel('LBL_status'),
        ];
        return [];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $langs = Language::getAllNames();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'ftagreq_username' => $row['user_first_name'] . ' ' . $row['user_last_name'],
                'ftagreq_name' => $row['ftagreq_name'],
                'ftagreq_language_id' => $langs[$row['ftagreq_language_id']] ?? 'NA',
                'ftagreq_status' => ForumTagRequest::getStatusArray($row['ftagreq_status']),
            ]);
            $count++;
        }
        return $count;
    }

}
