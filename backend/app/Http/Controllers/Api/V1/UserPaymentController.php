<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\UserPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserPaymentController extends Controller
{
    public function __construct(private UserPaymentService $payments)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $data = $this->payments->index((int) $user->user_id);
        if (count($data['payout_methods']) === 0) {
            return response()->json([
                'message' => 'No payout methods are active.',
                'data' => ['enabled' => false],
            ], 403);
        }

        return response()->json(['data' => $this->paymentPayload((int) $user->user_id)]);
    }

    public function updateBank(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $validator = Validator::make($request->all(), [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:100'],
            'ifsc_swift_code' => ['required', 'string', 'max:100'],
            'bank_address' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->payments->saveBank((int) $user->user_id, $validator->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'Bank details saved successfully',
            'data' => $this->paymentPayload((int) $user->user_id),
        ]);
    }

    public function updatePaypal(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $validator = Validator::make($request->all(), [
            'paypal_email' => ['required', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $this->payments->savePaypal(
                (int) $user->user_id,
                $validator->validated()['paypal_email']
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => 'PayPal details saved successfully',
            'data' => $this->paymentPayload((int) $user->user_id),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function paymentPayload(int $userId): array
    {
        return array_merge($this->payments->index($userId), ['enabled' => true]);
    }
}
