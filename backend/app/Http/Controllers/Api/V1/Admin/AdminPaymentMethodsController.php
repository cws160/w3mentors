<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminPaymentMethodManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentMethodsController extends Controller
{
    public function __construct(
        private AdminPaymentMethodManageService $methods,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function settingForm(Request $request, int $methodId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($methodId) {
            try {
                return response()->json(['data' => $this->methods->settingForm($methodId)]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function settingSetup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $methodId = $this->methods->settingSetup($request->all());

                return response()->json([
                    'message' => 'Setup successful',
                    'data' => ['pmethod_id' => $methodId],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function txnfeeForm(Request $request, int $methodId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($methodId) {
            try {
                return response()->json(['data' => $this->methods->txnfeeForm($methodId)]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function txnfeeSetup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $methodId = $this->methods->txnfeeSetup($request->all());

                return response()->json([
                    'message' => 'Setup successful',
                    'data' => ['pmethod_id' => $methodId],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function changeStatus(Request $request, int $methodId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $methodId) {
            try {
                $status = $request->boolean('active') ? 1 : 0;
                $this->methods->changeStatus($methodId, $status);

                return response()->json(['message' => 'Action performed successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function updateOrder(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $ids = $request->input('paymentMethod', $request->input('payment_method', []));
                if (! is_array($ids)) {
                    throw new \InvalidArgumentException('Invalid request');
                }
                $this->methods->updateOrder($ids);

                return response()->json(['message' => 'Order updated successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_PAYMENT_METHODS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
