<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\GiftcardListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardGiftcardsController extends Controller
{
    public function __construct(private readonly GiftcardListingService $giftcards)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $result = $this->giftcards->list((int) $request->user()->user_id, [
            'keyword' => $request->string('keyword')->toString(),
            'giftcard_type' => $request->integer('giftcard_type', GiftcardListingService::TYPE_PURCHASED),
            'giftcard_status' => $request->has('giftcard_status') ? $request->integer('giftcard_status') : null,
            'page' => $request->integer('page', 1),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return response()->json([
            'data' => $result['items'],
            'meta' => $result['meta'],
            'filters' => $result['filters'],
        ]);
    }
}
