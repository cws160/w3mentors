<?php

class ExportCourses extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::COURSES;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'course_id' => Label::getLabel('LBL_ID'),
            'course_title' => Label::getLabel('LBL_TITLE'),
            'teacher_name' => Label::getLabel('LBL_TEACHER'),
            'course_cate_id' => Label::getLabel('LBL_CATEGORY'),
            'course_subcate_id' => Label::getLabel('LBL_SUBCATEGORY'),
            'coapre_updated' => Label::getLabel('LBL_PUBLISHED_ON'),
            'course_active' => Label::getLabel('LBL_STATUS'),
        ];
        return [
            'course.course_id', 'course_title', 'course_active',
            'coapre_updated', 'course_cate_id', 'course_subcate_id',
            'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) as teacher_name'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $cats = static::getCategories();
        fputcsv($fh, array_values($this->headers));
        while ($row = FatApp::getDb()->fetch($rs)) {
            fputcsv($fh, [
                'course_id' => $row['course_id'],
                'course_title' => $row['course_title'],
                'teacher_name' => $row['teacher_name'],
                'course_cate_id' => $cats[$row['course_cate_id']] ?? '',
                'course_subcate_id' => $cats[$row['course_subcate_id']] ?? '',
                'coapre_updated' => MyDate::formatDate($row['coapre_updated']),
                'course_active' => AppConstant::getActiveArr($row['course_active']),
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
