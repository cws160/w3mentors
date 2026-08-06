<?php

class ExportForumTags extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::FORUM_TAGS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'ftag_name' => Label::getLabel('LBL_Tag_Name'),
            'ftag_language_id' => Label::getLabel('LBL_LANGUAGE'),
            'ftag_active' => Label::getLabel('LBL_STATUS'),
        ];
        return ['ftag_name', 'ftag_language_id', 'ftag_active', 'ftag_deleted'];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $langs = Language::getAllNames();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'ftag_name' => $row['ftag_name'].' '. ($row['ftag_deleted']?
                   '(' . Label::getLabel('LBL_Deleted_Record') . ')':''),
                'ftag_language_id' => $langs[$row['ftag_language_id']] ?? 'NA',
                'ftag_active' => AppConstant::getActiveArr($row['ftag_active']),
            ]);
            $count++;
        }
        return $count;
    }

}
