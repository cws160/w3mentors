<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AffiliateRegistrationService;
use App\Services\ForgotPasswordService;
use App\Services\LegacyPasswordHasher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::active()
            ->where('user_email', $request->string('email'))
            ->first();

        if (!$user || !$user->validateLegacyPassword($request->string('password'))) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->user_verified) {
            return response()->json(['message' => 'Email verification pending'], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->resolve($request),
        ]);
    }

    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:tbl_users,user_email'],
            'password' => ['required', 'string', 'min:6', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'lang_id' => ['nullable', 'integer'],
            'currency_id' => ['nullable', 'integer'],
            'country_id' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'user_first_name' => $request->string('first_name'),
            'user_last_name' => $request->input('last_name'),
            'user_email' => $request->string('email'),
            'user_username' => str_replace(' ', '_', $request->string('first_name')) . '_' . time(),
            'user_password' => LegacyPasswordHasher::hash($request->string('password')),
            'user_timezone' => $request->input('timezone', 'UTC'),
            'user_lang_id' => $request->integer('lang_id', 1),
            'user_currency_id' => $request->integer('currency_id', 1),
            'user_country_id' => $request->integer('country_id', 1),
            'user_is_teacher' => 0,
            'user_is_affiliate' => 0,
            'user_active' => 1,
            'user_verified' => now(),
            'user_created' => now(),
        ]);

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->resolve($request),
        ], 201);
    }

    public function affiliateRegister(Request $request, AffiliateRegistrationService $affiliates): JsonResponse
    {
        if (!$affiliates->isEnabled()) {
            return response()->json(['message' => 'Affiliate module is not enabled'], 403);
        }

        try {
            $payload = $affiliates->register($request);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation failed', 'errors' => $e->errors()], 422);
        }

        return response()->json($payload, 201);
    }

    public function forgotPassword(Request $request, ForgotPasswordService $forgotPassword): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $message = $forgotPassword->requestPasswordReset(
                $request->string('email'),
                $request->integer('lang_id', 1)
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => $message]);
    }

    public function validateResetPassword(
        ForgotPasswordService $forgotPassword,
        int $userId,
        string $token
    ): JsonResponse {
        try {
            $forgotPassword->validateResetLink($userId, $token);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage(), 'valid' => false], 422);
        }

        return response()->json(['valid' => true]);
    }

    public function resetPassword(Request $request, ForgotPasswordService $forgotPassword): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'min:1'],
            'token' => ['required', 'string'],
            'new_password' => ['required', 'string'],
            'confirm_password' => ['required', 'string'],
        ]);

        try {
            $message = $forgotPassword->resetPassword(
                $request->integer('user_id'),
                $request->string('token'),
                $request->string('new_password'),
                $request->string('confirm_password'),
                $request->integer('lang_id', 1)
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => $message]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => (new UserResource($request->user()))->resolve($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logged out']);
    }
}
