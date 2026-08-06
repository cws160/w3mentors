<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WithdrawListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardWithdrawalsController extends Controller
{
    public function __construct(private readonly WithdrawListingService $withdrawals)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->user_id;
        $result = $this->withdrawals->list($userId, [
            'keyword' => $request->string('keyword')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
            'balance' => $result['balance'],
            'can_withdraw' => $result['can_withdraw'],
        ]);
    }
}
