<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminRobotsTxtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBotsController extends Controller
{
    public function __construct(
        private AdminRobotsTxtService $robots,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request): JsonResponse
    {
        return $this->guardView($request, function () {
            try {
                $content = $this->robots->content();
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            return response()->json([
                'data' => [
                    'bots_txt' => $content,
                ],
            ]);
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $this->robots->save((string) $request->input('botsTxt', ''));
            } catch (\RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 500);
            }

            return response()->json(['message' => 'Setup successful']);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_ROBOTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_ROBOTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
