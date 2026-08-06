<?php

class ExportCourseRequests extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::COURSE_REQUESTS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'coapre_title' => Label::getLabel('LBL_COURSE_NAME'),
            'user_name' => Label::getLabel('LBL_TEACHER_NAME'),
            'coapre_status' => Label::getLabel('LBL_STATUS'),
            'coapre_created' => Label::getLabel('LBL_REQUESTED_ON'),
        ];
        return [
            'coapre_title', 'coapre_status', 'coapre_created',
            'CONCAT(user_first_name, " ", user_last_name) as teacher_name'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $cats = static::getCategories();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'coapre_title' => $row['coapre_title'],
                'teacher_name' => $row['teacher_name'],
                'coapre_status' => Course::getRequestStatuses($row['coapre_status']),
                'coapre_created' => MyDate::formatDate($row['coapre_created']),
            ]);
            $count++;
        }
        return $count;
    }

    public static function getCategories()
    {
        $srch = new SearchBased(Category::DB_TBL, 'cate');
        $srch->joinTable(Category::DB_LANG_TBL, 'LEFT JOIN', 'cate.cate_id = catelang.catelang_cate_id '
                . ' AND catelang.catelang_lang_id = ' . MyUtility::getSiteLangId(), 'catelang');
        $srch->addMultipleFields(['cate_id', 'IFNULL(cate_name, cate_identifier) AS cate_name']);
        $srch->addCondition('cate_type', '=', Category::TYPE_COURSE);
        $srch->addDirectCondition('cate_deleted IS NULL');
        $srch->addOrder('cate_order');
        $srch->doNotCalculateRecords();
        $srch->doNotLimitRecords();
        return FatApp::getDb()->fetchAllAssoc($srch->getResultSet());
    }

}
