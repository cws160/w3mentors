<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Services\Admin\AdminOrderManageService;
use App\Services\Admin\AdminOrderInvoiceService;
use App\Services\Admin\AdminPrivilegeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

class AdminOrdersController extends Controller
{
    public function __construct(
        private AdminPrivilegeService $privileges,
        private AdminOrderManageService $orders,
        private AdminOrderInvoiceService $invoices,
    ) {
    }

    public function show(Request $request, int $orderId): JsonResponse
    {
        return $this->guardView($request, function () use ($request, $orderId) {
            $langId = max(1, $request->integer('lang_id', 1));
            $data = $this->orders->showOrder($orderId, $langId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function cancel(Request $request, int $orderId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($orderId) {
            try {
                $this->orders->cancelOrder($orderId);
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], (int) ($e->getCode() ?: 422));
            }

            return response()->json(['message' => 'Order cancelled successfully']);
        });
    }

    public function addPayment(Request $request, int $orderId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $orderId) {
            try {
                $this->orders->addPayment($orderId, $request->all());
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], (int) ($e->getCode() ?: 422));
            }

            return response()->json(['message' => 'Payment details added successfully']);
        });
    }

    public function updateBankTransferStatus(Request $request, int $payId): JsonResponse
    {
        return $this->guardEdit($request, function () use ($request, $payId) {
            try {
                $this->orders->updateBankTransferStatus($payId, (int) $request->input('status', 0));
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], (int) ($e->getCode() ?: 422));
            }

            return response()->json(['message' => 'Order payment updated successfully']);
        });
    }

    public function showLesson(Request $request, int $lessonId): JsonResponse
    {
        return $this->guardLessonView($request, function () use ($request, $lessonId) {
            $data = $this->orders->showLesson($lessonId, max(1, $request->integer('lang_id', 1)));
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function showClass(Request $request, int $classId): JsonResponse
    {
        return $this->guardClassView($request, function () use ($request, $classId) {
            $data = $this->orders->showClass($classId, max(1, $request->integer('lang_id', 1)));
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function showPackage(Request $request, int $packageId): JsonResponse
    {
        return $this->guardPackageView($request, function () use ($request, $packageId) {
            $data = $this->orders->showPackage($packageId, max(1, $request->integer('lang_id', 1)));
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function showCourse(Request $request, int $courseOrderId): JsonResponse
    {
        return $this->guardCourseView($request, function () use ($request, $courseOrderId) {
            $data = $this->orders->showCourse($courseOrderId, max(1, $request->integer('lang_id', 1)));
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function showGiftcard(Request $request, int $orderId): JsonResponse
    {
        return $this->guardGiftcardView($request, function () use ($orderId) {
            $data = $this->orders->showGiftcard($orderId);
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function showOrderSubscriptionPlan(Request $request, int $planOrderId): JsonResponse
    {
        return $this->guardSubscriptionPlanView($request, function () use ($request, $planOrderId) {
            $data = $this->orders->showOrderSubscriptionPlan($planOrderId, max(1, $request->integer('lang_id', 1)));
            if (! $data) {
                return response()->json(['message' => 'Record not found'], 404);
            }

            return response()->json(['data' => $data]);
        });
    }

    public function invoice(Request $request, int $orderId): Response
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_MANAGE_ORDERS)) {
            abort(403);
        }

        $langId = max(1, $request->integer('lang_id', 1));
        $subOrderId = $request->integer('sub_order_id', 0);
        $invoice = $this->invoices->build($orderId, $langId, $subOrderId > 0 ? $subOrderId : null);
        if (! $invoice) {
            abort(404);
        }

        $html = view('admin.order-invoice', ['invoice' => $invoice])->render();

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
        ]);
    }

    private function guardView(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_MANAGE_ORDERS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardEdit(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canEdit($adminId, AdminPrivilegeService::SECTION_MANAGE_ORDERS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardLessonView(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_LESSONS_ORDERS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardClassView(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_CLASSES_ORDERS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardPackageView(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_PACKAGS_ORDERS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardCourseView(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_COURSES_ORDERS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardGiftcardView(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_GIFTCARD_ORDERS)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return $callback();
    }

    private function guardSubscriptionPlanView(Request $request, callable $callback): JsonResponse
    {
        $adminId = $this->adminId($request);
        if (! $this->privileges->canView($adminId, AdminPrivilegeService::SECTION_ORDER_SUBSCRIPTION_PLAN)) {
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
