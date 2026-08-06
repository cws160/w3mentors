<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminNavigationManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminNavigationsController extends Controller
{
    public function __construct(
        private AdminNavigationManageService $navigations,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request, int $navigationId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($navigationId) {
            $data = $this->navigations->show($navigationId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function update(Request $request, int $navigationId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $navigationId) {
            $result = $this->navigations->saveGeneral($navigationId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'nav_id' => $result['id'] ?? $navigationId,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function pages(Request $request, int $navigationId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $navigationId) {
            $langId = max(1, (int) $request->query('lang_id', 1));
            $data = $this->navigations->pages($navigationId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function langForm(Request $request, int $navigationId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($navigationId, $langId) {
            $data = $this->navigations->langForm($navigationId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function storeLang(Request $request, int $navigationId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $navigationId, $langId) {
            $result = $this->navigations->saveLang($navigationId, $langId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'nav_id' => $result['id'] ?? $navigationId,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function updateLinkStatus(Request $request, int $linkId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $linkId) {
            if (! $this->navigations->changeLinkStatus($linkId, $request->boolean('active') ? 1 : 0)) {
                return response()->json(['message' => 'Status is not available for this record'], 422);
            }

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function linkForm(Request $request, int $navigationId, int $linkId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $navigationId, $linkId) {
            $langId = max(1, (int) $request->query('lang_id', 1));
            $data = $this->navigations->linkForm($navigationId, $linkId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function saveLink(Request $request, int $navigationId, int $linkId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $navigationId, $linkId) {
            $result = $this->navigations->saveLink($navigationId, $linkId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'nlink_id' => $result['id'] ?? $linkId,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function linkLangForm(Request $request, int $navigationId, int $linkId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($navigationId, $linkId, $langId) {
            $data = $this->navigations->linkLangForm($navigationId, $linkId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function saveLinkLang(Request $request, int $navigationId, int $linkId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $navigationId, $linkId, $langId) {
            $result = $this->navigations->saveLinkLang($navigationId, $linkId, $langId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'nlink_id' => $result['id'] ?? $linkId,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function updateLinkOrder(Request $request, int $navigationId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $navigationId) {
            $ids = array_map('intval', (array) $request->input('ids', []));
            $this->navigations->updateLinkOrder($navigationId, $ids);

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    public function deleteLink(Request $request, int $linkId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($linkId) {
            if (! $this->navigations->deleteLink($linkId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateStatus(Request $request, int $navigationId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $navigationId) {
            $updated = DB::table('tbl_navigations')
                ->where('nav_id', $navigationId)
                ->update(['nav_active' => $request->boolean('active') ? 1 : 0]);

            if ($updated === 0 && ! DB::table('tbl_navigations')->where('nav_id', $navigationId)->exists()) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_NAVIGATION_MANAGEMENT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_NAVIGATION_MANAGEMENT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
