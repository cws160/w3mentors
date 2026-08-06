<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\LearnerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearnerDashboardController extends Controller
{
    public function __construct(
        private readonly LearnerDashboardService $dashboard
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => $this->dashboard->summary((int) $user->user_id),
        ]);
    }
}
