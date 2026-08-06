<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardPlansController extends Controller
{
    public function __construct(private PlanService $plans)
    {
    }

    public function show(Request $request, int $planId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        if ($planId < 1) {
            return response()->json([
                'data' => null,
                'meta' => $this->plans->formMeta(),
            ]);
        }

        $plan = $this->plans->getForTeacher((int) $user->user_id, $planId);
        if (! $plan) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json([
            'data' => $plan,
            'meta' => $this->plans->formMeta(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'plan_id' => 'nullable|integer|min:0',
            'plan_title' => 'required|string|max:255',
            'plan_detail' => 'required|string|max:500',
            'plan_level' => 'required|integer|min:1|max:5',
        ]);

        $uploads = $request->file('plan_file', []);
        if (! is_array($uploads)) {
            $uploads = $uploads ? [$uploads] : [];
        }

        try {
            $planId = $this->plans->save((int) $user->user_id, $validated, $uploads);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Saved successfully',
            'data' => ['id' => $planId],
        ]);
    }

    public function destroy(Request $request, int $planId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $this->plans->delete((int) $user->user_id, $planId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function destroyFile(Request $request, int $planId, int $fileId): JsonResponse
    {
        $user = $request->user();
        if (! $user->user_is_teacher) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        try {
            $this->plans->deleteFile((int) $user->user_id, $planId, $fileId);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'File removed']);
    }
}
