<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminCourseLanguageManageService;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\Listings\AdminCourseLanguageListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCourseLanguagesController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminCourseLanguageListingService $listings,
        private AdminCourseLanguageManageService $manage,
    ) {
    }

    public function createForm(Request $request): JsonResponse
    {
        return $this->guardEdit($request, fn () => response()->json(['data' => $this->manage->createForm()]));
    }

    public function show(Request $request, int $clangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($clangId) {
            $data = $this->manage->show($clangId);
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

    public function update(Request $request, int $clangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $clangId) {
            $result = $this->manage->store(array_merge($request->all(), ['clang_id' => $clangId]));
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful', 'id' => $clangId]);
        });
    }

    public function langForm(Request $request, int $clangId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($clangId, $langId) {
            $data = $this->manage->langForm($clangId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function storeLang(Request $request, int $clangId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $clangId, $langId) {
            $payload = array_merge($request->all(), [
                'clanglang_clang_id' => $clangId,
                'clanglang_lang_id' => $langId,
            ]);
            $result = $this->manage->storeLang($payload);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save'], 422);
            }

            return response()->json(['message' => 'Setup successful']);
        });
    }

    public function changeStatus(Request $request, int $clangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $clangId) {
            $status = (int) $request->input('status', 0);
            $result = $this->manage->changeStatus($clangId, $status);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to update status'], 422);
            }

            return response()->json(['message' => 'Action performed successfully']);
        });
    }

    public function destroy(Request $request, int $clangId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($clangId) {
            $result = $this->manage->delete($clangId);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to delete'], 422);
            }

            return response()->json(['message' => 'Record deleted successfully']);
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $order = $request->input('courseLanguages', $request->input('course_languages', []));
            if (! is_array($order)) {
                return response()->json(['message' => 'Invalid request'], 422);
            }
            $result = $this->manage->updateOrder($order);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to update order'], 422);
            }

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $rows = $this->listings->exportRows($request);

            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Sr No', 'Identifier', 'Name', 'Status']);
                foreach ($rows as $index => $row) {
                    fputcsv($handle, [
                        $index + 1,
                        $row['identifier'] ?? '',
                        $row['title'] ?? '',
                        ((int) ($row['active'] ?? 0)) === 1 ? 'Active' : 'Inactive',
                    ]);
                }
                fclose($handle);
            }, 'course-languages.csv', ['Content-Type' => 'text/csv']);
        });
    }

  private function guardView(Request $request, callable $callback): JsonResponse|StreamedResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_COURSE_LANGUAGES)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse|StreamedResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_COURSE_LANGUAGES)) {
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
