<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminBlogPostCategoryManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBlogPostCategoriesController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminBlogPostCategoryManageService $manage,
    ) {
    }

    public function createForm(Request $request): JsonResponse
    {
        $langId = max(1, $request->integer('lang_id', 1));
        $parentId = max(0, $request->integer('parent_id', 0));
        $excludeId = max(0, $request->integer('exclude_id', 0));

        return $this->guardEdit($request, fn () => response()->json([
            'data' => $this->manage->createForm($langId, $parentId, $excludeId),
        ]));
    }

    public function show(Request $request, int $categoryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($categoryId) {
            $data = $this->manage->show($categoryId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function store(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $result = $this->manage->store($request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful', 'id' => $result['id'] ?? null]);
        });
    }

    public function update(Request $request, int $categoryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $categoryId) {
            $result = $this->manage->store(array_merge($request->all(), ['bpcategory_id' => $categoryId]));
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful', 'id' => $categoryId]);
        });
    }

    public function langForm(Request $request, int $categoryId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($categoryId, $langId) {
            $data = $this->manage->langForm($categoryId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function storeLang(Request $request, int $categoryId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $categoryId, $langId) {
            $result = $this->manage->storeLang($categoryId, $langId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful']);
        });
    }

    public function changeStatus(Request $request, int $categoryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $categoryId) {
            $status = $request->integer('status', 1);
            $result = $this->manage->changeStatus($categoryId, $status);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to update'], 422);
            }

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function destroy(Request $request, int $categoryId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($categoryId) {
            $result = $this->manage->delete($categoryId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $order = $request->input('bpcategory', $request->input('categoriesList', []));
            if (! is_array($order)) {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            $result = $this->manage->updateOrder($order);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to update'], 422);
            }

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_BLOG_POST_CATEGORIES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
