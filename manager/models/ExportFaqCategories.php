<?php

class ExportFaqCategories extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::FAQ_CATEGORIES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'faqcat_identifier' => Label::getLabel('LBL_CATEGORY_IDENTIFIER'),
            'faqcat_name' => Label::getLabel('LBL_Category_name'),
            'faqcat_active' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'faqcat_identifier', 'faqcat_name', 'faqcat_active'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'faqcat_identifier' => $row['faqcat_identifier'],
                'faqcat_name' => $row['faqcat_name'],
                'faqcat_active' => AppConstant::getActiveArr($row['faqcat_active']),
            ]);
            $count++;
        }
        return $count;
    }
}
