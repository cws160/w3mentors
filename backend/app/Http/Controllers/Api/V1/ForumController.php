<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherListingResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ForumController extends Controller
{
    private const TYPE_ALL = 0;
    private const TYPE_ACTIVE = 1;
    private const TYPE_ANSWERED = 2;
    private const TYPE_POPULAR = 3;

    private const STATUS_PUBLISHED = 1;
    private const STATUS_RESOLVED = 2;
    private const STATUS_SPAMMED = 3;

    private const REACT_TYPE_QUESTION = 1;
    private const REACT_TYPE_COMMENT = 2;

    public function meta(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        $totalQuestions = DB::table('tbl_forum_questions')
            ->where('fque_deleted', 0)
            ->whereIn('fque_status', [self::STATUS_PUBLISHED, self::STATUS_RESOLVED, self::STATUS_SPAMMED])
            ->count();

        $totalComments = DB::table('tbl_forum_question_comments as c')
            ->join('tbl_forum_questions as q', 'q.fque_id', '=', 'c.fquecom_fque_id')
            ->count();

        $totalTutors = User::active()->verified()->teachers()->count();

        return response()->json([
            'total_questions' => $totalQuestions,
            'total_comments' => (int) $totalComments,
            'total_tutors' => $totalTutors,
            'popular_tags' => $this->popularTags($langId),
            'top_teachers' => $this->topTeachers(5),
            'recommended_posts' => $this->recommendedPosts(
                $langId,
                $request->integer('exclude_question_id', 0),
                $this->parseTagIds($request->string('tag_ids')->toString())
            ),
        ]);
    }

    public function questions(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);
        $searchType = $request->integer('search_type', self::TYPE_ALL);
        $tagId = $request->integer('tag_id', 0);
        $keyword = trim($request->string('keyword')->toString());
        $perPage = $request->integer('per_page', 12);

        $query = $this->baseQuestionQuery($langId, $searchType);

        if ($tagId > 0) {
            $query->whereExists(function ($sub) use ($langId, $tagId) {
                $sub->select(DB::raw(1))
                    ->from('tbl_forum_tags_to_question as ftq')
                    ->join('tbl_forum_tags as ft', 'ft.ftag_id', '=', 'ftq.ftagque_ftag_id')
                    ->whereColumn('ftq.ftagque_fque_id', 'q.fque_id')
                    ->where('ft.ftag_id', $tagId)
                    ->where('ft.ftag_active', 1)
                    ->where('ft.ftag_deleted', 0)
                    ->where('ft.ftag_language_id', $langId);
            });
            $keyword = '';
        } elseif ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('q.fque_title', 'like', "%{$keyword}%")
                    ->orWhere('q.fque_description', 'like', "%{$keyword}%");
            });
        }

        if ($searchType === self::TYPE_POPULAR) {
            $query->havingRaw('(COALESCE(fs.fstat_comments, 0) + COALESCE(fs.fstat_likes, 0)) > 0');
        }

        $query->orderByDesc('q.fque_id');
        if ($searchType === self::TYPE_POPULAR) {
            $query->reorder()
                ->orderByRaw('(COALESCE(fs.fstat_comments, 0) + COALESCE(fs.fstat_likes, 0)) DESC')
                ->orderByDesc('q.fque_id');
        } else {
            $query->orderByDesc('q.fque_updated_on');
        }

        $paginator = $query->paginate($perPage);
        $ids = collect($paginator->items())->pluck('id')->all();
        $tagsByQuestion = $this->tagsForQuestions($ids, $langId);

        $items = collect($paginator->items())->map(function ($row) use ($tagsByQuestion) {
            $item = $this->formatQuestion($row);
            $item['tags'] = $tagsByQuestion[$row->id] ?? [];

            return $item;
        });

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function show(Request $request, string $slug): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        $row = $this->baseQuestionQuery($langId, self::TYPE_ALL)
            ->where('q.fque_slug', $slug)
            ->whereIn('q.fque_status', [self::STATUS_PUBLISHED, self::STATUS_RESOLVED])
            ->first();

        if (!$row) {
            return response()->json(['message' => 'Question not found'], 404);
        }

        $question = $this->formatQuestion($row);
        $question['tags'] = $this->tagsForQuestions([$row->id], $langId)[$row->id] ?? [];

        $sort = $request->string('sort', 'latest')->toString();
        $commentsQuery = DB::table('tbl_forum_question_comments as c')
            ->join('tbl_users as u', 'u.user_id', '=', 'c.fquecom_user_id')
            ->leftJoin('tbl_forum_stats as fs', function ($join) {
                $join->on('fs.fstat_record_id', '=', 'c.fquecom_id')
                    ->where('fs.fstat_record_type', self::REACT_TYPE_COMMENT);
            })
            ->where('c.fquecom_fque_id', $row->id)
            ->where('c.fquecom_deleted', 0)
            ->whereNull('u.user_deleted')
            ->whereNotNull('u.user_verified')
            ->where('u.user_active', 1);

        if ($sort === 'most_liked') {
            $commentsQuery->orderByDesc('c.fquecom_accepted')
                ->orderByRaw('(COALESCE(fs.fstat_likes, 0) - COALESCE(fs.fstat_dislikes, 0)) DESC')
                ->orderByDesc('c.fquecom_id');
        } else {
            $commentsQuery->orderByDesc('c.fquecom_accepted')
                ->orderByDesc('c.fquecom_id');
        }

        $comments = $commentsQuery->get([
                'c.fquecom_id as id',
                'c.fquecom_comment as comment',
                'c.fquecom_accepted as is_accepted',
                'c.fquecom_added_on as added_on',
                'u.user_id',
                'u.user_first_name',
                'u.user_last_name',
                DB::raw('COALESCE(fs.fstat_likes, 0) as likes'),
                DB::raw('COALESCE(fs.fstat_dislikes, 0) as dislikes'),
            ])
            ->map(fn ($c) => [
                'id' => (int) $c->id,
                'comment' => $c->comment,
                'is_accepted' => (bool) $c->is_accepted,
                'added_on' => $c->added_on,
                'time_ago' => $this->timeAgo($c->added_on),
                'author' => [
                    'id' => (int) $c->user_id,
                    'full_name' => trim("{$c->user_first_name} {$c->user_last_name}"),
                    'first_name' => $c->user_first_name,
                ],
                'likes' => (int) $c->likes,
                'dislikes' => (int) $c->dislikes,
                'vote_score' => abs((int) $c->likes - (int) $c->dislikes),
                'vote_tone' => $this->voteTone((int) $c->likes, (int) $c->dislikes),
            ]);

        $question['author']['id'] = (int) $row->author_id;

        $tagIds = array_column($question['tags'], 'id');

        return response()->json([
            'data' => $question,
            'comments' => $comments,
            'sidebar' => [
                'popular_tags' => $this->popularTags($langId),
                'top_teachers' => $this->topTeachers(5),
                'recommended_posts' => $this->recommendedPosts($langId, $row->id, $tagIds),
            ],
        ]);
    }

    private function baseQuestionQuery(int $langId, int $searchType)
    {
        $query = DB::table('tbl_forum_questions as q')
            ->join('tbl_users as u', 'u.user_id', '=', 'q.fque_user_id')
            ->leftJoin('tbl_forum_stats as fs', function ($join) {
                $join->on('fs.fstat_record_id', '=', 'q.fque_id')
                    ->where('fs.fstat_record_type', self::REACT_TYPE_QUESTION);
            })
            ->where('q.fque_deleted', 0)
            ->where('q.fque_lang_id', $langId)
            ->whereNull('u.user_deleted')
            ->whereNotNull('u.user_verified')
            ->where('u.user_active', 1)
            ->select([
                'q.fque_id as id',
                'q.fque_title as title',
                'q.fque_slug as slug',
                'q.fque_description as description',
                'q.fque_status as status',
                'q.fque_comments_allowed as comments_allowed',
                'q.fque_updated_on as updated_on',
                'q.fque_user_id as author_id',
                'u.user_first_name',
                'u.user_last_name',
                DB::raw('COALESCE(fs.fstat_comments, 0) as comments'),
                DB::raw('COALESCE(fs.fstat_likes, 0) as likes'),
                DB::raw('COALESCE(fs.fstat_dislikes, 0) as dislikes'),
                DB::raw('COALESCE(fs.fstat_views, 0) as views'),
            ]);

        if ($searchType === self::TYPE_ACTIVE) {
            $query->where('q.fque_status', self::STATUS_PUBLISHED);
        } elseif ($searchType === self::TYPE_ANSWERED) {
            $query->where('q.fque_status', self::STATUS_RESOLVED);
        } elseif ($searchType === self::TYPE_POPULAR) {
            $query->whereIn('q.fque_status', [self::STATUS_PUBLISHED, self::STATUS_RESOLVED]);
        } else {
            $query->whereIn('q.fque_status', [self::STATUS_PUBLISHED, self::STATUS_RESOLVED]);
        }

        return $query;
    }

    private function formatQuestion(object $row): array
    {
        $likes = (int) $row->likes;
        $dislikes = (int) $row->dislikes;
        $voteScore = abs($likes - $dislikes);
        $voteTone = $voteScore > 0
            ? ($likes > $dislikes ? 'success' : ($likes < $dislikes ? 'danger' : ''))
            : '';

        return [
            'id' => (int) $row->id,
            'title' => $row->title,
            'slug' => $row->slug,
            'description' => $row->description,
            'status' => (int) $row->status,
            'comments_allowed' => (bool) $row->comments_allowed,
            'updated_on' => $row->updated_on,
            'time_ago' => $this->timeAgo($row->updated_on),
            'author' => [
                'id' => (int) $row->author_id,
                'full_name' => trim("{$row->user_first_name} {$row->user_last_name}"),
                'first_name' => $row->user_first_name,
            ],
            'comments' => (int) $row->comments,
            'likes' => $likes,
            'dislikes' => $dislikes,
            'views' => (int) $row->views,
            'vote_score' => $voteScore,
            'vote_tone' => $voteTone,
        ];
    }

    private function tagsForQuestions(array $questionIds, int $langId): array
    {
        if ($questionIds === []) {
            return [];
        }

        $rows = DB::table('tbl_forum_tags_to_question as ftq')
            ->join('tbl_forum_tags as ft', 'ft.ftag_id', '=', 'ftq.ftagque_ftag_id')
            ->whereIn('ftq.ftagque_fque_id', $questionIds)
            ->where('ft.ftag_active', 1)
            ->where('ft.ftag_deleted', 0)
            ->where('ft.ftag_language_id', $langId)
            ->get(['ftq.ftagque_fque_id as question_id', 'ft.ftag_id as id', 'ft.ftag_name as name']);

        $map = [];
        foreach ($rows as $row) {
            $map[$row->question_id][] = ['id' => (int) $row->id, 'name' => $row->name];
        }

        return $map;
    }

    private function popularTags(int $langId): array
    {
        $rows = DB::table('tbl_forum_tags as ft')
            ->join('tbl_forum_tags_to_question as ftq', 'ft.ftag_id', '=', 'ftq.ftagque_ftag_id')
            ->join('tbl_forum_questions as q', function ($join) use ($langId) {
                $join->on('q.fque_id', '=', 'ftq.ftagque_fque_id')
                    ->where('q.fque_deleted', 0)
                    ->where('q.fque_lang_id', $langId)
                    ->whereIn('q.fque_status', [self::STATUS_PUBLISHED, self::STATUS_RESOLVED]);
            })
            ->join('tbl_forum_stats as fs', function ($join) {
                $join->on('fs.fstat_record_id', '=', 'q.fque_id')
                    ->where('fs.fstat_record_type', self::REACT_TYPE_QUESTION);
            })
            ->where('ft.ftag_active', 1)
            ->where('ft.ftag_deleted', 0)
            ->where('ft.ftag_language_id', $langId)
            ->groupBy('ft.ftag_id', 'ft.ftag_name')
            ->orderByRaw('SUM(COALESCE(fs.fstat_comments, 0)) DESC')
            ->orderBy('ft.ftag_id')
            ->limit(10)
            ->get(['ft.ftag_id as id', 'ft.ftag_name as name']);

        return $rows->map(fn ($t) => ['id' => (int) $t->id, 'name' => $t->name])->values()->all();
    }

    private function topTeachers(int $limit): array
    {
        $teachers = User::active()
            ->verified()
            ->teachers()
            ->leftJoin('tbl_teacher_stats as ts', 'ts.testat_user_id', '=', 'tbl_users.user_id')
            ->where('ts.testat_ratings', '>', 0)
            ->orderByDesc('ts.testat_ratings')
            ->orderByDesc('ts.testat_reviewes')
            ->limit($limit)
            ->get([
                'tbl_users.*',
                'ts.testat_ratings',
                'ts.testat_reviewes',
            ]);

        return TeacherListingResource::collection($teachers)->resolve();
    }

    private function recommendedPosts(int $langId, int $excludeId, array $tagIds): array
    {
        if ($tagIds === []) {
            return [];
        }

        $query = DB::table('tbl_forum_questions as q')
            ->join('tbl_forum_tags_to_question as ftq', 'ftq.ftagque_fque_id', '=', 'q.fque_id')
            ->join('tbl_forum_tags as ft', 'ft.ftag_id', '=', 'ftq.ftagque_ftag_id')
            ->leftJoin('tbl_forum_stats as fs', function ($join) {
                $join->on('fs.fstat_record_id', '=', 'q.fque_id')
                    ->where('fs.fstat_record_type', self::REACT_TYPE_QUESTION);
            })
            ->where('q.fque_deleted', 0)
            ->where('q.fque_lang_id', $langId)
            ->whereIn('q.fque_status', [self::STATUS_PUBLISHED, self::STATUS_RESOLVED])
            ->whereIn('ft.ftag_id', $tagIds)
            ->where('ft.ftag_active', 1)
            ->where('ft.ftag_deleted', 0);

        if ($excludeId > 0) {
            $query->where('q.fque_id', '!=', $excludeId);
        }

        return $query
            ->groupBy('q.fque_id', 'q.fque_title', 'q.fque_slug', 'fs.fstat_comments', 'fs.fstat_likes', 'fs.fstat_views')
            ->orderByRaw('(COALESCE(fs.fstat_comments, 0) + COALESCE(fs.fstat_likes, 0)) DESC')
            ->limit(5)
            ->get([
                'q.fque_id as id',
                'q.fque_title as title',
                'q.fque_slug as slug',
                DB::raw('COALESCE(fs.fstat_likes, 0) as likes'),
                DB::raw('COALESCE(fs.fstat_comments, 0) as comments'),
                DB::raw('COALESCE(fs.fstat_views, 0) as views'),
            ])
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'title' => $r->title,
                'slug' => $r->slug,
                'likes' => (int) $r->likes,
                'comments' => (int) $r->comments,
                'views' => (int) $r->views,
            ])
            ->values()
            ->all();
    }

    private function parseTagIds(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('intval', explode(',', $raw))));
    }

    private function voteTone(int $likes, int $dislikes): string
    {
        $score = abs($likes - $dislikes);
        if ($score < 1) {
            return '';
        }

        return $likes > $dislikes ? 'success' : ($likes < $dislikes ? 'danger' : '');
    }

    private function timeAgo(?string $datetime): string
    {
        if (!$datetime) {
            return '';
        }

        try {
            return Carbon::parse($datetime)->diffForHumans();
        } catch (\Throwable) {
            return '';
        }
    }
}
