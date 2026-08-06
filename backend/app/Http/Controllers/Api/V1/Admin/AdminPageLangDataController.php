<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPageLangDataManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPageLangDataController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminPageLangDataManageService $manage,
    ) {
    }

    public function langForm(Request $request, int $plangId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($plangId, $langId) {
            $data = $this->manage->langForm($plangId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function langSetup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $result = $this->manage->langSetup($request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'plang_id' => $result['plang_id'] ?? 0,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_PAGE_LANG_DATA)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
