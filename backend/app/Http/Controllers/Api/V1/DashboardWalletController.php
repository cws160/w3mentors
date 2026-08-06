<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\WalletListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardWalletController extends Controller
{
    public function __construct(private readonly WalletListingService $wallet)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->user_id;
        $result = $this->wallet->list($userId, [
            'keyword' => $request->string('keyword')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return response()->json([
            'data' => [
                'balance' => $result['balance'],
                'transactions' => $result['transactions'],
            ],
            'meta' => $result['meta'],
        ]);
    }
}
