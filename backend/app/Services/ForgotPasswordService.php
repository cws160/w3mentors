<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\User;
use App\Services\LegacyPasswordHasher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordService
{
    /**
     * @throws \InvalidArgumentException
     */
    public function requestPasswordReset(string $email, int $langId = 1): string
    {
        $email = strtolower(trim($email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Please enter a valid email address.');
        }

        $user = User::query()
            ->where('user_email', $email)
            ->whereNull('user_deleted')
            ->first();

        if (! $user) {
            throw new \InvalidArgumentException('Please enter a registered email.');
        }

        if (! $user->user_verified) {
            throw new \InvalidArgumentException('Email verification is pending. Please verify your account first.');
        }

        $pending = DB::table('tbl_user_password_reset_requests')
            ->where('uprr_user_id', $user->user_id)
            ->where('uprr_expiry', '>', now())
            ->exists();

        if ($pending) {
            throw new \InvalidArgumentException('A password reset request has already been placed.');
        }

        $token = uniqid('Y', true);
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');
        $resetUrl = "{$frontendUrl}/guest-user/reset-password/{$user->user_id}/".urlencode($token);
        $fullName = trim($user->user_first_name.' '.($user->user_last_name ?? ''));

        DB::transaction(function () use ($user, $token, $fullName, $resetUrl, $langId) {
            DB::table('tbl_user_password_reset_requests')->insert([
                'uprr_user_id' => $user->user_id,
                'uprr_token' => $token,
                'uprr_expiry' => now()->addDay(),
            ]);

            DB::table('tbl_user_auth_token')->where('usrtok_user_id', $user->user_id)->delete();

            $this->sendResetEmail($user->user_email, $fullName, $resetUrl, $langId);
        });

        return 'Password reset instructions have been sent to your email.';
    }

    private function sendResetEmail(string $to, string $fullName, string $resetUrl, int $langId): void
    {
        $template = DB::table('tbl_email_templates')
            ->where('etpl_code', 'forgot_password')
            ->where('etpl_lang_id', $langId)
            ->where('etpl_status', 1)
            ->first(['etpl_subject', 'etpl_body']);

        $siteName = (string) Configuration::getValue("CONF_WEBSITE_NAME_{$langId}", 'w3mentors');
        $fromEmail = (string) Configuration::getValue('CONF_FROM_EMAIL', config('mail.from.address', 'noreply@localhost'));
        $fromName = (string) Configuration::getValue('CONF_FROM_NAME', $siteName);

        $subject = $template->etpl_subject ?? 'Password reset instructions';
        $body = $template->etpl_body ?? '<p>Click <a href="{reset_url}">here</a> to reset your password.</p>';

        $replacements = [
            '{user_full_name}' => $fullName,
            '{reset_url}' => $resetUrl,
            '{website_name}' => $siteName,
            '{primary-color}' => '#0c9331',
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $subject);
        $body = str_replace(array_keys($replacements), array_values($replacements), $body);

        try {
            Mail::html($body, function ($message) use ($to, $subject, $fromEmail, $fromName) {
                $message->to($to)->subject($subject);
                if ($fromEmail !== '') {
                    $message->from($fromEmail, $fromName !== '' ? $fromName : null);
                }
            });
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('Something went wrong. Please try again.');
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function validateResetLink(int $userId, string $token): void
    {
        if (! $this->resetLinkIsValid($userId, $token)) {
            throw new \InvalidArgumentException('Invalid or expired link.');
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function resetPassword(
        int $userId,
        string $token,
        string $password,
        string $confirmPassword,
        int $langId = 1
    ): string {
        if ($password !== $confirmPassword) {
            throw new \InvalidArgumentException('Passwords do not match.');
        }

        if (! preg_match('/^(?=.*\d)(?=.*[A-Za-z])[0-9A-Za-z!@#$%-_]{8,15}$/', $password)) {
            throw new \InvalidArgumentException('Password must be 8-15 characters and include letters and numbers.');
        }

        if (! $this->resetLinkIsValid($userId, $token)) {
            throw new \InvalidArgumentException('Invalid or expired link.');
        }

        $user = User::query()->where('user_id', $userId)->whereNull('user_deleted')->first();
        if (! $user) {
            throw new \InvalidArgumentException('Invalid or expired link.');
        }

        DB::transaction(function () use ($user, $userId, $password, $langId) {
            $user->user_password = LegacyPasswordHasher::hash($password);
            $user->save();

            DB::table('tbl_user_password_reset_requests')->where('uprr_user_id', $userId)->delete();

            $this->sendPasswordChangedEmail($user, $langId);
        });

        return 'Password changed successfully.';
    }

    private function resetLinkIsValid(int $userId, string $token): bool
    {
        if ($userId < 1 || $token === '') {
            return false;
        }

        return DB::table('tbl_user_password_reset_requests')
            ->where('uprr_user_id', $userId)
            ->where('uprr_token', $token)
            ->where('uprr_expiry', '>', now())
            ->exists();
    }

    private function sendPasswordChangedEmail(User $user, int $langId): void
    {
        $template = DB::table('tbl_email_templates')
            ->where('etpl_code', 'password_changed_successfully')
            ->where('etpl_lang_id', $langId)
            ->where('etpl_status', 1)
            ->first(['etpl_subject', 'etpl_body']);

        if (! $template) {
            return;
        }

        $siteName = (string) Configuration::getValue("CONF_WEBSITE_NAME_{$langId}", 'w3mentors');
        $fromEmail = (string) Configuration::getValue('CONF_FROM_EMAIL', config('mail.from.address', 'noreply@localhost'));
        $fromName = (string) Configuration::getValue('CONF_FROM_NAME', $siteName);
        $frontendUrl = rtrim((string) config('app.frontend_url', 'http://localhost:5173'), '/');
        $fullName = trim($user->user_first_name.' '.($user->user_last_name ?? ''));

        $replacements = [
            '{user_full_name}' => $fullName,
            '{login_link}' => "{$frontendUrl}/login",
            '{website_name}' => $siteName,
            '{primary-color}' => '#0c9331',
        ];

        $subject = str_replace(array_keys($replacements), array_values($replacements), $template->etpl_subject);
        $body = str_replace(array_keys($replacements), array_values($replacements), $template->etpl_body);

        try {
            Mail::html($body, function ($message) use ($user, $subject, $fromEmail, $fromName) {
                $message->to($user->user_email)->subject($subject);
                if ($fromEmail !== '') {
                    $message->from($fromEmail, $fromName !== '' ? $fromName : null);
                }
            });
        } catch (\Throwable) {
            // Non-blocking — password was already updated.
        }
    }
}
