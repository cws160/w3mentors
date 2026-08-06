<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QuestionListingService
{
    public const TYPE_SINGLE = 1;

    public const TYPE_MULTIPLE = 2;

    public const TYPE_TEXT = 3;

    public const ACTIVE = 1;

    public const INACTIVE = 0;

    public const CATEGORY_TYPE_QUESTION = 2;

    /**
     * @param  array{keyword?: string, category_id?: int, subcategory_id?: int, type?: int, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(int $teacherId, int $langId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $query = DB::table('tbl_questions as ques')
            ->leftJoin('tbl_categories as cate', 'cate.cate_id', '=', 'ques.ques_cate_id')
            ->leftJoin('tbl_categories as subcate', 'subcate.cate_id', '=', 'ques.ques_subcate_id')
            ->leftJoin('tbl_categories_lang as catelang', function ($join) use ($langId) {
                $join->on('catelang.catelang_cate_id', '=', 'ques.ques_cate_id')
                    ->where('catelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories_lang as subcatelang', function ($join) use ($langId) {
                $join->on('subcatelang.catelang_cate_id', '=', 'ques.ques_subcate_id')
                    ->where('subcatelang.catelang_lang_id', '=', $langId);
            })
            ->where('ques.ques_user_id', $teacherId)
            ->whereNull('ques.ques_deleted');

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where('ques.ques_title', 'like', '%'.$keyword.'%');
        }
        if (! empty($filters['category_id'])) {
            $query->where('ques.ques_cate_id', (int) $filters['category_id']);
        }
        if (! empty($filters['subcategory_id'])) {
            $query->where('ques.ques_subcate_id', (int) $filters['subcategory_id']);
        }
        if (! empty($filters['type'])) {
            $query->where('ques.ques_type', (int) $filters['type']);
        }

        $total = (clone $query)->count('ques.ques_id');
        $rows = $query
            ->orderByDesc('ques.ques_id')
            ->forPage($page, $perPage)
            ->get([
                'ques.ques_id',
                'ques.ques_title',
                'ques.ques_type',
                'ques.ques_status',
                'ques.ques_created',
                'catelang.cate_name as category_name',
                'subcatelang.cate_name as subcategory_name',
            ]);

        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->ques_id,
            'title' => (string) $row->ques_title,
            'type' => (int) $row->ques_type,
            'type_label' => $this->typeLabel((int) $row->ques_type),
            'category_name' => (string) ($row->category_name ?? ''),
            'subcategory_name' => (string) ($row->subcategory_name ?? ''),
            'status' => (int) $row->ques_status,
            'is_active' => (int) $row->ques_status === self::ACTIVE,
            'created_at' => $row->ques_created ? (string) $row->ques_created : null,
        ])->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    public function categories(int $langId, int $parentId = 0): array
    {
        return DB::table('tbl_categories as cate')
            ->leftJoin('tbl_categories_lang as catelang', function ($join) use ($langId) {
                $join->on('catelang.catelang_cate_id', '=', 'cate.cate_id')
                    ->where('catelang.catelang_lang_id', '=', $langId);
            })
            ->where('cate.cate_type', self::CATEGORY_TYPE_QUESTION)
            ->where('cate.cate_parent', $parentId)
            ->where('cate.cate_status', self::ACTIVE)
            ->whereNull('cate.cate_deleted')
            ->orderBy('cate.cate_order')
            ->get(['cate.cate_id as id', 'catelang.cate_name as name', 'cate.cate_identifier as identifier'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) ($row->name ?: $row->identifier),
            ])
            ->all();
    }

    public function toggleStatus(int $teacherId, int $questionId, int $currentStatus): bool
    {
        $row = DB::table('tbl_questions')
            ->where('ques_id', $questionId)
            ->where('ques_user_id', $teacherId)
            ->whereNull('ques_deleted')
            ->first(['ques_id']);

        if (! $row) {
            return false;
        }

        $next = $currentStatus === self::ACTIVE ? self::INACTIVE : self::ACTIVE;

        return DB::table('tbl_questions')
            ->where('ques_id', $questionId)
            ->update(['ques_status' => $next]) > 0;
    }

    public function delete(int $teacherId, int $questionId): ?string
    {
        $row = DB::table('tbl_questions')
            ->where('ques_id', $questionId)
            ->where('ques_user_id', $teacherId)
            ->whereNull('ques_deleted')
            ->first(['ques_id']);

        if (! $row) {
            return 'Question not found';
        }

        $attached = DB::table('tbl_quizzes_questions as qq')
            ->join('tbl_quizzes as quiz', 'quiz.quiz_id', '=', 'qq.quique_quiz_id')
            ->where('qq.quique_ques_id', $questionId)
            ->where('quiz.quiz_user_id', $teacherId)
            ->whereNull('quiz.quiz_deleted')
            ->exists();

        if ($attached) {
            return 'Questions attached with quizzes cannot be deleted';
        }

        DB::table('tbl_questions')
            ->where('ques_id', $questionId)
            ->update(['ques_deleted' => now()->format('Y-m-d H:i:s')]);

        return null;
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
