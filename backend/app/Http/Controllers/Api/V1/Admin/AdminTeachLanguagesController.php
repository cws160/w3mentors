<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminTeachLanguageManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminTeachLanguagesController extends Controller
{
    public function __construct(
        private AdminTeachLanguageManageService $languages,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function context(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $langId = max(1, $request->integer('lang_id', 1));
            $parentId = max(0, $request->integer('parent_id', 0));
            $excludeId = max(0, $request->integer('exclude_id', 0));

            return response()->json(['data' => $this->languages->context($parentId, $langId, $excludeId)]);
        });
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $langId = max(1, $request->integer('lang_id', 1));
            $keyword = (string) $request->query('keyword', '');

            return response()->json([
                'data' => $this->languages->autocomplete($keyword, $langId),
            ]);
        });
    }

    public function show(Request $request, int $tlangId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $tlangId) {
            $langId = max(1, $request->integer('lang_id', 1));
            $data = $this->languages->show($tlangId, $langId);
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

    public function update(Request $request, int $tlangId): JsonResponse
    {
        return $this->save($request, $tlangId);
    }

    public function changeStatus(Request $request, int $tlangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $tlangId) {
            $status = $request->boolean('active') ? 1 : 0;

            try {
                if (! $this->languages->changeStatus($tlangId, $status)) {
                    return response()->json(['message' => 'Record not found'], 404);
                }
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json(['message' => 'Status updated successfully']);
        });
    }

    public function destroy(Request $request, int $tlangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($tlangId) {
            try {
                if (! $this->languages->delete($tlangId)) {
                    return response()->json(['message' => 'Record not found'], 404);
                }
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_map(
                'intval',
                $request->input('teachingLangages', $request->input('teachingLanguages', $request->input('languages', []))),
            ));
            if ($ids === [] || ! $this->languages->updateOrder($ids)) {
                return response()->json(['message' => 'Unable to update order'], 422);
            }

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    public function mediaForm(Request $request, int $tlangId): JsonResponse
    {
        return $this->guardView($request, function () use ($tlangId) {
            $data = $this->languages->mediaForm($tlangId);
            if ($data === null) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function uploadImage(Request $request, int $tlangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $tlangId) {
            $file = $request->file('file', $request->file('tlang_image_file'));
            if (! $file) {
                return response()->json(['message' => 'Please select a file'], 422);
            }

            $result = $this->languages->uploadImage($tlangId, $file);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to upload image'], 422);
            }

            return response()->json(['message' => 'File uploaded successfully']);
        });
    }

    public function removeImage(Request $request, int $tlangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($tlangId) {
            $result = $this->languages->removeImage($tlangId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete image'], 422);
            }

            return response()->json(['message' => 'Deleted successfully']);
        });
    }

    private function save(Request $request, int $tlangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $tlangId) {
            $langId = max(1, $request->integer('lang_id', 1));

            try {
                $id = $this->languages->save($tlangId, $request->only([
                    'tlang_identifier',
                    'tlang_slug',
                    'tlang_name',
                    'tlang_description',
                    'tlang_parent',
                    'tlang_featured',
                    'tlang_active',
                    'tlang_min_price',
                    'tlang_max_price',
                    'tlang_hourly_price',
                    'update_langs_data',
                ]), $langId);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => ['tlang_id' => $id],
            ]);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_TEACH_LANGUAGES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_TEACH_LANGUAGES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
