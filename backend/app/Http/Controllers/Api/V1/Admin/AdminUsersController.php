<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPrivilegeService;
use App\Services\Admin\AdminUserService;
use App\Services\Admin\Listings\AdminUsersListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminUsersController extends Controller
{
    public function __construct(
        private AdminUserService $users,
        private AdminUsersListingService $listing,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function autocomplete(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $keyword = (string) $request->query('keyword', '');

            return response()->json([
                'data' => $this->listing->autocomplete($keyword),
            ]);
        });
    }

    public function create(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            return response()->json($this->users->createForm($this->langId($request)));
        });
    }

    public function store(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $userId = $this->users->create($request->all());

            return response()->json(['message' => 'User created successfully', 'id' => $userId], 201);
        });
    }

    public function export(Request $request): StreamedResponse|JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            $request->merge(['export' => true]);
            $result = $this->listing->search($request);
            $rows = $result['data'];

            return response()->streamDownload(function () use ($rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, ['ID', 'Name', 'Email', 'Phone', 'Featured', 'Verified', 'Status', 'Registered']);
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row['id'],
                        $row['full_name'],
                        $row['email'],
                        $row['phone_display'] ?? '',
                        $row['featured'] ? 'Yes' : 'No',
                        $row['verified'] ? 'Yes' : 'No',
                        $row['active'] ? 'Active' : 'Inactive',
                        $row['created_at'],
                    ]);
                }
                fclose($handle);
            }, 'users.csv', ['Content-Type' => 'text/csv']);
        });
    }

    public function show(Request $request, int $userId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $userId) {
            return response()->json([
                'data' => $this->users->view($userId, $this->langId($request)),
            ]);
        });
    }

    public function edit(Request $request, int $userId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $userId) {
            return response()->json($this->users->editForm($userId, $this->langId($request)));
        });
    }

    public function update(Request $request, int $userId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $userId) {
            $this->users->update($userId, $request->all());

            return response()->json(['message' => 'User updated successfully']);
        });
    }

    public function login(Request $request, int $userId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($userId) {
            return response()->json($this->users->loginAsUser($userId));
        });
    }

    public function transactions(Request $request, int $userId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $userId) {
            return response()->json($this->users->transactions($userId, $request));
        });
    }

    public function storeTransaction(Request $request, int $userId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $userId) {
            $this->users->createTransaction($userId, $request->all());

            return response()->json(['message' => 'Transaction saved successfully', 'user_id' => $userId]);
        });
    }

    public function addresses(Request $request, int $userId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $userId) {
            return response()->json([
                'data' => $this->users->addresses($userId, $this->langId($request)),
            ]);
        });
    }

    public function changePassword(Request $request, int $userId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $userId) {
            $this->users->changePassword($userId, $request->all());

            return response()->json(['message' => 'Password updated successfully']);
        });
    }

    private function guardView(Request $request, callable $callback): Response
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_USERS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $this->run($callback);
    }

    private function guardEdit(Request $request, callable $callback): Response
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_USERS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $this->run($callback);
    }

    private function run(callable $callback): Response
    {
        try {
            return $callback();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\RuntimeException $e) {
            if ($e->getCode() === 404) {
                return response()->json(['message' => $e->getMessage()], 404);
            }
            throw $e;
        }
    }

    private function langId(Request $request): int
    {
        return max(1, $request->integer('lang_id', 1));
    }
}
