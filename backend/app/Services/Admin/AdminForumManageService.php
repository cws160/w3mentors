<?php

namespace App\Services\Admin;

use App\Services\Admin\Listings\AdminForumListingService;
use Illuminate\Support\Facades\DB;

class AdminForumManageService
{
    // --- Report reasons ---

    public function reportReasonShow(int $id, int $langId): ?array
    {
        $row = DB::table('tbl_forum_report_issue_reasons as reason')
            ->leftJoin('tbl_forum_report_issue_reasons_lang as reasonlang', function ($join) use ($langId) {
                $join->on('reasonlang.frireasonlang_frireason_id', '=', 'reason.frireason_id')
                    ->where('reasonlang.frireasonlang_lang_id', '=', $langId);
            })
            ->where('reason.frireason_id', $id)
            ->first([
                'reason.frireason_id',
                'reason.frireason_identifier',
                'reason.frireason_active',
                DB::raw('IFNULL(reasonlang.frireason_name, reason.frireason_identifier) as frireason_name'),
            ]);

        if (! $row) {
            return null;
        }

        return [
            'frireason_id' => (int) $row->frireason_id,
            'frireason_identifier' => (string) $row->frireason_identifier,
            'frireason_name' => (string) $row->frireason_name,
            'frireason_active' => (int) ($row->frireason_active ?? 0),
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function reportReasonSave(int $id, array $payload, int $langId): int
    {
        $identifier = trim((string) ($payload['frireason_identifier'] ?? ''));
        $name = trim((string) ($payload['frireason_name'] ?? ''));
        $active = (int) ($payload['frireason_active'] ?? 1);

        if ($identifier === '') {
            throw new \InvalidArgumentException('Option identifier is required.');
        }

        $duplicate = DB::table('tbl_forum_report_issue_reasons')
            ->whereRaw('LOWER(frireason_identifier) = ?', [strtolower($identifier)])
            ->where('frireason_id', '!=', $id)
            ->exists();

        if ($duplicate) {
            throw new \InvalidArgumentException('Identifier is already in use.');
        }

        if ($id > 0) {
            DB::table('tbl_forum_report_issue_reasons')
                ->where('frireason_id', $id)
                ->update([
                    'frireason_identifier' => $identifier,
                    'frireason_active' => $active,
                ]);
        } else {
            $maxOrder = (int) DB::table('tbl_forum_report_issue_reasons')->max('frireason_order');
            $id = (int) DB::table('tbl_forum_report_issue_reasons')->insertGetId([
                'frireason_identifier' => $identifier,
                'frireason_active' => $active,
                'frireason_order' => $maxOrder + 1,
            ]);
        }

        if ($name !== '') {
            $this->saveReportReasonLanguageRow($id, $langId, $name);

            if (! empty($payload['update_langs_data'])) {
                foreach ($this->siteLanguages() as $language) {
                    if ($language['id'] === $langId) {
                        continue;
                    }
                    $this->saveReportReasonLanguageRow($id, $language['id'], $name);
                }
            }
        }

        return $id;
    }

    public function reportReasonChangeStatus(int $id, int $status): bool
    {
        return DB::table('tbl_forum_report_issue_reasons')
            ->where('frireason_id', $id)
            ->update(['frireason_active' => $status]) > 0;
    }

    private function saveReportReasonLanguageRow(int $id, int $langId, string $name): void
    {
        DB::table('tbl_forum_report_issue_reasons_lang')->updateOrInsert(
            ['frireasonlang_frireason_id' => $id, 'frireasonlang_lang_id' => $langId],
            [
                'frireasonlang_frireason_id' => $id,
                'frireasonlang_lang_id' => $langId,
                'frireason_name' => $name,
            ],
        );
    }

    /** @return list<array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    /** @param  array<int, int|string>  $ids */
    public function reportReasonUpdateOrder(array $ids): bool
    {
        if ($ids === []) {
            return false;
        }

        foreach (array_values($ids) as $order => $id) {
            $reasonId = (int) $id;
            if ($reasonId < 1) {
                continue;
            }
            DB::table('tbl_forum_report_issue_reasons')
                ->where('frireason_id', $reasonId)
                ->update(['frireason_order' => $order]);
        }

        return true;
    }

    // --- Forum tags ---

    public function tagShow(int $id): ?array
    {
        $row = DB::table('tbl_forum_tags')->where('ftag_id', $id)->first();

        return $row ? [
            'ftag_id' => (int) $row->ftag_id,
            'ftag_name' => (string) $row->ftag_name,
            'ftag_language_id' => (int) ($row->ftag_language_id ?? 0),
            'ftag_active' => (int) ($row->ftag_active ?? 0),
            'ftag_deleted' => (int) ($row->ftag_deleted ?? 0),
        ] : null;
    }

    /** @param  array<string, mixed>  $payload */
    public function tagSave(int $id, array $payload): int
    {
        $name = $this->sanitizeTagName((string) ($payload['ftag_name'] ?? ''));
        $languageId = (int) ($payload['ftag_language_id'] ?? 0);
        $active = (int) ($payload['ftag_active'] ?? 1);

        if ($name === '') {
            throw new \InvalidArgumentException('Tag name is required.');
        }

        if ($languageId < 1) {
            throw new \InvalidArgumentException('Language is required.');
        }

        $data = [
            'ftag_name' => $name,
            'ftag_language_id' => $languageId,
            'ftag_active' => $active,
        ];

        if ($id > 0) {
            DB::table('tbl_forum_tags')->where('ftag_id', $id)->update($data);

            return $id;
        }

        return (int) DB::table('tbl_forum_tags')->insertGetId(array_merge($data, [
            'ftag_deleted' => 0,
            'ftag_user_id' => 0,
        ]));
    }

    public function tagChangeStatus(int $id, int $status): bool
    {
        return DB::table('tbl_forum_tags')->where('ftag_id', $id)->update(['ftag_active' => $status]) > 0;
    }

    public function tagDelete(int $id): bool
    {
        return DB::table('tbl_forum_tags')
            ->where('ftag_id', $id)
            ->update(['ftag_deleted' => 1]) > 0;
    }

    public function tagRestore(int $id): bool
    {
        return DB::table('tbl_forum_tags')
            ->where('ftag_id', $id)
            ->update(['ftag_deleted' => 0]) > 0;
    }

    // --- Tag requests ---

    public function tagRequestShow(int $id): ?array
    {
        $row = DB::table('tbl_forum_tag_requests as req')
            ->join('tbl_users as u', 'u.user_id', '=', 'req.ftagreq_user_id')
            ->where('req.ftagreq_id', $id)
            ->first([
                'req.ftagreq_id',
                'req.ftagreq_name',
                'req.ftagreq_language_id',
                'req.ftagreq_status',
                'req.ftagreq_user_id',
                DB::raw('TRIM(CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))) as user_name'),
            ]);

        return $row ? [
            'ftagreq_id' => (int) $row->ftagreq_id,
            'ftagreq_name' => (string) $row->ftagreq_name,
            'ftagreq_language_id' => (int) ($row->ftagreq_language_id ?? 0),
            'ftagreq_status' => (int) ($row->ftagreq_status ?? 0),
            'ftagreq_user_id' => (int) ($row->ftagreq_user_id ?? 0),
            'user_name' => trim((string) $row->user_name),
        ] : null;
    }

    public function tagRequestChangeStatus(int $id, int $status): void
    {
        $row = DB::table('tbl_forum_tag_requests')->where('ftagreq_id', $id)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Invalid request.');
        }

        if ((int) $row->ftagreq_status !== AdminForumListingService::TAG_REQ_PENDING) {
            throw new \InvalidArgumentException('Request has already been handled.');
        }

        if (! in_array($status, [AdminForumListingService::TAG_REQ_APPROVED, AdminForumListingService::TAG_REQ_REJECTED], true)) {
            throw new \InvalidArgumentException('Invalid status.');
        }

        $tagName = $this->sanitizeTagName((string) $row->ftagreq_name);
        $languageId = (int) $row->ftagreq_language_id;

        if ($status === AdminForumListingService::TAG_REQ_APPROVED) {
            $existing = DB::table('tbl_forum_tags')
                ->where('ftag_name', $tagName)
                ->where('ftag_language_id', $languageId)
                ->where('ftag_deleted', 0)
                ->exists();

            if ($existing) {
                throw new \InvalidArgumentException('Tag already available.');
            }
        }

        DB::transaction(function () use ($row, $id, $status, $tagName, $languageId) {
            DB::table('tbl_forum_tag_requests')
                ->where('ftagreq_id', $id)
                ->update(['ftagreq_status' => $status]);

            if ($status === AdminForumListingService::TAG_REQ_APPROVED) {
                DB::table('tbl_forum_tags')->insert([
                    'ftag_name' => $tagName,
                    'ftag_language_id' => $languageId,
                    'ftag_user_id' => (int) $row->ftagreq_user_id,
                    'ftag_active' => 1,
                    'ftag_deleted' => 0,
                ]);
            }
        });
    }

    // --- Reported questions ---

    public function reportedQuestionShow(int $id, int $langId): ?array
    {
        $row = DB::table('tbl_forum_question_reported as rep')
            ->join('tbl_forum_questions as fq', 'fq.fque_id', '=', 'rep.fquerep_fque_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'rep.fquerep_user_id')
            ->leftJoin('tbl_forum_report_issue_reasons as reason', 'reason.frireason_id', '=', 'rep.fquerep_frireason_id')
            ->leftJoin('tbl_forum_report_issue_reasons_lang as reasonlang', function ($join) use ($langId) {
                $join->on('reasonlang.frireasonlang_frireason_id', '=', 'reason.frireason_id')
                    ->where('reasonlang.frireasonlang_lang_id', '=', $langId);
            })
            ->where('rep.fquerep_id', $id)
            ->first([
                'rep.fquerep_id',
                'rep.fquerep_fque_id',
                'rep.fquerep_status',
                'rep.fquerep_comments',
                'rep.fquerep_admin_comments',
                'rep.fquerep_added_on',
                'rep.fquerep_updated_on',
                DB::raw('IFNULL(reasonlang.frireason_name, reason.frireason_identifier) as report_title'),
                'fq.fque_title as question_title',
                DB::raw('TRIM(CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))) as reporter_name'),
            ]);

        if (! $row) {
            return null;
        }

        $status = (int) $row->fquerep_status;

        return [
            'fquerep_id' => (int) $row->fquerep_id,
            'fquerep_fque_id' => (int) $row->fquerep_fque_id,
            'fquerep_status' => $status,
            'status_label' => match ($status) {
                AdminForumListingService::REPORT_ACCEPTED => 'Accepted',
                AdminForumListingService::REPORT_CANCELLED => 'Cancelled',
                default => 'Pending',
            },
            'fquerep_comments' => (string) ($row->fquerep_comments ?? ''),
            'fquerep_admin_comments' => (string) ($row->fquerep_admin_comments ?? ''),
            'fquerep_added_on' => (string) ($row->fquerep_added_on ?? ''),
            'fquerep_updated_on' => (string) ($row->fquerep_updated_on ?? ''),
            'report_title' => (string) $row->report_title,
            'question_title' => (string) $row->question_title,
            'reporter_name' => trim((string) $row->reporter_name),
        ];
    }

    /** @param  array<string, mixed>  $payload */
    public function reportedQuestionAction(int $id, array $payload): void
    {
        $row = DB::table('tbl_forum_question_reported')->where('fquerep_id', $id)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Invalid request.');
        }

        if ((int) $row->fquerep_status !== AdminForumListingService::REPORT_PENDING) {
            throw new \InvalidArgumentException('Request has already been handled.');
        }

        $status = (int) ($payload['fquerep_status'] ?? -1);
        $adminComments = trim((string) ($payload['fquerep_admin_comments'] ?? ''));

        if (! in_array($status, [AdminForumListingService::REPORT_ACCEPTED, AdminForumListingService::REPORT_CANCELLED], true)) {
            throw new \InvalidArgumentException('Invalid action.');
        }

        if (strlen($adminComments) < 10 || strlen($adminComments) > 2000) {
            throw new \InvalidArgumentException('Admin comment must be between 10 and 2000 characters.');
        }

        DB::transaction(function () use ($row, $id, $status, $adminComments) {
            DB::table('tbl_forum_question_reported')
                ->where('fquerep_id', $id)
                ->update([
                    'fquerep_status' => $status,
                    'fquerep_admin_comments' => $adminComments,
                    'fquerep_updated_on' => now()->format('Y-m-d H:i:s'),
                ]);

            if ($status === AdminForumListingService::REPORT_ACCEPTED) {
                DB::table('tbl_forum_questions')
                    ->where('fque_id', (int) $row->fquerep_fque_id)
                    ->update([
                        'fque_status' => AdminForumListingService::QUEST_SPAMMED,
                        'fque_comments_allowed' => 0,
                        'fque_updated_on' => now()->format('Y-m-d H:i:s'),
                    ]);
            }
        });
    }

    // --- Forum questions ---

    public function questionShow(int $id, int $langId): ?array
    {
        $row = DB::table('tbl_forum_questions as fq')
            ->join('tbl_users as u', 'u.user_id', '=', 'fq.fque_user_id')
            ->where('fq.fque_id', $id)
            ->where('fq.fque_deleted', 0)
            ->first([
                'fq.fque_id',
                'fq.fque_title',
                'fq.fque_description',
                'fq.fque_status',
                'fq.fque_added_on',
                DB::raw('TRIM(CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))) as user_name'),
            ]);

        if (! $row) {
            return null;
        }

        $status = (int) $row->fque_status;
        $tags = DB::table('tbl_forum_tags_to_question as ttq')
            ->join('tbl_forum_tags as tag', 'tag.ftag_id', '=', 'ttq.ftagque_ftag_id')
            ->where('ttq.ftagque_fque_id', $id)
            ->pluck('tag.ftag_name')
            ->all();

        return [
            'fque_id' => (int) $row->fque_id,
            'fque_title' => (string) $row->fque_title,
            'fque_description' => (string) ($row->fque_description ?? ''),
            'fque_status' => $status,
            'status_label' => match ($status) {
                AdminForumListingService::QUEST_PUBLISHED => 'Published',
                AdminForumListingService::QUEST_RESOLVED => 'Resolved',
                AdminForumListingService::QUEST_SPAMMED => 'Spammed',
                default => 'Drafted',
            },
            'fque_added_on' => (string) ($row->fque_added_on ?? ''),
            'user_name' => trim((string) $row->user_name),
            'tags' => array_values(array_map('strval', $tags)),
        ];
    }

    public function questionDelete(int $id): bool
    {
        return DB::table('tbl_forum_questions')
            ->where('fque_id', $id)
            ->where('fque_deleted', 0)
            ->update([
                'fque_deleted' => 1,
                'fque_updated_on' => now()->format('Y-m-d H:i:s'),
            ]) > 0;
    }

    public function commentDelete(int $questionId, int $commentId): bool
    {
        return DB::table('tbl_forum_question_comments')
            ->where('fquecom_id', $commentId)
            ->where('fquecom_fque_id', $questionId)
            ->where('fquecom_deleted', 0)
            ->update([
                'fquecom_deleted' => 1,
            ]) > 0;
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function questionComments(int $questionId, int $page, int $perPage): array
    {
        $query = DB::table('tbl_forum_question_comments as com')
            ->join('tbl_forum_questions as fq', 'fq.fque_id', '=', 'com.fquecom_fque_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'com.fquecom_user_id')
            ->leftJoin('tbl_forum_stats as fs', function ($join) {
                $join->on('fs.fstat_record_id', '=', 'com.fquecom_id')
                    ->where('fs.fstat_record_type', '=', AdminForumListingService::REACT_TYPE_COMMENT);
            })
            ->where('com.fquecom_fque_id', $questionId)
            ->where('com.fquecom_deleted', 0)
            ->select([
                'com.fquecom_id as id',
                'com.fquecom_comment as comment',
                'com.fquecom_accepted as accepted',
                DB::raw('COALESCE(fs.fstat_likes, 0) as likes'),
                DB::raw('COALESCE(fs.fstat_dislikes, 0) as dislikes'),
                'com.fquecom_added_on as added_on',
                'fq.fque_id as fque_id',
                'fq.fque_deleted as fque_deleted',
                DB::raw('TRIM(CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))) as user_name'),
            ])
            ->orderByDesc('com.fquecom_id');

        $total = (clone $query)->count();
        $rows = $query
            ->offset(max(0, ($page - 1) * $perPage))
            ->limit($perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'comment' => (string) $row->comment,
                'accepted' => (int) ($row->accepted ?? 0),
                'likes' => (int) ($row->likes ?? 0),
                'dislikes' => (int) ($row->dislikes ?? 0),
                'added_on' => (string) ($row->added_on ?? ''),
                'fque_id' => (int) ($row->fque_id ?? 0),
                'fque_deleted' => (int) ($row->fque_deleted ?? 0),
                'user_name' => trim((string) $row->user_name),
            ])
            ->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ];
    }

    private function sanitizeTagName(string $name): string
    {
        $name = preg_replace('/[\s-]+/', ' ', trim($name)) ?? trim($name);
        $name = preg_replace('/[\s_]/', '-', $name) ?? $name;

        return strtolower($name);
    }
}
