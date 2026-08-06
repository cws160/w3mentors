<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminContentBlockManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContentBlocksController extends Controller
{
    public function __construct(
        private AdminContentBlockManageService $contentBlocks,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function types(Request $request): JsonResponse
    {
        return $this->guardView($request, fn () => response()->json([
            'data' => ['types' => $this->contentBlocks->types()],
        ]));
    }

    public function show(Request $request, int $blockId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($blockId) {
            $data = $this->contentBlocks->show($blockId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function update(Request $request, int $blockId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $blockId) {
            $result = $this->contentBlocks->saveGeneral($blockId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'epage_id' => $result['id'] ?? $blockId,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function langForm(Request $request, int $blockId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($blockId, $langId) {
            $data = $this->contentBlocks->langForm($blockId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function storeLang(Request $request, int $blockId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $blockId, $langId) {
            $result = $this->contentBlocks->saveLang($blockId, $langId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'epage_id' => $result['id'] ?? $blockId,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function changeStatus(Request $request, int $blockId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $blockId) {
            $this->contentBlocks->changeStatus($blockId, $request->boolean('active') ? 1 : 0);

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_map('intval', (array) $request->input('ids', []));
            $this->contentBlocks->updateOrder($ids);

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_CONTENT_BLOCKS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_CONTENT_BLOCKS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
