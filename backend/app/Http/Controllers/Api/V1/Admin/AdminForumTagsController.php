<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminForumManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminForumTagsController extends Controller
{
    public function __construct(
        private AdminForumManageService $forum,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return $this->guardView($request, function () use ($id) {
            $data = $this->forum->tagShow($id);
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

    public function update(Request $request, int $id): JsonResponse
    {
        return $this->save($request, $id);
    }

    public function changeStatus(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $id) {
            $status = $request->boolean('active') ? 1 : 0;
            if (! $this->forum->tagChangeStatus($id, $status)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($id) {
            if (! $this->forum->tagDelete($id)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function restore(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($id) {
            if (! $this->forum->tagRestore($id)) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['message' => 'Tag restored successfully']);
        });
    }

    private function save(Request $request, int $id): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $id) {
            try {
                $savedId = $this->forum->tagSave($id, $request->only([
                    'ftag_name',
                    'ftag_language_id',
                    'ftag_active',
                ]));
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'message' => 'Setup successful',
                'data' => ['ftag_id' => $savedId],
            ]);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_DISCUSSION_FORUM)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_DISCUSSION_FORUM)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
