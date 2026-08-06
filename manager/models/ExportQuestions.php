<?php

class ExportQuestions extends Export
{

    public function __construct(int $langId, int $exportId = 0)
    {
        $this->type = static::QUESTIONS;
        parent::__construct($langId, $exportId);
    }

    public function getFields(): array
    {
        $this->headers = [
            'ques_title' => Label::getLabel('LBL_QUESTION_TITLE'),
            'ques_type' => Label::getLabel('LBL_TYPE'),
            'ques_cate_name' => Label::getLabel('LBL_CATEGORY'),
            'ques_subcate_name' => Label::getLabel('LBL_SUBCATEGORY'),
            'full_name' => Label::getLabel('LBL_TEACHER'),
            'ques_created' => Label::getLabel('LBL_ADDED_ON')
        ];
        return [
            'ques_title', 'ques_type',
            'ques_cate_id', 'ques_subcate_id', 'user_first_name as teacher_first_name', 'user_last_name as teacher_last_name',
            'ques_created'
        ];
    }

    public function writeData($fh, $rs): int
    {
        $count = 0;
        $types = Question::getTypes();
        fputcsv($fh, array_values($this->headers));
        $rows = FatApp::getDb()->fetchAll($rs);
        $categoryIds = [];
        array_map(function ($val) use (&$categoryIds) {
            $categoryIds = array_merge($categoryIds, [$val['ques_cate_id'], $val['ques_subcate_id']]);
        }, $rows);
        $categories = QuestionSearch::getCategoryNames($this->langId, array_unique($categoryIds));

        foreach ($rows as $row ) {
            fputcsv($fh, [
                'ques_title' => $row['ques_title'],
                'ques_type' => $types[$row['ques_type']],
                'ques_cate_name' => CommonHelper::renderHtml($categories[$row['ques_cate_id']] ?? ''),
                'ques_subcate_name' => CommonHelper::renderHtml($categories[$row['ques_subcate_id']] ?? ''),
                'full_name' => ucwords($row['teacher_first_name'] . ' ' . $row['teacher_last_name']),
                'ques_created' => MyDate::formatDate($row['ques_created']),
            ]);
            $count++;
        }
        return $count;
    }
}
