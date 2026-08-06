<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminGroupClassManageService;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\Listings\AdminGroupClassListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminGroupClassesController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminGroupClassListingService $listings,
        private AdminGroupClassManageService $manage,
    ) {
    }

    public function createForm(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $langId = max(1, $request->integer('lang_id', 1));

            return response()->json(['data' => $this->manage->createForm($langId)]);
        });
    }

    public function teacherAutocomplete(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $keyword = (string) $request->query('keyword', '');

            return response()->json([
                'data' => $this->manage->teacherAutocomplete($keyword),
            ]);
        });
    }

    public function details(Request $request, int $classId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $classId) {
            $langId = max(1, $request->integer('lang_id', 1));
            $data = $this->manage->viewDetails($classId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Class not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function show(Request $request, int $classId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $classId) {
            $langId = max(1, $request->integer('lang_id', 1));
            $data = $this->manage->show($classId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Class not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function store(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $result = $this->manage->store($request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save class'], 422);
            }

            return response()->json(['message' => 'Class saved', 'id' => $result['id'] ?? null]);
        });
    }

    public function update(Request $request, int $classId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $classId) {
            $result = $this->manage->update($classId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save class'], 422);
            }

            return response()->json(['message' => 'Class saved', 'id' => $result['id'] ?? $classId]);
        });
    }

    public function langForm(Request $request, int $classId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($classId, $langId) {
            $data = $this->manage->langForm($classId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Class not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function storeLang(Request $request, int $classId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $classId, $langId) {
            $result = $this->manage->storeLang($classId, $langId, $request->all());
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to save language data'], 422);
            }

            return response()->json(['message' => 'Language data saved', 'id' => $result['id'] ?? $classId]);
        });
    }

    public function mediaForm(Request $request, int $classId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($classId) {
            $data = $this->manage->mediaForm($classId);
            if (! $data) {
                return response()->json(['message' => 'Class not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function uploadBanner(Request $request, int $classId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $classId) {
            $file = $request->file('banner');
            if (! $file) {
                return response()->json(['message' => 'Banner file is required'], 422);
            }

            $result = $this->manage->uploadBanner($classId, $file);
            if (! ($result['ok'] ?? false)) {
                return response()->json(['message' => $result['message'] ?? 'Unable to upload banner'], 422);
            }

            return response()->json(['message' => 'Banner uploaded', 'id' => $result['id'] ?? $classId]);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $request->merge(['export' => true, 'per_page' => 5000]);
            $module = $request->query('module', 'group-classes') === 'package-classes'
                ? 'package-classes'
                : 'group-classes';
            $result = $module === 'package-classes'
                ? $this->listings->packageClasses($request)
                : $this->listings->groupClasses($request);
            $rows = $result['data'] ?? [];

            return response()->streamDownload(function () use ($rows, $module) {
                $handle = fopen('php://output', 'w');
                if ($module === 'package-classes') {
                    fputcsv($handle, ['Package', 'Teacher', 'Start', 'End', 'Created', 'Service type', 'Status']);
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row['title'] ?? '',
                            $row['teacher_name'] ?? '',
                            $row['start_at'] ?? '',
                            $row['end_at'] ?? '',
                            $row['created_at'] ?? '',
                            $row['service_type_label'] ?? '',
                            $row['status_label'] ?? '',
                        ]);
                    }
                } else {
                    fputcsv($handle, ['Title', 'Type', 'Service type', 'Teacher', 'Entry fee', 'Start', 'End', 'Status']);
                    foreach ($rows as $row) {
                        fputcsv($handle, [
                            $row['title'] ?? '',
                            $row['class_type_label'] ?? '',
                            $row['service_type_label'] ?? '',
                            $row['teacher_name'] ?? '',
                            $row['entry_fee'] ?? '',
                            $row['start_at'] ?? '',
                            $row['end_at'] ?? '',
                            $row['status_label'] ?? '',
                        ]);
                    }
                }
                fclose($handle);
            }, $module.'.csv', ['Content-Type' => 'text/csv']);
        });
    }

    public function learners(Request $request, int $classId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $classId) {
            $result = $this->listings->learners($request, $classId);

            return response()->json($result);
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        $canEditGroup = $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_GROUP_CLASSES);
        $canEditPackage = $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_PACKAGE_CLASSES);
        if (! $canEditGroup && ! $canEditPackage) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardView(Request $request, callable $callback): JsonResponse|StreamedResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_GROUP_CLASSES)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
