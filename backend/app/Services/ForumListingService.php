<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ForumListingService
{
    public const STATUS_DRAFT = 0;

    public const STATUS_PUBLISHED = 1;

    public const STATUS_RESOLVED = 2;

    public const STATUS_SPAMMED = 3;

    public const STAT_TYPE_QUESTION = 1;

    /**
     * @param  array{keyword?: string, status?: int, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function listMyQuestions(int $userId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $query = DB::table('tbl_forum_questions as fque')
            ->leftJoin('tbl_forum_stats as fstat', function ($join) {
                $join->on('fstat.fstat_record_id', '=', 'fque.fque_id')
                    ->where('fstat.fstat_record_type', '=', self::STAT_TYPE_QUESTION);
            })
            ->where('fque.fque_user_id', $userId)
            ->where('fque.fque_deleted', 0);

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where('fque.fque_title', 'like', '%'.$keyword.'%');
        }
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('fque.fque_status', (int) $filters['status']);
        }

        $total = (clone $query)->count('fque.fque_id');
        $rows = $query
            ->orderBy('fque.fque_status')
            ->orderByDesc('fque.fque_id')
            ->forPage($page, $perPage)
            ->get([
                'fque.fque_id',
                'fque.fque_title',
                'fque.fque_slug',
                'fque.fque_status',
                'fque.fque_comments_allowed',
                'fque.fque_added_on',
                'fstat.fstat_comments',
            ]);

        $offset = ($page - 1) * $perPage;
        $items = $rows->values()->map(function ($row, int $index) use ($offset) {
            $status = (int) $row->fque_status;
            $commentCount = (int) ($row->fstat_comments ?? 0);

            return [
                'id' => (int) $row->fque_id,
                'serial' => $offset + $index + 1,
                'title' => (string) $row->fque_title,
                'slug' => (string) ($row->fque_slug ?? ''),
                'status' => $status,
                'status_label' => $this->questionStatusLabel($status),
                'comments_allowed' => (int) ($row->fque_comments_allowed ?? 0),
                'comment_count' => $commentCount,
                'can_edit' => $status !== self::STATUS_RESOLVED,
                'can_view_comments' => $commentCount > 0 || (int) ($row->fque_comments_allowed ?? 0) === 1,
                'can_view_public' => ! in_array($status, [self::STATUS_SPAMMED, self::STATUS_DRAFT], true),
                'created_at' => $row->fque_added_on ? (string) $row->fque_added_on : null,
            ];
        })->all();

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

    private function questionStatusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'Drafted',
            self::STATUS_PUBLISHED => 'Published',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_SPAMMED => 'Spammed',
            default => 'N/A',
        };
    }
}
