<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TeacherListingResource;
use App\Http\Resources\TeacherProfileResource;
use App\Models\User;
use App\Services\TeacherAvailabilityService;
use App\Services\TeacherBookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function __construct(
        private TeacherBookingService $booking,
        private TeacherAvailabilityService $availability
    ) {
    }
    public function index(Request $request): JsonResponse
    {
        $query = User::active()
            ->verified()
            ->teachers()
            ->leftJoin('tbl_teacher_stats as ts', 'ts.testat_user_id', '=', 'tbl_users.user_id')
            ->select([
                'tbl_users.*',
                'ts.testat_ratings',
                'ts.testat_reviewes',
                'ts.testat_students',
                'ts.testat_lessons',
                'ts.testat_classes',
                'ts.testat_courses',
                'ts.testat_minprice',
                'ts.testat_maxprice',
            ])
            ->orderByDesc('user_featured')
            ->orderByDesc('user_lastseen');

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->where('user_first_name', 'like', "%{$search}%")
                    ->orWhere('user_last_name', 'like', "%{$search}%")
                    ->orWhere('user_username', 'like', "%{$search}%");
            });
        }

        $teachers = $query->paginate($request->integer('per_page', 12));

        return response()->json([
            'data' => TeacherListingResource::collection($teachers),
            'meta' => [
                'current_page' => $teachers->currentPage(),
                'last_page' => $teachers->lastPage(),
                'per_page' => $teachers->perPage(),
                'total' => $teachers->total(),
            ],
        ]);
    }

    public function show(string $teacher): JsonResponse
    {
        $query = User::active()
            ->verified()
            ->teachers()
            ->leftJoin('tbl_teacher_stats as ts', 'ts.testat_user_id', '=', 'tbl_users.user_id')
            ->select([
                'tbl_users.*',
                'ts.testat_ratings',
                'ts.testat_reviewes',
                'ts.testat_students',
                'ts.testat_lessons',
                'ts.testat_classes',
                'ts.testat_courses',
                'ts.testat_minprice',
                'ts.testat_maxprice',
            ]);

        if (is_numeric($teacher)) {
            $query->where('tbl_users.user_id', (int) $teacher);
        } else {
            $query->where('tbl_users.user_username', $teacher);
        }

        $user = $query->first();

        if (!$user) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }

        return response()->json(['data' => new TeacherProfileResource($user)]);
    }

    public function bookingOptions(Request $request, string $teacher): JsonResponse
    {
        $user = $this->resolveTeacher($teacher);
        if (!$user) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }

        if ($request->user() && $request->user()->user_id === $user->user_id) {
            return response()->json(['message' => 'Invalid request'], 422);
        }

        $langId = $request->integer('lang_id', 1);
        $data = $this->booking->getBookingOptions(
            $user->user_id,
            $langId,
            $request->integer('ordles_tlang_id') ?: null,
            $request->integer('ordles_duration') ?: null
        );

        if ($data['languages'] === []) {
            return response()->json(['message' => 'No teach languages configured'], 422);
        }

        return response()->json(['data' => array_merge($data, ['teacher_id' => $user->user_id])]);
    }

    public function availabilityMeta(Request $request, string $teacher): JsonResponse
    {
        $user = $this->resolveTeacher($teacher);
        if (!$user) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }

        $timezone = $this->viewerTimezone($request);

        return response()->json([
            'data' => $this->availability->meta($user->user_id, $timezone),
        ]);
    }

    public function availabilitySlots(Request $request, string $teacher): JsonResponse
    {
        $user = $this->resolveTeacher($teacher);
        if (!$user) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }

        $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'duration' => ['nullable', 'integer', 'min:1'],
        ]);

        $timezone = $this->viewerTimezone($request);
        $viewerId = $request->user()?->user_id;

        return response()->json([
            'data' => $this->availability->slotsForDate(
                $user->user_id,
                $request->string('date')->toString(),
                $timezone,
                $viewerId,
                $request->integer('duration', 15)
            ),
        ]);
    }

    private function resolveTeacher(string|int $teacher): ?User
    {
        return $this->booking->resolveTeacher($teacher);
    }

    private function viewerTimezone(Request $request): string
    {
        $tz = $request->user()?->user_timezone ?? $request->string('timezone')->toString();

        return $tz !== '' ? $tz : 'UTC';
    }
}
