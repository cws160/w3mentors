<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QuizQuestionsService
{
    public const TYPE_AUTO_GRADED = 1;

    public const TYPE_NON_GRADED = 2;

    public const TYPE_SINGLE = 1;

    public const TYPE_MULTIPLE = 2;

    public const TYPE_TEXT = 3;

    public const STATUS_DRAFTED = 1;

    public const STATUS_PUBLISHED = 2;

    public const CATEGORY_TYPE_QUESTION = 2;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAttached(int $teacherId, int $quizId, int $langId): array
    {
        if (! $this->ownsQuiz($teacherId, $quizId)) {
            return [];
        }

        $rows = DB::table('tbl_quizzes_questions as quique')
            ->join('tbl_questions as ques', 'ques.ques_id', '=', 'quique.quique_ques_id')
            ->leftJoin('tbl_categories_lang as catelang', function ($join) use ($langId) {
                $join->on('catelang.catelang_cate_id', '=', 'ques.ques_cate_id')
                    ->where('catelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories_lang as subcatelang', function ($join) use ($langId) {
                $join->on('subcatelang.catelang_cate_id', '=', 'ques.ques_subcate_id')
                    ->where('subcatelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories as cate', 'cate.cate_id', '=', 'ques.ques_cate_id')
            ->where('quique.quique_quiz_id', $quizId)
            ->where('ques.ques_status', 1)
            ->whereNull('ques.ques_deleted')
            ->where('cate.cate_status', 1)
            ->whereNull('cate.cate_deleted')
            ->orderBy('quique.quique_order')
            ->get([
                'quique.quique_quiz_id',
                'quique.quique_ques_id',
                'quique.quique_order',
                'ques.ques_title',
                'ques.ques_type',
                'catelang.cate_name as category_name',
                'subcatelang.cate_name as subcategory_name',
            ]);

        return $rows->map(fn ($row) => [
            'question_id' => (int) $row->quique_ques_id,
            'quiz_id' => (int) $row->quique_quiz_id,
            'order' => (int) $row->quique_order,
            'title' => (string) $row->ques_title,
            'type' => (int) $row->ques_type,
            'type_label' => $this->questionTypeLabel((int) $row->ques_type),
            'category_name' => (string) ($row->category_name ?? '—'),
            'subcategory_name' => (string) ($row->subcategory_name ?? '—'),
        ])->all();
    }

    /**
     * @param  array{keyword?: string, category_id?: int, subcategory_id?: int, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function searchAvailable(int $teacherId, int $quizId, int $langId, array $filters): array
    {
        if (! $this->ownsQuiz($teacherId, $quizId)) {
            return ['items' => [], 'meta' => $this->emptyMeta($filters)];
        }

        $quizType = (int) DB::table('tbl_quizzes')->where('quiz_id', $quizId)->value('quiz_type');
        $attachedIds = DB::table('tbl_quizzes_questions')
            ->where('quique_quiz_id', $quizId)
            ->pluck('quique_ques_id')
            ->all();

        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 10)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $query = DB::table('tbl_questions as ques')
            ->leftJoin('tbl_categories_lang as catelang', function ($join) use ($langId) {
                $join->on('catelang.catelang_cate_id', '=', 'ques.ques_cate_id')
                    ->where('catelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories_lang as subcatelang', function ($join) use ($langId) {
                $join->on('subcatelang.catelang_cate_id', '=', 'ques.ques_subcate_id')
                    ->where('subcatelang.catelang_lang_id', '=', $langId);
            })
            ->where('ques.ques_user_id', $teacherId)
            ->where('ques.ques_status', 1)
            ->whereNull('ques.ques_deleted');

        if ($quizType === self::TYPE_NON_GRADED) {
            $query->where('ques.ques_type', self::TYPE_TEXT);
        } else {
            $query->where('ques.ques_type', '!=', self::TYPE_TEXT);
        }

        if ($attachedIds !== []) {
            $query->whereNotIn('ques.ques_id', $attachedIds);
        }

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

        $total = (clone $query)->count('ques.ques_id');
        $rows = $query
            ->orderByDesc('ques.ques_id')
            ->forPage($page, $perPage)
            ->get([
                'ques.ques_id',
                'ques.ques_title',
                'ques.ques_type',
                'catelang.cate_name as category_name',
                'subcatelang.cate_name as subcategory_name',
            ]);

        return [
            'items' => $rows->map(fn ($row) => [
                'id' => (int) $row->ques_id,
                'title' => (string) $row->ques_title,
                'type' => (int) $row->ques_type,
                'type_label' => $this->questionTypeLabel((int) $row->ques_type),
                'category_name' => (string) ($row->category_name ?? '—'),
                'subcategory_name' => (string) ($row->subcategory_name ?? '—'),
            ])->all(),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) max(1, ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * @param  array<int>  $questionIds
     */
    public function attach(int $teacherId, int $quizId, array $questionIds, ?string &$error = null): bool
    {
        if (! $this->ownsQuiz($teacherId, $quizId)) {
            $error = 'Quiz not found';

            return false;
        }

        $questionIds = array_values(array_unique(array_filter(array_map('intval', $questionIds))));
        if ($questionIds === []) {
            $error = 'Please select question(s)';

            return false;
        }

        $quizType = (int) DB::table('tbl_quizzes')->where('quiz_id', $quizId)->value('quiz_type');
        $validQuery = DB::table('tbl_questions')
            ->whereIn('ques_id', $questionIds)
            ->where('ques_user_id', $teacherId)
            ->where('ques_status', 1)
            ->whereNull('ques_deleted');

        if ($quizType === self::TYPE_NON_GRADED) {
            $validQuery->where('ques_type', self::TYPE_TEXT);
        } else {
            $validQuery->where('ques_type', '!=', self::TYPE_TEXT);
        }

        if ($validQuery->count() !== count($questionIds)) {
            $error = 'Invalid data sent';

            return false;
        }

        $attached = DB::table('tbl_quizzes_questions')
            ->where('quique_quiz_id', $quizId)
            ->pluck('quique_ques_id')
            ->all();

        $toAttach = array_values(array_diff($questionIds, $attached));
        if ($toAttach === []) {
            $error = 'Questions already attached';

            return false;
        }

        DB::transaction(function () use ($teacherId, $quizId, $toAttach) {
            foreach ($toAttach as $quesId) {
                DB::table('tbl_quizzes_questions')->insert([
                    'quique_quiz_id' => $quizId,
                    'quique_ques_id' => $quesId,
                    'quique_order' => 0,
                ]);
            }
            $this->renumberOrder($quizId);
            $this->syncQuizMeta($teacherId, $quizId);
        });

        return true;
    }

    public function remove(int $teacherId, int $quizId, int $questionId, ?string &$error = null): bool
    {
        if (! $this->ownsQuiz($teacherId, $quizId)) {
            $error = 'Quiz not found';

            return false;
        }

        $deleted = DB::table('tbl_quizzes_questions')
            ->where('quique_quiz_id', $quizId)
            ->where('quique_ques_id', $questionId)
            ->delete();

        if ($deleted < 1) {
            $error = 'Invalid request';

            return false;
        }

        DB::transaction(function () use ($teacherId, $quizId) {
            $this->renumberOrder($quizId);
            $this->syncQuizMeta($teacherId, $quizId);
        });

        return true;
    }

    /**
     * @param  array<int>  $order
     */
    public function updateOrder(int $teacherId, int $quizId, array $order, ?string &$error = null): bool
    {
        if (! $this->ownsQuiz($teacherId, $quizId)) {
            $error = 'Quiz not found';

            return false;
        }

        $order = array_values(array_filter(array_map('intval', $order)));
        if ($order === []) {
            $error = 'Invalid data sent';

            return false;
        }

        DB::transaction(function () use ($quizId, $order) {
            foreach ($order as $index => $quesId) {
                if ($quesId < 1) {
                    continue;
                }
                DB::table('tbl_quizzes_questions')
                    ->where('quique_quiz_id', $quizId)
                    ->where('quique_ques_id', $quesId)
                    ->update(['quique_order' => $index + 1]);
            }
        });

        return true;
    }

    private function ownsQuiz(int $teacherId, int $quizId): bool
    {
        return DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->where('quiz_user_id', $teacherId)
            ->whereNull('quiz_deleted')
            ->exists();
    }

    private function renumberOrder(int $quizId): void
    {
        $ids = DB::table('tbl_quizzes_questions')
            ->where('quique_quiz_id', $quizId)
            ->orderBy('quique_order')
            ->orderBy('quique_ques_id')
            ->pluck('quique_ques_id')
            ->all();

        foreach ($ids as $index => $quesId) {
            DB::table('tbl_quizzes_questions')
                ->where('quique_quiz_id', $quizId)
                ->where('quique_ques_id', $quesId)
                ->update(['quique_order' => $index + 1]);
        }
    }

    private function syncQuizMeta(int $teacherId, int $quizId): void
    {
        $quizType = (int) DB::table('tbl_quizzes')->where('quiz_id', $quizId)->value('quiz_type');

        $marksQuery = DB::table('tbl_quizzes_questions as quique')
            ->join('tbl_questions as ques', 'ques.ques_id', '=', 'quique.quique_ques_id')
            ->join('tbl_categories as cate', 'cate.cate_id', '=', 'ques.ques_cate_id')
            ->where('quique.quique_quiz_id', $quizId)
            ->where('ques.ques_status', 1)
            ->whereNull('ques.ques_deleted')
            ->where('cate.cate_status', 1)
            ->whereNull('cate.cate_deleted');

        if ($quizType === self::TYPE_NON_GRADED) {
            $marksQuery->where('ques.ques_type', self::TYPE_TEXT);
        } else {
            $marksQuery->where('ques.ques_type', '!=', self::TYPE_TEXT);
        }

        $marks = (float) ($marksQuery->sum('ques.ques_marks') ?? 0);
        $count = (int) (clone $marksQuery)->count('quique.quique_ques_id');

        $status = app(QuizFormService::class)->getCompletedStatus($teacherId, $quizId);
        $quizStatus = ($status['is_complete'] ?? false) ? self::STATUS_PUBLISHED : self::STATUS_DRAFTED;

        DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->update([
                'quiz_marks' => $marks,
                'quiz_questions' => $count,
                'quiz_status' => $quizStatus,
                'quiz_updated' => now()->format('Y-m-d H:i:s'),
            ]);
    }

    /**
     * @param  array{page?: int, per_page?: int}  $filters
     * @return array<string, int>
     */
    private function emptyMeta(array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 10)));

        return [
            'current_page' => 1,
            'per_page' => $perPage,
            'total' => 0,
            'last_page' => 1,
        ];
    }

    private function questionTypeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_SINGLE => 'Single choice',
            self::TYPE_MULTIPLE => 'Multiple choice',
            self::TYPE_TEXT => 'Text',
            default => 'N/A',
        };
    }
}
