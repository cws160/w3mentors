<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class PlanListingService
{
    public const LEVEL_BEGINNER = 1;

    public const LEVEL_UPPER_BEGINNER = 2;

    public const LEVEL_INTERMEDIATE = 3;

    public const LEVEL_UPPER_INTERMEDIATE = 4;

    public const LEVEL_ADVANCED = 5;

    /**
     * @param  array{keyword?: string, level?: int, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function list(int $teacherId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $query = DB::table('tbl_plans')
            ->where('plan_teacher_id', $teacherId);

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('plan_title', 'like', '%'.$keyword.'%')
                    ->orWhere('plan_detail', 'like', '%'.$keyword.'%');
            });
        }

        if (! empty($filters['level'])) {
            $query->where('plan_level', (int) $filters['level']);
        }

        $total = (clone $query)->count('plan_id');
        $rows = $query
            ->orderByDesc('plan_id')
            ->forPage($page, $perPage)
            ->get(['plan_id', 'plan_title', 'plan_detail', 'plan_level']);

        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->plan_id,
            'title' => (string) $row->plan_title,
            'detail' => (string) ($row->plan_detail ?? ''),
            'level' => (int) $row->plan_level,
            'level_label' => $this->levelLabel((int) $row->plan_level),
        ])->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    public function levelLabel(int $level): string
    {
        return match ($level) {
            self::LEVEL_BEGINNER => 'Beginner',
            self::LEVEL_UPPER_BEGINNER => 'Upper beginner',
            self::LEVEL_INTERMEDIATE => 'Intermediate',
            self::LEVEL_UPPER_INTERMEDIATE => 'Upper intermediate',
            self::LEVEL_ADVANCED => 'Advanced',
            default => '—',
        };
    }
}
