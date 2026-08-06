<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardService;
use App\Services\Admin\AdminGoogleAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(
        private AdminDashboardService $dashboard,
        private AdminGoogleAnalyticsService $analytics
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $langId = $request->integer('lang_id', 1);

        return response()->json([
            'stats' => $this->dashboard->stats(),
            'features' => $this->dashboard->featureFlags(),
            'page_text' => $this->dashboard->pageText($langId),
        ]);
    }

    public function charts(): JsonResponse
    {
        return response()->json($this->dashboard->chartData());
    }

    public function topLessonLanguages(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->topLessonLanguages(
                $request->integer('lang_id', 1),
                $request->integer('interval', 9)
            ),
        ]);
    }

    public function topClassLanguages(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->topClassLanguages(
                $request->integer('lang_id', 1),
                $request->integer('interval', 9)
            ),
        ]);
    }

    public function topCourseCategories(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->dashboard->topCourseCategories(
                $request->integer('lang_id', 1),
                $request->integer('interval', 9)
            ),
        ]);
    }

    public function analyticsEvents(Request $request): JsonResponse
    {
        $features = $this->dashboard->featureFlags();

        return response()->json(
            $this->analytics->eventMeasurements(
                $request->integer('interval', 9),
                $features['courses_enabled'],
                $features['group_classes_enabled']
            )
        );
    }

    public function analyticsTraffic(Request $request): JsonResponse
    {
        return response()->json(
            $this->analytics->trafficAcquisitions($request->integer('interval', 9))
        );
    }
}
