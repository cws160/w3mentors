<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminCurrencyManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCurrencyController extends Controller
{
    public function __construct(
        private AdminCurrencyManageService $currencies,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function form(Request $request, int $currencyId = 0): JsonResponse
    {
        return $this->guardEdit($request, function () use ($currencyId) {
            try {
                return response()->json(['data' => $this->currencies->form($currencyId)]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function langForm(Request $request, int $currencyId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($currencyId, $langId) {
            try {
                $data = $this->currencies->langForm($currencyId, $langId);
                if ($data === null) {
                    return response()->json(['message' => 'Currency not found'], 404);
                }

                return response()->json(['data' => $data]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function setup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $currencyId = $this->currencies->setup($request->all());

                return response()->json(['data' => ['currency_id' => $currencyId]]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function langSetup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $currencyId = $this->currencies->langSetup($request->all());

                return response()->json(['data' => ['currency_id' => $currencyId]]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function changeStatus(Request $request, int $currencyId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $currencyId) {
            try {
                $status = $request->boolean('active') ? 1 : 0;
                $this->currencies->changeStatus($currencyId, $status);

                return response()->json(['message' => 'Status updated successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            $ids = array_values(array_map(
                'intval',
                $request->input('currencyList', $request->input('currencies', [])),
            ));

            if ($ids === []) {
                return response()->json(['message' => 'Unable to update order'], 422);
            }

            $this->currencies->updateOrder($ids);

            return response()->json(['message' => 'Order updated successfully']);
        });
    }

    public function fixerConfig(Request $request): JsonResponse
    {
        return $this->guardEdit($request, fn () => response()->json(['data' => $this->currencies->fixerConfig()]));
    }

    public function setupFixerConfig(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $data = $this->currencies->setupFixerConfig($request->all());

                return response()->json(['data' => $data]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function syncRates(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () {
            try {
                $data = $this->currencies->syncRates();

                return response()->json(['data' => $data]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();

        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_CURRENCY_MANAGEMENT)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
