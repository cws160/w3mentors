<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\OrderListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardOrdersController extends Controller
{
    public function __construct(private readonly OrderListingService $orders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $result = $this->orders->list(
            (int) $user->user_id,
            (bool) $user->user_is_teacher,
            [
                'keyword' => $request->string('keyword')->toString(),
                'order_type' => $request->integer('order_type') ?: null,
                'order_pmethod_id' => $request->integer('order_pmethod_id') ?: null,
                'date_from' => $request->string('date_from')->toString(),
                'date_to' => $request->string('date_to')->toString(),
                'page' => $request->integer('page', 1),
                'per_page' => $request->integer('per_page', 20),
            ]
        );

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
            'filters' => $result['filters'],
        ]);
    }
}
