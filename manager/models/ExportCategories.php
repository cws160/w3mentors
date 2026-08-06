<?php

class ExportCategories extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::CATEGORIES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'cate_identifier' => Label::getLabel('LBL_IDENTIFIER'),
            'cate_name' => Label::getLabel('LBL_NAME')
        ];
        if ($this->filters['parent_id'] == 0) {
            $this->headers['cate_subcategories'] = Label::getLabel('LBL_SUB_CATEGORIES');
        }
        $this->headers['cate_records'] = Label::getLabel('LBL_RECORDS');
        if ($this->filters['parent_id'] == 0 && $this->filters['cate_type'] == Category::TYPE_COURSE) {
            $this->headers['cate_featured'] = Label::getLabel('LBL_FEATURED');
        }
        $this->headers['cate_updated'] = Label::getLabel('LBL_UPDATED');
        $this->headers['cate_status'] = Label::getLabel('LBL_STATUS');
        
        return [
            'cate_identifier', 'catg_l.cate_name', 'cate_subcategories',
            'cate_records', 'cate_featured', 'cate_updated', 'cate_status'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            $data = [
                'cate_identifier' => $row['cate_identifier'],
                'cate_name' => $row['cate_name'],
            ];
            if ($this->filters['parent_id'] == 0) {
                $data['cate_subcategories'] = $row['cate_subcategories'];
            }
            $data['cate_records'] = $row['cate_records'];
            if ($this->filters['parent_id'] == 0 && $this->filters['cate_type'] == Category::TYPE_COURSE) {
                $data['cate_featured'] = AppConstant::getYesNoArr($row['cate_featured']);
            }
            $data['cate_updated'] = MyDate::formatDate($row['cate_updated']);
            $data['cate_status'] = AppConstant::getActiveArr($row['cate_status']);
            fputcsv($fh, $data);
            $count++;
        }
        return $count;
    }
}