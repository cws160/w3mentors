<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ForumSubscribedTagsService
{
    public const ACTIVE = 1;

    public const INACTIVE = 0;

    public const NOT_DELETED = 0;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listSubscribed(int $userId, int $langId): array
    {
        $rows = DB::table('tbl_forum_subscribed_tags as fsubsctag')
            ->leftJoin('tbl_forum_tags as ftag', 'ftag.ftag_id', '=', 'fsubsctag.fsubsctag_ftag_id')
            ->where('fsubsctag.fsubsctag_user_id', $userId)
            ->where('ftag.ftag_language_id', $langId)
            ->orderBy('ftag.ftag_name')
            ->get([
                'ftag.ftag_id',
                'ftag.ftag_name',
                'ftag.ftag_deleted',
                'ftag.ftag_active',
            ]);

        return $rows->map(function ($row) {
            $deleted = (int) ($row->ftag_deleted ?? 0);
            $active = (int) ($row->ftag_active ?? 0);

            return [
                'id' => (int) $row->ftag_id,
                'name' => (string) ($row->ftag_name ?? ''),
                'is_deleted' => $deleted === 1,
                'is_inactive' => $active === self::INACTIVE,
                'show_alert' => $deleted === 1 || $active === self::INACTIVE,
            ];
        })->all();
    }

    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function listSystemTags(int $langId, int $page, int $perPage = 50): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));

        $query = DB::table('tbl_forum_tags as ftag')
            ->where('ftag.ftag_language_id', $langId)
            ->where('ftag.ftag_active', self::ACTIVE)
            ->where('ftag.ftag_deleted', self::NOT_DELETED);

        $total = (clone $query)->count('ftag.ftag_id');
        $rows = $query
            ->orderBy('ftag.ftag_name')
            ->forPage($page, $perPage)
            ->get(['ftag.ftag_id', 'ftag.ftag_name']);

        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->ftag_id,
            'name' => (string) $row->ftag_name,
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
    public function suggestTags(string $keyword, int $langId, int $limit = 20): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);

        return DB::table('tbl_forum_tags')
            ->where('ftag_language_id', $langId)
            ->where('ftag_active', self::ACTIVE)
            ->where('ftag_deleted', self::NOT_DELETED)
            ->where('ftag_name', 'like', '%'.$escaped.'%')
            ->orderBy('ftag_name')
            ->limit($limit)
            ->get(['ftag_id', 'ftag_name'])
            ->map(fn ($row) => [
                'id' => (int) $row->ftag_id,
                'name' => (string) $row->ftag_name,
            ])
            ->all();
    }

    public function subscribe(int $userId, int $tagId): bool
    {
        if ($tagId < 1) {
            return false;
        }

        $exists = DB::table('tbl_forum_subscribed_tags')
            ->where('fsubsctag_ftag_id', $tagId)
            ->where('fsubsctag_user_id', $userId)
            ->exists();

        if ($exists) {
            return false;
        }

        return DB::table('tbl_forum_subscribed_tags')->insert([
            'fsubsctag_ftag_id' => $tagId,
            'fsubsctag_user_id' => $userId,
        ]);
    }

    public function unsubscribe(int $userId, int $tagId): bool
    {
        if ($tagId < 1) {
            return false;
        }

        return DB::table('tbl_forum_subscribed_tags')
            ->where('fsubsctag_ftag_id', $tagId)
            ->where('fsubsctag_user_id', $userId)
            ->delete() > 0;
    }

    public function unsubscribeAll(int $userId, int $langId): bool
    {
        $deleted = DB::delete(
            'DELETE stags FROM tbl_forum_subscribed_tags AS stags
             INNER JOIN tbl_forum_tags AS ftags ON ftags.ftag_id = stags.fsubsctag_ftag_id
             WHERE ftags.ftag_language_id = ? AND stags.fsubsctag_user_id = ?',
            [$langId, $userId]
        );

        return $deleted > 0;
    }
}
