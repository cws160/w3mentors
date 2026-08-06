<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QuizListingService
{
    public const TYPE_AUTO_GRADED = 1;

    public const TYPE_NON_GRADED = 2;

    public const STATUS_DRAFTED = 1;

    public const STATUS_PUBLISHED = 2;

    public const ACTIVE = 1;

    public const INACTIVE = 0;

    /**
     * @param  array{keyword?: string, type?: int, status?: int, active?: int|string, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(int $teacherId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $query = DB::table('tbl_quizzes as quiz')
            ->where('quiz.quiz_user_id', $teacherId)
            ->whereNull('quiz.quiz_deleted');

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where('quiz.quiz_title', 'like', '%'.$keyword.'%');
        }
        if (! empty($filters['type'])) {
            $query->where('quiz.quiz_type', (int) $filters['type']);
        }
        if (! empty($filters['status'])) {
            $query->where('quiz.quiz_status', (int) $filters['status']);
        }
        if (isset($filters['active']) && $filters['active'] !== '' && $filters['active'] !== null) {
            $query->where('quiz.quiz_active', (int) $filters['active']);
        }

        $total = (clone $query)->count('quiz.quiz_id');
        $rows = $query
            ->orderByDesc('quiz.quiz_id')
            ->forPage($page, $perPage)
            ->get([
                'quiz.quiz_id',
                'quiz.quiz_title',
                'quiz.quiz_type',
                'quiz.quiz_questions',
                'quiz.quiz_duration',
                'quiz.quiz_attempts',
                'quiz.quiz_passmark',
                'quiz.quiz_status',
                'quiz.quiz_active',
                'quiz.quiz_created',
            ]);

        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->quiz_id,
            'title' => (string) $row->quiz_title,
            'type' => (int) $row->quiz_type,
            'type_label' => $this->typeLabel((int) $row->quiz_type),
            'question_count' => (int) ($row->quiz_questions ?? 0),
            'duration' => (int) ($row->quiz_duration ?? 0),
            'attempts' => (int) ($row->quiz_attempts ?? 0),
            'pass_percent' => $row->quiz_passmark !== null ? (float) $row->quiz_passmark : null,
            'status' => (int) $row->quiz_status,
            'status_label' => $this->statusLabel((int) $row->quiz_status),
            'active' => (int) $row->quiz_active,
            'is_active' => (int) $row->quiz_active === self::ACTIVE,
            'created_at' => $row->quiz_created ? (string) $row->quiz_created : null,
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

    public function toggleActive(int $teacherId, int $quizId, int $currentActive): bool
    {
        $row = DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->where('quiz_user_id', $teacherId)
            ->whereNull('quiz_deleted')
            ->first(['quiz_id']);

        if (! $row) {
            return false;
        }

        $next = $currentActive === self::ACTIVE ? self::INACTIVE : self::ACTIVE;

        return DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->update(['quiz_active' => $next]) > 0;
    }

    public function delete(int $teacherId, int $quizId): bool
    {
        return DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->where('quiz_user_id', $teacherId)
            ->whereNull('quiz_deleted')
            ->update(['quiz_deleted' => now()->format('Y-m-d H:i:s')]) > 0;
    }

    private function typeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_AUTO_GRADED => 'Auto graded',
            self::TYPE_NON_GRADED => 'Non graded',
            default => 'N/A',
        };
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_DRAFTED => 'Drafted',
            self::STATUS_PUBLISHED => 'Published',
            default => 'N/A',
        };
    }
}
