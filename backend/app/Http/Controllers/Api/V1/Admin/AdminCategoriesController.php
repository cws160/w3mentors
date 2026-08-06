<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminCategoryManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCategoriesController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminCategoryManageService $manage,
    ) {
    }

    public function createForm(Request $request): JsonResponse
    {
        $cateType = (int) $request->query('cate_type', AdminCategoryManageService::TYPE_COURSE);
        $langId = max(1, $request->integer('lang_id', 1));
        $parentId = max(0, $request->integer('parent_id', 0));

        return $this->guardEdit($request, fn () => response()->json([
            'data' => $this->manage->createForm($langId, $cateType, $parentId),
        ]), $cateType);
    }

    public function show(Request $request, int $cateId): JsonResponse
    {
        $cateType = (int) $request->query('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($cateId) {
            $data = $this->manage->show($cateId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        }, $cateType);
    }

    public function store(Request $request): JsonResponse
    {
        $cateType = (int) $request->input('cate_type', AdminCategoryManageService::TYPE_COURSE);
        $langId = max(1, $request->integer('lang_id', 1));

        return $this->guardEdit($request, function () use ($request, $langId) {
            $result = $this->manage->store($request->all(), $langId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful', 'id' => $result['id'] ?? null]);
        }, $cateType);
    }

    public function update(Request $request, int $cateId): JsonResponse
    {
        $cateType = (int) $request->input('cate_type', AdminCategoryManageService::TYPE_COURSE);
        $langId = max(1, $request->integer('lang_id', 1));

        return $this->guardEdit($request, function () use ($request, $cateId, $langId) {
            $result = $this->manage->store(array_merge($request->all(), ['cate_id' => $cateId]), $langId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful', 'id' => $cateId]);
        }, $cateType);
    }

    public function langForm(Request $request, int $cateId, int $langId): JsonResponse
    {
        $cateType = (int) $request->query('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($cateId, $langId) {
            $data = $this->manage->langForm($cateId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        }, $cateType);
    }

    public function storeLang(Request $request, int $cateId, int $langId): JsonResponse
    {
        $cateType = (int) $request->input('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($request, $cateId, $langId) {
            $payload = array_merge($request->all(), [
                'catelang_cate_id' => $cateId,
                'catelang_lang_id' => $langId,
            ]);
            $result = $this->manage->storeLang($payload);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful']);
        }, $cateType);
    }

    public function changeStatus(Request $request, int $cateId): JsonResponse
    {
        $cateType = (int) $request->input('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($request, $cateId) {
            $status = (int) $request->input('status', 0);
            $result = $this->manage->changeStatus($cateId, $status);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to update status'], 422);
            }

            return response()->json(['message' => 'Status updated successfully']);
        }, $cateType);
    }

    public function destroy(Request $request, int $cateId): JsonResponse
    {
        $cateType = (int) $request->query('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($cateId) {
            $result = $this->manage->delete($cateId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        }, $cateType);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $cateType = (int) $request->input('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($request, $cateType) {
            $order = $request->input('categoriesList', $request->input('categories_list', []));
            if (! is_array($order)) {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            $result = $this->manage->updateOrder($order, $cateType);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to update order'], 422);
            }

            return response()->json(['message' => 'Order updated successfully']);
        }, $cateType);
    }

    public function mediaForm(Request $request, int $cateId): JsonResponse
    {
        $cateType = (int) $request->query('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($cateId) {
            $data = $this->manage->mediaForm($cateId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        }, $cateType);
    }

    public function uploadImage(Request $request, int $cateId): JsonResponse
    {
        $cateType = (int) $request->input('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($request, $cateId) {
            $file = $request->file('file', $request->file('category_image'));
            if (! $file) {
                return response()->json(['message' => 'Invalid request or file not supported'], 422);
            }

            $result = $this->manage->uploadImage($cateId, $file);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to upload image'], 422);
            }

            return response()->json(['message' => 'Image uploaded successfully']);
        }, $cateType);
    }

    public function removeImage(Request $request, int $cateId): JsonResponse
    {
        $cateType = (int) $request->query('cate_type', AdminCategoryManageService::TYPE_COURSE);

        return $this->guardEdit($request, function () use ($cateId) {
            $result = $this->manage->removeImage($cateId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete image'], 422);
            }

            return response()->json(['message' => 'Deleted successfully']);
        }, $cateType);
    }

    private function guardEdit(Request $request, callable $callback, int $cateType = AdminCategoryManageService::TYPE_COURSE): JsonResponse
    {
        $adminId = $this->adminId($request);
        $section = $cateType === AdminCategoryManageService::TYPE_QUESTION
            ? AdminPrivilegeService::SECTION_QUIZ_CATEGORIES
            : AdminPrivilegeService::SECTION_COURSE_CATEGORIES;

        if (! $this->privileges->canEdit($adminId, $section)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function adminId(Request $request): int
    {
        /** @var Admin $admin */
        $admin = $request->user();

        return (int) $admin->admin_id;
    }
}
