<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminSpeakLanguageManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSpeakLanguagesController extends Controller
{
    public function __construct(
        private AdminSpeakLanguageManageService $languages,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request, int $slangId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $slangId) {
            $langId = max(1, $request->integer('lang_id', 1));
            $data = $this->languages->show($slangId, $langId);
            if ($data === null) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function store(Request $request): JsonResponse
    {
        return $this->save($request, 0);
    }

    public function update(Request $request, int $slangId): JsonResponse
    {
        return $this->save($request, $slangId);
    }

    public function changeStatus(Request $request, int $slangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $slangId) {
            $status = $request->boolean('active') ? 1 : 0;
            if (! $this->languages->changeStatus($slangId, $status)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Status updated successfully']);
        });
    }

    public function destroy(Request $request, int $slangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($slangId) {
            if (! $this->languages->delete($slangId)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_map(
                'intval',
                $request->input('spokenLangages', $request->input('spokenLanguages', $request->input('languages', []))),
            ));
            if ($ids === [] || ! $this->languages->updateOrder($ids)) {
                return response()->json(['message' => 'Unable to update order'], 422);
            }

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    private function save(Request $request, int $slangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $slangId) {
            $langId = max(1, $request->integer('lang_id', 1));

            try {
                $id = $this->languages->save($slangId, $request->only([
                    'slang_identifier',
                    'slang_name',
                    'slang_active',
                    'update_langs_data',
                ]), $langId);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => ['slang_id' => $id],
            ]);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_SPEAK_LANGUAGES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_SPEAK_LANGUAGES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
