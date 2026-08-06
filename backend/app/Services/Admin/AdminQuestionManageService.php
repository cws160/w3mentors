<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminQuestionManageService
{
    private const TYPE_SINGLE = 1;

    private const TYPE_MULTIPLE = 2;

    private const TYPE_TEXT = 3;

    /** @return array<string, mixed> */
    public function show(int $questionId, int $langId = 1): array
    {
        $row = DB::table('tbl_questions as ques')
            ->join('tbl_categories as cate', 'cate.cate_id', '=', 'ques.ques_cate_id')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'ques.ques_user_id')
            ->leftJoin('tbl_categories_lang as catelang', function ($join) use ($langId) {
                $join->on('catelang.catelang_cate_id', '=', 'ques.ques_cate_id')
                    ->where('catelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories_lang as subcatelang', function ($join) use ($langId) {
                $join->on('subcatelang.catelang_cate_id', '=', 'ques.ques_subcate_id')
                    ->where('subcatelang.catelang_lang_id', '=', $langId);
            })
            ->where('ques.ques_id', $questionId)
            ->whereNull('ques.ques_deleted')
            ->whereNull('cate.cate_deleted')
            ->first([
                'ques.ques_id',
                'ques.ques_title',
                'ques.ques_detail',
                'ques.ques_type',
                'ques.ques_status',
                'ques.ques_marks',
                'ques.ques_hint',
                'ques.ques_answer',
                'ques.ques_created',
                DB::raw('IFNULL(catelang.cate_name, cate.cate_identifier) as category_name'),
                DB::raw('IFNULL(subcatelang.cate_name, "") as subcategory_name'),
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
            ]);

        if (! $row) {
            throw new \RuntimeException('Question not found', 404);
        }

        $type = (int) $row->ques_type;
        $options = [];
        $answers = [];

        if ($type !== self::TYPE_TEXT) {
            $options = DB::table('tbl_question_options')
                ->where('queopt_ques_id', $questionId)
                ->orderBy('queopt_order')
                ->get(['queopt_id', 'queopt_title'])
                ->mapWithKeys(fn ($option) => [
                    (int) $option->queopt_id => (string) $option->queopt_title,
                ])
                ->all();

            $answerIds = json_decode((string) ($row->ques_answer ?? ''), true);
            if (is_array($answerIds)) {
                foreach ($answerIds as $answerId) {
                    $id = (int) $answerId;
                    if (isset($options[$id])) {
                        $answers[] = $options[$id];
                    }
                }
            }
        }

        return [
            'id' => (int) $row->ques_id,
            'title' => (string) $row->ques_title,
            'detail' => (string) ($row->ques_detail ?? ''),
            'type' => $type,
            'type_label' => $this->typeLabel($type),
            'teacher_name' => ucwords(trim((string) $row->teacher_name)),
            'category_name' => (string) $row->category_name,
            'subcategory_name' => (string) $row->subcategory_name,
            'status' => (int) $row->ques_status,
            'status_label' => (int) $row->ques_status === 1 ? 'Active' : 'Inactive',
            'marks' => (int) ($row->ques_marks ?? 0),
            'hint' => (string) ($row->ques_hint ?? ''),
            'created_at' => (string) ($row->ques_created ?? ''),
            'options' => array_values($options),
            'answers' => $answers,
        ];
    }

    private function typeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_SINGLE => 'Single choice',
            self::TYPE_MULTIPLE => 'Multiple choice',
            self::TYPE_TEXT => 'Text',
            default => 'N/A',
        };
    }
}
