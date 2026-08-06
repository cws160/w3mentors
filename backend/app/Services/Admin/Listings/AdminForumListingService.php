<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminForumListingService
{
    use AdminListingSupport;

    public const QUEST_DRAFT = 0;

    public const QUEST_PUBLISHED = 1;

    public const QUEST_RESOLVED = 2;

    public const QUEST_SPAMMED = 3;

    public const REPORT_PENDING = 0;

    public const REPORT_ACCEPTED = 1;

    public const REPORT_CANCELLED = 2;

    public const TAG_REQ_PENDING = 0;

    public const TAG_REQ_APPROVED = 1;

    public const TAG_REQ_REJECTED = 2;

    public const REACT_TYPE_QUESTION = 1;

    public const REACT_TYPE_COMMENT = 2;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>}|null */
    public function search(string $module, Request $request): ?array
    {
        return match ($module) {
            'forum' => $this->questions($request),
            'forum-reported-questions' => $this->reportedQuestions($request),
            'forum-tags' => $this->tags($request),
            'forum-tag-requests' => $this->tagRequests($request),
            'forum-report-issue-reasons' => $this->reportReasons($request),
            default => null,
        };
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function questions(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_forum_questions as fq')
            ->join('tbl_users as u', 'u.user_id', '=', 'fq.fque_user_id')
            ->leftJoin('tbl_forum_stats as fs', function ($join) {
                $join->on('fs.fstat_record_id', '=', 'fq.fque_id')
                    ->where('fs.fstat_record_type', '=', self::REACT_TYPE_QUESTION);
            })
            ->where('fq.fque_deleted', 0)
            ->select([
                'fq.fque_id as id',
                'fq.fque_id as fque_id',
                'fq.fque_title as title',
                DB::raw('TRIM(CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))) as user_name'),
                'fq.fque_lang_id as lang_id',
                'fq.fque_status as status',
                'fq.fque_comments_allowed as comments_allowed',
                DB::raw('COALESCE(fs.fstat_comments, 0) as comment_count'),
                'fq.fque_added_on as added_on',
            ]);

        $this->applyKeyword($request, $query, ['fq.fque_title', 'u.user_email']);

        $status = $request->query('fque_status', $request->query('status'));
        if ($status !== null && $status !== '') {
            $query->where('fq.fque_status', (int) $status);
        }

        $filterLang = $request->integer('lang_id', 0);
        if ($filterLang > 0) {
            $query->where('fq.fque_lang_id', $filterLang);
        }

        $dateFrom = trim((string) $request->query('date_from', ''));
        if ($dateFrom !== '') {
            $query->where('fq.fque_added_on', '>=', $dateFrom.' 00:00:00');
        }

        $dateTill = trim((string) $request->query('date_till', ''));
        if ($dateTill !== '') {
            $query->where('fq.fque_added_on', '<=', $dateTill.' 23:59:59');
        }

        $query->orderBy('fq.fque_status')->orderByDesc('fq.fque_id');

        $languages = $this->languageMap();
        $rows = $query->get()->map(function ($row) use ($languages) {
            $status = (int) $row->status;

            return [
                'id' => (int) $row->id,
                'fque_id' => (int) $row->fque_id,
                'title' => (string) $row->title,
                'user_name' => trim((string) $row->user_name),
                'language_label' => $languages[(int) $row->lang_id] ?? '',
                'status' => $status,
                'status_label' => $this->questionStatusLabel($status),
                'comments_allowed' => (int) ($row->comments_allowed ?? 0),
                'comment_count' => (int) ($row->comment_count ?? 0),
                'added_on' => (string) $row->added_on,
            ];
        })->all();

        return $this->paginateRows($request, $rows);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function reportedQuestions(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_forum_question_reported as rep')
            ->join('tbl_forum_questions as fq', 'fq.fque_id', '=', 'rep.fquerep_fque_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'rep.fquerep_user_id')
            ->leftJoin('tbl_forum_report_issue_reasons as reason', 'reason.frireason_id', '=', 'rep.fquerep_frireason_id')
            ->leftJoin('tbl_forum_report_issue_reasons_lang as reasonlang', function ($join) use ($langId) {
                $join->on('reasonlang.frireasonlang_frireason_id', '=', 'reason.frireason_id')
                    ->where('reasonlang.frireasonlang_lang_id', '=', $langId);
            })
            ->select([
                'rep.fquerep_id as id',
                'rep.fquerep_id as fquerep_id',
                'rep.fquerep_fque_id as fque_id',
                DB::raw('IFNULL(reasonlang.frireason_name, reason.frireason_identifier) as report_title'),
                'fq.fque_title as question_title',
                DB::raw('TRIM(CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))) as reporter_name'),
                'rep.fquerep_status as status',
                'rep.fquerep_added_on as added_on',
            ]);

        $this->applyKeyword($request, $query, ['fq.fque_title']);

        $status = $request->query('status');
        if ($status !== null && $status !== '') {
            $query->where('rep.fquerep_status', (int) $status);
        }

        $query->orderByDesc('rep.fquerep_id')->orderBy('rep.fquerep_status');

        $rows = $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'fquerep_id' => (int) $row->fquerep_id,
            'fque_id' => (int) $row->fque_id,
            'report_title' => (string) $row->report_title,
            'question_title' => (string) $row->question_title,
            'reporter_name' => trim((string) $row->reporter_name),
            'status' => (int) $row->status,
            'status_label' => $this->reportStatusLabel((int) $row->status),
            'added_on' => (string) $row->added_on,
        ])->all();

        return $this->paginateRows($request, $rows);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function tags(Request $request): array
    {
        $query = DB::table('tbl_forum_tags as tag')
            ->select([
                'tag.ftag_id as id',
                'tag.ftag_id as ftag_id',
                'tag.ftag_name as title',
                'tag.ftag_language_id as lang_id',
                'tag.ftag_active as active',
                'tag.ftag_deleted as deleted',
            ]);

        $this->applyKeyword($request, $query, ['tag.ftag_name']);

        $active = $request->query('ftag_active', $request->query('active'));
        if ($active !== null && $active !== '') {
            $query->where('tag.ftag_active', (int) $active);
        }

        $filterLang = $request->integer('lang_id', 0);
        if ($filterLang > 0) {
            $query->where('tag.ftag_language_id', $filterLang);
        }

        $query->orderByDesc('tag.ftag_active')->orderBy('tag.ftag_name');

        $languages = $this->languageMap();
        $rows = $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'ftag_id' => (int) $row->ftag_id,
            'title' => (string) $row->title,
            'language_label' => $languages[(int) $row->lang_id] ?? '',
            'active' => (int) ($row->active ?? 0),
            'deleted' => (int) ($row->deleted ?? 0),
        ])->all();

        return $this->paginateRows($request, $rows);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function tagRequests(Request $request): array
    {
        $query = DB::table('tbl_forum_tag_requests as req')
            ->join('tbl_users as u', 'u.user_id', '=', 'req.ftagreq_user_id')
            ->select([
                'req.ftagreq_id as id',
                'req.ftagreq_id as ftagreq_id',
                DB::raw('TRIM(CONCAT(u.user_first_name, " ", COALESCE(u.user_last_name, ""))) as user_name'),
                'req.ftagreq_name as tag_name',
                'req.ftagreq_language_id as lang_id',
                'req.ftagreq_status as status',
            ]);

        $this->applyKeyword($request, $query, ['req.ftagreq_name']);

        $filterLang = $request->integer('lang_id', 0);
        if ($filterLang > 0) {
            $query->where('req.ftagreq_language_id', $filterLang);
        }

        $status = $request->query('req_status', $request->query('status'));
        if ($status !== null && $status !== '') {
            $query->where('req.ftagreq_status', (int) $status);
        }

        $query->orderBy('req.ftagreq_status')->orderByDesc('req.ftagreq_id');

        $languages = $this->languageMap();
        $rows = $query->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'ftagreq_id' => (int) $row->ftagreq_id,
            'user_name' => trim((string) $row->user_name),
            'tag_name' => (string) $row->tag_name,
            'language_label' => $languages[(int) $row->lang_id] ?? '',
            'status' => (int) $row->status,
            'status_label' => $this->tagRequestStatusLabel((int) $row->status),
        ])->all();

        return $this->paginateRows($request, $rows);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function reportReasons(Request $request): array
    {
        $langId = $this->langId($request);
        $query = DB::table('tbl_forum_report_issue_reasons as reason')
            ->leftJoin('tbl_forum_report_issue_reasons_lang as reasonlang', function ($join) use ($langId) {
                $join->on('reasonlang.frireasonlang_frireason_id', '=', 'reason.frireason_id')
                    ->where('reasonlang.frireasonlang_lang_id', '=', $langId);
            })
            ->select([
                'reason.frireason_id as id',
                'reason.frireason_id as frireason_id',
                'reason.frireason_identifier as identifier',
                DB::raw('IFNULL(reasonlang.frireason_name, reason.frireason_identifier) as title'),
                'reason.frireason_active as active',
                'reason.frireason_order as sort_order',
            ]);

        $this->applyKeyword($request, $query, ['reason.frireason_identifier', 'reasonlang.frireason_name']);

        $rows = $query
            ->orderByDesc('reason.frireason_active')
            ->orderBy('reason.frireason_order')
            ->orderBy('reason.frireason_id')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'frireason_id' => (int) $row->frireason_id,
                'identifier' => (string) $row->identifier,
                'title' => (string) $row->title,
                'active' => (int) ($row->active ?? 0),
                'sort_order' => (int) ($row->sort_order ?? 0),
            ])
            ->all();

        return $this->allRowsResult($rows);
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function paginateRows(Request $request, array $rows): array
    {
        $total = count($rows);
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $offset = ($page - 1) * $perPage;

        return [
            'data' => array_slice($rows, $offset, $perPage),
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ];
    }

    /** @param  array<int, array<string, mixed>>  $rows */
    private function allRowsResult(array $rows): array
    {
        $total = count($rows);

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => $total > 0 ? $total : 1,
                'total' => $total,
                'last_page' => 1,
            ],
        ];
    }

    /** @return array<int, string> */
    private function languageMap(): array
    {
        return DB::table('tbl_languages')
            ->pluck('language_name', 'language_id')
            ->map(fn ($name) => (string) $name)
            ->all();
    }

    private function questionStatusLabel(int $status): string
    {
        return match ($status) {
            self::QUEST_PUBLISHED => 'Published',
            self::QUEST_RESOLVED => 'Resolved',
            self::QUEST_SPAMMED => 'Spammed',
            default => 'Drafted',
        };
    }

    private function reportStatusLabel(int $status): string
    {
        return match ($status) {
            self::REPORT_ACCEPTED => 'Accepted',
            self::REPORT_CANCELLED => 'Cancelled',
            default => 'Pending',
        };
    }

    private function tagRequestStatusLabel(int $status): string
    {
        return match ($status) {
            self::TAG_REQ_APPROVED => 'Approved',
            self::TAG_REQ_REJECTED => 'Rejected',
            default => 'Pending',
        };
    }
}
