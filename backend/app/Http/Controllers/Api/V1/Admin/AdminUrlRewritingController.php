<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminUrlRewritingManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUrlRewritingController extends Controller
{
    public function __construct(
        private AdminUrlRewritingManageService $urls,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function form(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $seoUrlId = $request->integer('seourlId', $request->integer('seourl_id', 0));
            $data = $this->urls->form($seoUrlId);
            if ($data === null) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $this->urls->setup($request->all());
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json(['message' => 'Setup successful']);
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($id) {
            if (! $this->urls->delete($id)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_URL_REWRITE)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_URL_REWRITE)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
