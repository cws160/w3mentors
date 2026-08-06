<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Dev fallback when /admin-dashboard-bridge.php is requested on the Laravel host (:8000).
 * Production Apache/IIS should serve public/admin-dashboard-bridge.php on LEGACY_ORIGIN instead.
 */
class LegacyDashboardBridgeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $userId = $request->integer('user_id', 0);
        $courseId = $request->integer('course_id', 0);
        $expires = $request->integer('exp', 0);
        $signature = (string) $request->query('sig', '');

        if ($userId < 1 || $courseId < 1 || $expires < 1 || $signature === '') {
            abort(400, 'Invalid request');
        }

        if ($expires < time()) {
            abort(403, 'Link expired');
        }

        $key = (string) config('legacy.dashboard_bridge_key');
        $payload = "{$userId}:{$courseId}:{$expires}";
        $expected = hash_hmac('sha256', $payload, $key);

        if (! hash_equals($expected, $signature)) {
            abort(403, 'Invalid signature');
        }

        $legacyOrigin = rtrim((string) config('legacy.webroot'), '/');
        if ($legacyOrigin !== '' && ! $this->isCurrentOrigin($request, $legacyOrigin)) {
            $query = http_build_query($request->query());
            return redirect("{$legacyOrigin}/admin-dashboard-bridge.php?{$query}");
        }

        $user = DB::table('tbl_users')
            ->where('user_id', $userId)
            ->whereNull('user_deleted')
            ->where('user_active', 1)
            ->first([
                'user_id',
                'user_email',
                'user_username',
                'user_first_name',
                'user_last_name',
                'user_is_teacher',
                'user_is_affiliate',
            ]);

        if (! $user) {
            abort(404, 'User not found');
        }

        $cookiePath = '/';
        $cookieParams = [
            'httponly' => true,
            'secure' => $request->isSecure(),
            'path' => $cookiePath,
            'samesite' => 'Lax',
        ];
        if (PHP_VERSION_ID >= 70300) {
            session_set_cookie_params($cookieParams);
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $_SESSION = [];
        $_SESSION['APP_SESSION'] = [
            'user_ip' => $request->ip() ?? '127.0.0.1',
            'user_id' => (int) $user->user_id,
            'user_email' => $user->user_email,
            'user_username' => $user->user_username ?? '',
            'user_first_name' => $user->user_first_name,
            'user_last_name' => $user->user_last_name,
            'user_token' => '',
        ];
        $_SESSION['ADMIN_SESSION_ELEMENT'] = ['LOGGED' => 1];

        $userType = 1;
        if ((int) ($user->user_is_teacher ?? 0) === 1) {
            $userType = 2;
        } elseif ((int) ($user->user_is_affiliate ?? 0) === 1) {
            $userType = 5;
        }
        $_SESSION['SITE_USER_TYPE'] = $userType;

        return redirect("/dashboard/course-preview/index/{$courseId}");
    }

    private function isCurrentOrigin(Request $request, string $legacyOrigin): bool
    {
        $current = $request->getSchemeAndHttpHost();

        return rtrim($current, '/') === rtrim($legacyOrigin, '/');
    }
}
