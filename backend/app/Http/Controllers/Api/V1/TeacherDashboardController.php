<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TeacherDashboardService;
use App\Services\TeacherScheduledSessionsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherDashboardController extends Controller
{
    public function __construct(
        private readonly TeacherDashboardService $dashboard,
        private readonly TeacherScheduledSessionsService $scheduledSessions,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Teacher account required'], 403);
        }

        return response()->json([
            'data' => $this->dashboard->summary(
                (int) $user->user_id,
                (int) ($user->user_lang_id ?: 1)
            ),
        ]);
    }

    public function scheduledSessions(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Teacher account required'], 403);
        }

        $start = (string) $request->query('start', '');
        $end = (string) $request->query('end', '');
        $userType = $request->integer('user_type', TeacherScheduledSessionsService::USER_TYPE_TEACHER);

        if ($start === '' || $end === '') {
            return response()->json(['message' => 'Invalid date range'], 422);
        }

        return response()->json([
            'data' => $this->scheduledSessions->events(
                (int) $user->user_id,
                $start,
                $end,
                $userType
            ),
        ]);
    }
}
