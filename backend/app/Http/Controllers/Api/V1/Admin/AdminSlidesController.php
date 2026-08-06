<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminSlideManageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSlidesController extends Controller
{
    public function __construct(
        private AdminSlideManageService $slides,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function form(Request $request, int $slideId = 0): JsonResponse
    {
        return $this->guardEdit($request, function () use ($slideId) {
            try {
                return response()->json(['data' => $this->slides->form($slideId)]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $slideId = $this->slides->setup($request->all());

                return response()->json([
                    'message' => 'Setup successful',
                    'data' => ['slide_id' => $slideId],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function mediaForm(Request $request, int $slideId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($slideId, $langId) {
            $data = $this->slides->mediaForm($slideId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Invalid request'], 422);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function setupMedia(Request $request, int $slideId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $slideId, $langId) {
            try {
                $files = [];
                foreach ($request->files->all() as $key => $file) {
                    if (preg_match('/^slide_image_(\d+)$/', (string) $key, $matches) === 1) {
                        $files[(int) $matches[1]] = $file;
                    }
                }

                $result = $this->slides->uploadMedia($slideId, $langId, $files);
                if (! ($result['ok'] ?? false)) {
                    return response()->json(['message' => $result['message'] ?? 'Unable to upload files'], 422);
                }

                return response()->json([
                    'message' => 'Files uploaded successfully',
                    'data' => ['slide_id' => $slideId, 'lang_id' => $langId],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function changeStatus(Request $request, int $slideId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $slideId) {
            try {
                $this->slides->changeStatus($slideId, $request->boolean('active') ? 1 : 0);

                return response()->json(['message' => 'Action performed successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_filter(array_map('intval', (array) $request->input('ids', []))));
            $this->slides->updateOrder($ids);

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    public function destroy(Request $request, int $slideId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($slideId) {
            try {
                $this->slides->delete($slideId);

                return response()->json(['message' => 'Record deleted successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_SLIDES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
