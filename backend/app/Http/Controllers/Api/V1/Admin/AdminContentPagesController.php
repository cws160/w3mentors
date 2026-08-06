<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminContentPageManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContentPagesController extends Controller
{
    public function __construct(
        private AdminContentPageManageService $contentPages,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function createForm(Request $request): JsonResponse
    {
        return $this->guardEdit($request, fn () => response()->json([
            'data' => $this->contentPages->createForm(),
        ]));
    }

    public function show(Request $request, int $pageId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($pageId) {
            $data = $this->contentPages->show($pageId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function store(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $result = $this->contentPages->saveGeneral($request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'page_id' => $result['id'] ?? 0,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function update(Request $request, int $pageId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $pageId) {
            $result = $this->contentPages->saveGeneral(array_merge($request->all(), ['cpage_id' => $pageId]));
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'page_id' => $result['id'] ?? $pageId,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function langForm(Request $request, int $pageId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($pageId, $langId) {
            $data = $this->contentPages->langForm($pageId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function storeLang(Request $request, int $pageId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $pageId, $langId) {
            $result = $this->contentPages->saveLang($pageId, $langId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => [
                    'page_id' => $result['id'] ?? $pageId,
                    'next_lang_id' => $result['next_lang_id'] ?? 0,
                ],
            ]);
        });
    }

    public function uploadBackgroundImage(Request $request, int $pageId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $pageId, $langId) {
            $result = $this->contentPages->uploadBackgroundImage($pageId, $langId, $request->file('file'));
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to upload'], 422);
            }

            return response()->json([
                'message' => 'Uploaded successfully',
                'data' => [
                    'page_id' => $result['id'] ?? $pageId,
                    'lang_id' => $langId,
                    'bg_image' => $result['bg_image'] ?? null,
                ],
            ]);
        });
    }

    public function removeBackgroundImage(Request $request, int $pageId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($pageId, $langId) {
            $result = $this->contentPages->removeBackgroundImage($pageId, $langId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Deleted successfully']);
        });
    }

    public function destroy(Request $request, int $pageId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($pageId) {
            $result = $this->contentPages->delete($pageId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_CONTENT_PAGES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
