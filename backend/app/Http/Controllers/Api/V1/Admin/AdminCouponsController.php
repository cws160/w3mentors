<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminCouponsManageService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCouponsController extends Controller
{
    public function __construct(
        private AdminCouponsManageService $coupons,
        private AdminPrivilegeService $privileges,
    ) {
    }

    public function form(Request $request, int $couponId = 0): JsonResponse
    {
        return $this->guardEdit($request, function () use ($couponId) {
            try {
                return response()->json(['data' => $this->coupons->form($couponId)]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function langForm(Request $request, int $couponId, int $langId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($couponId, $langId) {
            try {
                $data = $this->coupons->langForm($couponId, $langId);
                if ($data === null) {
                    return response()->json(['message' => 'Coupon not found'], 404);
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
                $couponId = $this->coupons->setup($request->all());

                return response()->json([
                    'message' => 'Setup successful',
                    'data' => ['coupon_id' => $couponId],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function langSetup(Request $request): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request) {
            try {
                $couponId = $this->coupons->langSetup($request->all());

                return response()->json([
                    'message' => 'Setup successful',
                    'data' => ['coupon_id' => $couponId],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function destroy(Request $request, int $couponId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($couponId) {
            try {
                $this->coupons->delete($couponId);

                return response()->json(['message' => 'Record deleted successfully']);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    public function uses(Request $request): JsonResponse
    {
        return $this->guardView($request, function () use ($request) {
            try {
                $couponId = $request->integer('coupon_id', 0);
                $page = max(1, $request->integer('page', 1));
                $result = $this->coupons->uses($couponId, $page);

                return response()->json([
                    'data' => $result['data'],
                    'meta' => $result['meta'],
                ]);
            } catch (\InvalidArgumentException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
        });
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canView($admin->admin_id, AdminPrivilegeService::SECTION_DISCOUNT_COUPONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user();
        if (! $this->privileges->canEdit($admin->admin_id, AdminPrivilegeService::SECTION_DISCOUNT_COUPONS)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return $callback();
    }
}
