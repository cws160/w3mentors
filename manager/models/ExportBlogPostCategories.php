<?php

class ExportBlogPostCategories extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::BLOG_CATEGORIES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'bpcategory_identifier' => Label::getLabel('LBL_CATEGORY_IDENTIFIER'),
            'bpcategory_name' => Label::getLabel('LBL_Category_Name'),
            'child_count' => Label::getLabel('LBL_Subcategories'),
            'bpcategory_active' => Label::getLabel('LBL_Status'),
        ];
        if ($this->filters['bpcategory_parent'] > 0) {
            unset($this->headers['child_count']);
        }
        return [
            'bpc.bpcategory_identifier', 'bpc_l.bpcategory_name',
            'COUNT(s.bpcategory_id) as child_count', 'bpc.bpcategory_active'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        $yesNoArray = AppConstant::getYesNoArr();
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data = [
                'bpcategory_identifier' => $row['bpcategory_identifier'],
                'bpcategory_name' => $row['bpcategory_name'],
                'child_count' => $row['child_count'],
                'bpcategory_active' => AppConstant::getActiveArr($row['bpcategory_active']),
            ];
            if ($this->filters['bpcategory_parent'] > 0) {
                unset($data['child_count']);
            }
            fputcsv($fh, $data);
            $count++;
        }
        return $count;
    }

}
