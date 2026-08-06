<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminBlogPostManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBlogPostsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminBlogPostManageService $manage,
    ) {
    }

    public function createForm(Request $request): JsonResponse
    {
        $langId = max(1, $request->integer('lang_id', 1));
        $postId = max(0, $request->integer('post_id', 0));

        return $this->guardEdit($request, fn () => response()->json([
            'data' => $this->manage->createForm($langId, $postId),
        ]));
    }

    public function show(Request $request, int $postId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($postId) {
            $data = $this->manage->show($postId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function store(Request $request): JsonResponse
    {
        $langId = max(1, $request->integer('lang_id', 1));

        return $this->guardEdit($request, function () use ($request, $langId) {
            $result = $this->manage->store($request->all(), $langId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful', 'id' => $result['id'] ?? null]);
        });
    }

    public function update(Request $request, int $postId): JsonResponse
    {
        $langId = max(1, $request->integer('lang_id', 1));

        return $this->guardEdit($request, function () use ($request, $postId, $langId) {
            $result = $this->manage->store(array_merge($request->all(), ['post_id' => $postId]), $langId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful', 'id' => $postId]);
        });
    }

    public function langForm(Request $request, int $postId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($postId, $langId) {
            $data = $this->manage->langForm($postId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function storeLang(Request $request, int $postId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $postId, $langId) {
            $result = $this->manage->storeLang($postId, $langId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful']);
        });
    }

    public function destroy(Request $request, int $postId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($postId) {
            $result = $this->manage->delete($postId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function imagesForm(Request $request, int $postId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($postId) {
            $data = $this->manage->imagesForm($postId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function images(Request $request, int $postId): JsonResponse
    {
        $langId = max(0, $request->integer('lang_id', 0));

        return $this->guardEdit($request, function () use ($postId, $langId) {
            return response()->json(['data' => $this->manage->listImages($postId, $langId)]);
        });
    }

    public function uploadImage(Request $request, int $postId): JsonResponse
    {
        $langId = max(0, $request->integer('lang_id', 0));

        return $this->guardEdit($request, function () use ($request, $postId, $langId) {
            $file = $request->file('file');
            if (! $file) {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            $result = $this->manage->uploadImage($postId, $langId, $file);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to upload'], 422);
            }

            return response()->json(['message' => 'Image uploaded successfully']);
        });
    }

    public function deleteImage(Request $request, int $postId, int $fileId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($postId, $fileId) {
            $result = $this->manage->deleteImage($postId, $fileId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Deleted successfully']);
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_BLOG_POSTS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
