<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CookieConsentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CookieConsentController extends Controller
{
    public function __construct(private CookieConsentService $cookies)
    {
    }

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $payload = $this->cookies->show((int) $user->user_id, $request);

        return response()->json(['data' => $payload]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $validator = Validator::make($request->all(), [
            CookieConsentService::PREFERENCES => ['sometimes', 'boolean'],
            CookieConsentService::STATISTICS => ['sometimes', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        try {
            $settings = $this->cookies->save((int) $user->user_id, $validator->validated());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->jsonWithCookie($settings, 'Cookie settings updated successfully');
    }

    public function acceptAll(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        try {
            $settings = $this->cookies->acceptAll((int) $user->user_id);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->jsonWithCookie($settings, 'Cookie settings updated successfully');
    }

    private function jsonWithCookie(array $settings, string $message): JsonResponse
    {
        $secure = request()->isSecure();

        return response()
            ->json([
                'message' => $message,
                'data' => [
                    'enabled' => $this->cookies->cookiesEnabled(),
                    'settings' => $settings,
                ],
            ])
            ->withCookie($this->cookies->makeConsentCookie($settings, $secure));
    }
}
