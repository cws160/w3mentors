<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminRatingReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminRatingReviewsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminRatingReviewService $reviews,
    ) {
    }

    public function show(Request $request, int $reviewId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        try {
            $data = $this->reviews->show($reviewId);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }

        $section = (int) $data['type'] === AdminRatingReviewService::TYPE_COURSE
            ? AdminPrivilegeService::SECTION_COURSE_REVIEWS
            : AdminPrivilegeService::SECTION_TEACHER_REVIEWS;

        if (! $this->privileges->canView($admin->admin_id, $section)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json(['data' => $data]);
    }

    public function updateStatus(Request $request, int $reviewId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        try {
            $existing = $this->reviews->show($reviewId);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }

        $section = (int) $existing['type'] === AdminRatingReviewService::TYPE_COURSE
            ? AdminPrivilegeService::SECTION_COURSE_REVIEWS
            : AdminPrivilegeService::SECTION_TEACHER_REVIEWS;

        if (! $this->privileges->canEdit($admin->admin_id, $section)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $status = $request->integer('status');
        try {
            $this->reviews->updateStatus($reviewId, $status);
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }

        return response()->json(['message' => 'Updated successfully']);
    }
}
