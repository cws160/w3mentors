<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ForumQuestionCommentsService
{
    /**
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function listForQuestion(int $userId, int $questionId, int $page = 1, int $perPage = 20): array
    {
        if ($questionId < 1) {
            throw new InvalidArgumentException('Invalid request.');
        }

        $question = DB::table('tbl_forum_questions')
            ->where('fque_id', $questionId)
            ->where('fque_user_id', $userId)
            ->where('fque_deleted', 0)
            ->first(['fque_id']);

        if (! $question) {
            throw new InvalidArgumentException('Invalid request.');
        }

        $perPage = max(1, min(50, $perPage));
        $page = max(1, $page);

        $query = DB::table('tbl_forum_question_comments as fqc')
            ->join('tbl_forum_questions as fque', 'fque.fque_id', '=', 'fqc.fquecom_fque_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'fqc.fquecom_user_id')
            ->leftJoin('tbl_forum_stats as fstat', function ($join) {
                $join->on('fstat.fstat_record_id', '=', 'fqc.fquecom_id')
                    ->where('fstat.fstat_record_type', '=', 2);
            })
            ->where('fqc.fquecom_fque_id', $questionId)
            ->where('fqc.fquecom_deleted', 0)
            ->where('fque.fque_deleted', 0);

        $total = (clone $query)->count('fqc.fquecom_id');
        $rows = $query
            ->orderByDesc('fqc.fquecom_id')
            ->forPage($page, $perPage)
            ->get([
                'fqc.fquecom_id',
                'fqc.fquecom_comment',
                'fqc.fquecom_accepted',
                'fqc.fquecom_added_on',
                'u.user_first_name',
                'u.user_last_name',
                DB::raw('COALESCE(fstat.fstat_likes, 0) as likes'),
                DB::raw('COALESCE(fstat.fstat_dislikes, 0) as dislikes'),
            ]);

        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->fquecom_id,
            'comment' => (string) $row->fquecom_comment,
            'accepted' => (int) ($row->fquecom_accepted ?? 0) === 1,
            'added_on' => $row->fquecom_added_on ? (string) $row->fquecom_added_on : null,
            'user_name' => trim((string) $row->user_first_name.' '.(string) $row->user_last_name),
            'likes' => (int) ($row->likes ?? 0),
            'dislikes' => (int) ($row->dislikes ?? 0),
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
}
