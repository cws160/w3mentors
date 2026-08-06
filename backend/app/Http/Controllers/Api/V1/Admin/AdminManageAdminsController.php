<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminManageAdminService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminManageAdminsController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminManageAdminService $admins,
    ) {
    }

    public function createForm(Request $request): JsonResponse
    {
        return $this->guardEdit($request, fn () => response()->json($this->admins->createForm()));
    }

    public function store(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $id = $this->admins->create($request->all());

            return response()->json(['message' => 'Setup successful', 'id' => $id], 201);
        });
    }

    public function show(Request $request, int $adminId): JsonResponse
    {
        return $this->guardView($request, function () use ($adminId) {
            return response()->json(['data' => $this->admins->show($adminId)]);
        });
    }

    public function update(Request $request, int $adminId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $adminId) {
            /** @var Admin $admin */
            $admin = $request->user();
            $this->admins->update($adminId, $request->all(), (int) $admin->admin_id);

            return response()->json(['message' => 'Updated successfully']);
        });
    }

    public function changePassword(Request $request, int $adminId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $adminId) {
            $this->admins->changePassword($adminId, $request->all());

            return response()->json(['message' => 'Password changed successfully']);
        });
    }

    public function updateStatus(Request $request, int $adminId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $adminId) {
            /** @var Admin $admin */
            $admin = $request->user();
            $this->admins->updateStatus($adminId, $request->integer('active'), (int) $admin->admin_id);

            return response()->json(['message' => 'Updated successfully']);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $result = $this->admins->exportList($request);
            $rows = $result['data'] ?? [];

            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['Full name', 'Username', 'Email', 'Status']);
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row['full_name'] ?? '',
                        $row['username'] ?? '',
                        $row['email'] ?? '',
                        ! empty($row['active']) ? 'Active' : 'Inactive',
                    ]);
                }
                fclose($handle);
            }, 'admin-users.csv', ['Content-Type' => 'text/csv']);
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse|StreamedResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_ADMIN_USERS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $this->run($callback);
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse|StreamedResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_ADMIN_USERS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $this->run($callback);
    }

    /** @return JsonResponse|StreamedResponse */
    private function run(callable $callback): JsonResponse|StreamedResponse
    {
        try {
            return $callback();
        } catch (\RuntimeException $e) {
            $code = (int) $e->getCode();

            return response()->json(
                ['message' => $e->getMessage()],
                ($code >= 400 && $code < 600) ? $code : 500,
            );
        }
    }
}
