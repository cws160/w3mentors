<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserAccountSecurityService
{
    private const PASSWORD_REGEX = '/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%-_]{8,15}$/';

    private const EMAIL_CHANGE_TYPE = 2;

    public function changePassword(User $user, string $currentPassword, string $newPassword, string $confirmPassword): void
    {
        if ($newPassword !== $confirmPassword) {
            throw new \InvalidArgumentException('New password and confirm password do not match.');
        }

        if (! preg_match(self::PASSWORD_REGEX, $newPassword)) {
            throw new \InvalidArgumentException(
                'Password must be 8–15 characters and include letters and numbers.'
            );
        }

        if (! $user->validateLegacyPassword($currentPassword)) {
            throw new \InvalidArgumentException('Your current password does not match.');
        }

        $user->user_password = LegacyPasswordHasher::hash($newPassword);
        $user->save();
    }

    public function requestEmailChange(User $user, string $newEmail, string $currentPassword): string
    {
        $newEmail = trim($newEmail);
        if (! filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Please enter a valid email address.');
        }

        if (strcasecmp($newEmail, (string) $user->user_email) === 0) {
            throw new \InvalidArgumentException('New email must be different from your current email.');
        }

        if (! $user->validateLegacyPassword($currentPassword)) {
            throw new \InvalidArgumentException('Your current password does not match.');
        }

        $exists = User::query()
            ->where('user_email', $newEmail)
            ->where('user_id', '!=', $user->user_id)
            ->exists();

        if ($exists) {
            throw new \InvalidArgumentException('This email is already registered.');
        }

        DB::transaction(function () use ($user, $newEmail) {
            DB::table('tbl_user_verifications')
                ->where('usrver_user_id', $user->user_id)
                ->delete();

            $token = $user->user_id.'_'.Str::random(15);
            DB::table('tbl_user_verifications')->insert([
                'usrver_user_id' => $user->user_id,
                'usrver_email' => $newEmail,
                'usrver_token' => $token,
                'usrver_type' => self::EMAIL_CHANGE_TYPE,
                'usrver_expire' => now()->addWeek()->format('Y-m-d H:i:s'),
                'usrver_created' => now()->format('Y-m-d H:i:s'),
            ]);
        });

        return 'A verification link has been sent to your new email address.';
    }
}
