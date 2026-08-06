<?php

return [
    /** Must match ENCRYPTION_KEY in conf/conf-common.php for dashboard bridge signatures. */
    'dashboard_bridge_key' => env('DASHBOARD_BRIDGE_KEY', env('LEGACY_ENCRYPTION_KEY', 'vt%qkpCDRWB*bq@R&#4e')),

    /** Public origin serving legacy PHP (public/admin-dashboard-bridge.php, dashboard.php). */
    'webroot' => rtrim((string) env('LEGACY_ORIGIN', config('demo.legacy_origin', 'http://127.0.0.1')), '/'),
];
