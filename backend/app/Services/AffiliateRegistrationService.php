<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\Configuration;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AffiliateRegistrationService
{
    private const AFFILIATE_DASHBOARD = 5;

    public function isEnabled(): bool
    {
        return (int) Configuration::getValue('CONF_ENABLE_AFFILIATE_MODULE', 0) === 1;
    }

    /**
     * @return array{token: string, token_type: string, user: array<string, mixed>, message: string}
     */
    public function register(Request $request): array
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:tbl_users,user_email'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/'],
            'timezone' => ['nullable', 'string', 'timezone:all'],
            'agree' => ['accepted'],
        ]);

        $email = strtolower(trim((string) $validated['email']));
        $now = now();
        $langId = $request->integer('lang_id', 1);
        $currencyId = $request->integer('currency_id', 1);
        $timezone = (string) ($validated['timezone'] ?? Configuration::getValue('CONF_TIMEZONE', 'UTC'));

        $userId = DB::transaction(function () use ($validated, $email, $now, $langId, $currencyId, $timezone) {
            $user = User::create([
                'user_first_name' => $validated['first_name'],
                'user_last_name' => $validated['last_name'] ?? '',
                'user_email' => $email,
                'user_username' => Str::slug($validated['first_name'] ?: 'affiliate').'_'.time(),
                'user_password' => LegacyPasswordHasher::hash($validated['password']),
                'user_timezone' => $timezone,
                'user_lang_id' => $langId,
                'user_currency_id' => $currencyId,
                'user_country_id' => 1,
                'user_is_teacher' => 0,
                'user_is_affiliate' => 1,
                'user_active' => 1,
                'user_verified' => $now,
                'user_created' => $now,
            ]);

            DB::table('tbl_user_settings')->insert([
                'user_id' => $user->user_id,
                'user_dashboard' => self::AFFILIATE_DASHBOARD,
                'user_registered_as' => self::AFFILIATE_DASHBOARD,
                'user_trial_enabled' => 0,
                'user_book_before' => 0,
                'user_wallet_balance' => 0,
                'user_apple_id' => '',
                'user_apple_token' => '',
                'user_device_token' => '',
                'user_zoom_status' => 0,
                'user_autorenew_subscription' => 0,
                'user_reward_points' => 0,
                'user_referral_code' => uniqid(),
            ]);

            return (int) $user->user_id;
        });

        $user = User::query()->findOrFail($userId);
        $token = $user->createToken('api')->plainTextToken;

        return [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => (new UserResource($user))->resolve($request),
            'message' => 'Registration successful',
        ];
    }
}
