<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\PaginatesJson;
use App\Http\Controllers\Controller;
use App\Services\GroupClassListingService;
use App\Services\IssueListingService;
use App\Services\PlanListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardPagesController extends Controller
{
    use PaginatesJson;

    public function __construct(
        private PlanListingService $planListing,
        private GroupClassListingService $groupClassListing,
        private IssueListingService $issueListing,
    ) {
    }

    private const ORDER_PAID = 1;
    private const GRPCLS_REGULAR = 1;
    private const GRPCLS_PACKAGE = 2;
    private const ISSUE_TYPE_LESSON = 1;
    private const ISSUE_TYPE_GCLASS = 2;

    public function notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $user->user_is_teacher ? 2 : 1;

        $query = DB::table('tbl_notifications')
            ->where('notifi_user_id', $user->user_id)
            ->where(function ($q) use ($userType) {
                $q->where('notifi_user_type', 0)->orWhere('notifi_user_type', $userType);
            })
            ->select([
                'notifi_id as id',
                'notifi_title as title',
                'notifi_desc as description',
                'notifi_type as type',
                'notifi_read as read_at',
                'notifi_added as created_at',
            ]);

        return $this->paginatedResponse($request, $query, 'notifi_id');
    }

    public function plans(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = $this->planListing->list((int) $user->user_id, [
            'keyword' => $request->input('keyword'),
            'level' => $request->input('level'),
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
        ]);
    }

    public function classes(Request $request): JsonResponse
    {
        return $this->groupSessionsListing($request, GroupClassListingService::TYPE_REGULAR);
    }

    public function packages(Request $request): JsonResponse
    {
        return $this->groupSessionsListing($request, GroupClassListingService::TYPE_PACKAGE);
    }

    private function groupSessionsListing(Request $request, int $type): JsonResponse
    {
        $user = $request->user();
        $statusInput = $request->input('status');
        $status = null;
        if ($statusInput === 'all') {
            $status = -1;
        } elseif ($statusInput !== null && $statusInput !== '') {
            $status = (int) $statusInput;
        }

        $result = $this->groupClassListing->list(
            (int) $user->user_id,
            (bool) $user->user_is_teacher,
            $type,
            [
                'keyword' => $request->input('keyword'),
                'status' => $status,
                'offline' => $request->input('offline'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'page' => $request->integer('page', 1),
                'per_page' => $request->integer('per_page', 20),
            ]
        );

        return response()->json([
            'data' => $result['items'],
            'groups' => $result['groups'],
            'meta' => $result['meta'],
        ]);
    }

    public function issues(Request $request): JsonResponse
    {
        $user = $request->user();
        $langId = (int) ($request->input('lang_id') ?: $user->user_lang_id ?: 1);
        $status = $request->input('status');
        $result = $this->issueListing->list(
            (int) $user->user_id,
            (bool) $user->user_is_teacher,
            $langId,
            [
                'keyword' => $request->input('keyword'),
                'class_type' => $request->input('class_type'),
                'status' => $status !== null && $status !== '' ? (int) $status : null,
                'page' => $request->integer('page', 1),
                'per_page' => $request->integer('per_page', 20),
            ]
        );

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
        ]);
    }

    public function subscriptions(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = max(1, $request->integer('page', 1));
        $perPage = min(50, max(1, $request->integer('per_page', 20)));

        $query = DB::table('tbl_order_subscriptions as ordsub')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordsub.ordsub_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordsub.ordsub_teacher_id')
            ->select([
                'ordsub.ordsub_id as id',
                'orders.order_id',
                'ordsub.ordsub_status as status',
                'ordsub.ordsub_startdate as starts_at',
                'ordsub.ordsub_enddate as ends_at',
                DB::raw('(SELECT COUNT(*) FROM tbl_order_lessons WHERE ordles_order_id = orders.order_id) as lesson_count'),
                DB::raw('CONCAT(learner.user_first_name, " ", COALESCE(learner.user_last_name, "")) as learner_name'),
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as teacher_name'),
                'learner.user_id as learner_id',
                'teacher.user_id as teacher_id',
            ]);

        if ($user->user_is_teacher) {
            $query->where('ordsub.ordsub_teacher_id', $user->user_id);
        } else {
            $query->where('orders.order_user_id', $user->user_id)
                ->where('orders.order_payment_status', self::ORDER_PAID);
        }

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword, $user) {
                if ($user->user_is_teacher) {
                    $q->whereRaw(
                        "CONCAT(learner.user_first_name, ' ', COALESCE(learner.user_last_name, '')) LIKE ?",
                        ['%'.$keyword.'%']
                    );
                } else {
                    $q->whereRaw(
                        "CONCAT(teacher.user_first_name, ' ', COALESCE(teacher.user_last_name, '')) LIKE ?",
                        ['%'.$keyword.'%']
                    );
                }
            });
        }

        $total = (clone $query)->count('ordsub.ordsub_id');
        $items = $query
            ->orderByDesc('ordsub.ordsub_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'order_id' => (int) $row->order_id,
                'status' => (int) $row->status,
                'starts_at' => $row->starts_at ? (string) $row->starts_at : null,
                'ends_at' => $row->ends_at ? (string) $row->ends_at : null,
                'lesson_count' => (int) ($row->lesson_count ?? 0),
                'learner_name' => (string) $row->learner_name,
                'teacher_name' => (string) $row->teacher_name,
                'counterparty_id' => $user->user_is_teacher ? (int) $row->learner_id : (int) $row->teacher_id,
                'counterparty_name' => $user->user_is_teacher
                    ? (string) $row->learner_name
                    : (string) $row->teacher_name,
            ])
            ->all();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ]);
    }

    public function subscriptionPlans(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->user_is_teacher) {
            $query = DB::table('tbl_order_subscription_plans as ordplan')
                ->join('tbl_order_lessons as ordles', 'ordles.ordles_ordsplan_id', '=', 'ordplan.ordsplan_id')
                ->join('tbl_subscription_plans as plan', 'plan.subplan_id', '=', 'ordplan.ordsplan_plan_id')
                ->join('tbl_users as learner', 'learner.user_id', '=', 'ordplan.ordsplan_user_id')
                ->where('ordles.ordles_teacher_id', $user->user_id)
                ->distinct()
                ->select([
                    'ordplan.ordsplan_id as id',
                    'plan.subplan_title as title',
                    'ordplan.ordsplan_status as status',
                    'ordplan.ordsplan_start_date as starts_at',
                    'ordplan.ordsplan_end_date as ends_at',
                    DB::raw('CONCAT(learner.user_first_name, " ", COALESCE(learner.user_last_name, "")) as learner_name'),
                ]);
        } else {
            $query = DB::table('tbl_order_subscription_plans as ordplan')
                ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordplan.ordsplan_order_id')
                ->join('tbl_subscription_plans as plan', 'plan.subplan_id', '=', 'ordplan.ordsplan_plan_id')
                ->where('ordplan.ordsplan_user_id', $user->user_id)
                ->where('orders.order_payment_status', self::ORDER_PAID)
                ->select([
                    'ordplan.ordsplan_id as id',
                    'plan.subplan_title as title',
                    'ordplan.ordsplan_status as status',
                    'ordplan.ordsplan_start_date as starts_at',
                    'ordplan.ordsplan_end_date as ends_at',
                ]);
        }

        return $this->paginatedResponse($request, $query, 'id');
    }

    public function chats(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->user_id;

        $query = DB::table('tbl_threads as thread')
            ->join('tbl_thread_users as thusr', function ($join) use ($userId) {
                $join->on('thusr.thusr_thread_id', '=', 'thread.thread_id')
                    ->where('thusr.thusr_user_id', $userId)
                    ->whereNull('thusr.thusr_deleted');
            })
            ->whereNull('thread.thread_deleted')
            ->select([
                'thread.thread_id as id',
                'thread.thread_type as type',
                'thread.thread_updated as updated_at',
            ]);

        return $this->paginatedResponse($request, $query, 'thread.thread_updated');
    }

    public function withdrawals(Request $request): JsonResponse
    {
        $query = DB::table('tbl_user_withdrawal_requests')
            ->where('withdrawal_user_id', $request->user()->user_id)
            ->select([
                'withdrawal_id as id',
                'withdrawal_amount as amount',
                'withdrawal_status as status',
                'withdrawal_request_date as requested_at',
            ]);

        return $this->paginatedResponse($request, $query, 'withdrawal_id');
    }

    public function resources(Request $request): JsonResponse
    {
        if (! $request->user()->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = DB::table('tbl_resources')
            ->where('resrc_user_id', $request->user()->user_id)
            ->whereNull('resrc_deleted')
            ->select([
                'resrc_id as id',
                'resrc_name as title',
                'resrc_name as name',
                'resrc_type as type',
                'resrc_size as size',
                'resrc_created as created_at',
            ]);

        return $this->paginatedResponse($request, $query, 'resrc_id');
    }

    public function courseEditRequests(Request $request): JsonResponse
    {
        if (! $request->user()->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = DB::table('tbl_course_edit_requests as req')
            ->join('tbl_courses as course', 'course.course_id', '=', 'req.coedre_course_id')
            ->join('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->where('course.course_user_id', $request->user()->user_id)
            ->select([
                'req.coedre_id as id',
                'crsdetail.course_title as course_title',
                'req.coedre_status as status',
                'req.coedre_created as created_at',
                'req.coedre_updated as updated_at',
                'req.coedre_duration as duration_days',
            ]);

        return $this->paginatedResponse($request, $query, 'coedre_id');
    }

    public function questions(Request $request): JsonResponse
    {
        if (! $request->user()->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = DB::table('tbl_questions')
            ->where('ques_user_id', $request->user()->user_id)
            ->whereNull('ques_deleted')
            ->select([
                'ques_id as id',
                'ques_title as title',
                'ques_type as type',
                'ques_created as created_at',
            ]);

        return $this->paginatedResponse($request, $query, 'ques_id');
    }

    public function quizzes(Request $request): JsonResponse
    {
        if (! $request->user()->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = DB::table('tbl_quizzes')
            ->where('quiz_user_id', $request->user()->user_id)
            ->whereNull('quiz_deleted')
            ->select([
                'quiz_id as id',
                'quiz_title as title',
                'quiz_type as type',
                'quiz_created as created_at',
            ]);

        return $this->paginatedResponse($request, $query, 'quiz_id');
    }

    public function giftcards(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->user_id;

        $query = DB::table('tbl_order_giftcards as ordgift')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordgift.ordgift_order_id')
            ->where('orders.order_user_id', $userId)
            ->select([
                'ordgift.ordgift_id as id',
                'ordgift.ordgift_code as code',
                'ordgift.ordgift_status as status',
                'ordgift.ordgift_receiver_name as receiver_name',
                'ordgift.ordgift_expiry as expires_at',
            ]);

        return $this->paginatedResponse($request, $query, 'ordgift_id');
    }

    public function favoriteCourses(Request $request): JsonResponse
    {
        if ($request->user()->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = DB::table('tbl_user_favourite_courses as fav')
            ->join('tbl_courses as course', 'course.course_id', '=', 'fav.ufc_course_id')
            ->where('fav.ufc_user_id', $request->user()->user_id)
            ->select([
                'course.course_id as id',
                'course.course_title as title',
                'course.course_slug as slug',
            ]);

        return $this->paginatedResponse($request, $query, 'course.course_id');
    }

    public function flashcards(Request $request): JsonResponse
    {
        $query = DB::table('tbl_flashcards')
            ->where('flashcard_user_id', $request->user()->user_id)
            ->select([
                'flashcard_id as id',
                'flashcard_title as title',
                'flashcard_addedon as created_at',
            ]);

        return $this->paginatedResponse($request, $query, 'flashcard_id');
    }

    public function forumQuestions(Request $request): JsonResponse
    {
        $query = DB::table('tbl_forum_questions')
            ->where('fque_user_id', $request->user()->user_id)
            ->where('fque_deleted', 0)
            ->select([
                'fque_id as id',
                'fque_title as title',
                'fque_status as status',
                'fque_added_on as created_at',
            ]);

        return $this->paginatedResponse($request, $query, 'fque_id');
    }

    public function forumTagRequests(Request $request): JsonResponse
    {
        $query = DB::table('tbl_forum_tag_requests')
            ->where('ftagreq_user_id', $request->user()->user_id)
            ->select([
                'ftagreq_id as id',
                'ftagreq_name as name',
                'ftagreq_status as status',
                'ftagreq_added_on as created_at',
            ]);

        return $this->paginatedResponse($request, $query, 'ftagreq_id');
    }

    public function referHistory(Request $request): JsonResponse
    {
        $query = DB::table('tbl_reward_points')
            ->where('repnt_user_id', $request->user()->user_id)
            ->select([
                'repnt_id as id',
                'repnt_points as points',
                'repnt_comment as comment',
                'repnt_datetime as created_at',
            ]);

        return $this->paginatedResponse($request, $query, 'repnt_id');
    }

    public function availability(Request $request): JsonResponse
    {
        if (! $request->user()->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = DB::table('tbl_availability')
            ->where('avail_user_id', $request->user()->user_id)
            ->select([
                'avail_id as id',
                'avail_starttime as starts_at',
                'avail_endtime as ends_at',
            ]);

        return $this->paginatedResponse($request, $query, 'starts_at', 'asc');
    }

    public function certificates(Request $request): JsonResponse
    {
        if ($request->user()->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $query = DB::table('tbl_course_progresses as prog')
            ->join('tbl_order_courses as ordcrs', 'ordcrs.ordcrs_id', '=', 'prog.crspro_ordcrs_id')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
            ->join('tbl_courses as course', 'course.course_id', '=', 'ordcrs.ordcrs_course_id')
            ->where('orders.order_user_id', $request->user()->user_id)
            ->whereNotNull('prog.crspro_completed')
            ->select([
                'prog.crspro_id as id',
                'course.course_title as title',
                'prog.crspro_completed as completed_at',
                'prog.crspro_progress as progress',
            ]);

        return $this->paginatedResponse($request, $query, 'completed_at');
    }
}
