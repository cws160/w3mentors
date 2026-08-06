<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TeacherAccountService;
use App\Services\TeacherAvailabilityCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherAvailabilityCalendarController extends Controller
{
    public function __construct(
        private TeacherAvailabilityCalendarService $calendar,
        private TeacherAccountService $account
    ) {
    }

    public function context(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        return response()->json([
            'data' => $this->calendar->context($user->user_id, $user->user_timezone ?? 'UTC'),
        ]);
    }

    public function generalEvents(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        return response()->json([
            'data' => $this->calendar->getGeneral($user->user_id, $user->user_timezone ?? 'UTC'),
        ]);
    }

    public function weeklyEvents(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $start = $request->string('start')->toString();
        $end = $request->string('end')->toString();
        if ($start === '' || $end === '') {
            return response()->json(['message' => 'Invalid request'], 422);
        }

        return response()->json([
            'data' => $this->calendar->getWeekly($user->user_id, $user->user_timezone ?? 'UTC', $start, $end),
        ]);
    }

    public function saveGeneral(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $events = $request->input('data', []);
        if (! is_array($events)) {
            return response()->json(['message' => 'Invalid data'], 422);
        }

        $this->calendar->saveGeneral($user->user_id, $user->user_timezone ?? 'UTC', $events);

        return response()->json([
            'message' => 'Availability updated successfully',
            'progress' => $this->account->getProfileProgress($user->user_id),
        ]);
    }

    public function saveWeekly(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);
        $start = $request->string('start')->toString();
        $end = $request->string('end')->toString();
        $events = $request->input('availability', []);
        if ($start === '' || $end === '' || ! is_array($events)) {
            return response()->json(['message' => 'Invalid request'], 422);
        }

        $this->calendar->saveWeekly($user->user_id, $user->user_timezone ?? 'UTC', $start, $end, $events);

        return response()->json([
            'message' => 'Availability updated successfully',
        ]);
    }

    public function progress(Request $request): JsonResponse
    {
        $user = $this->requireTeacher($request);

        return response()->json([
            'data' => $this->account->getProfileProgress($user->user_id),
        ]);
    }

    private function requireTeacher(Request $request)
    {
        $user = $request->user();
        if (! $user || ! $user->user_is_teacher) {
            abort(403, 'Teacher access required');
        }

        return $user;
    }
}
