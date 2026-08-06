<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Admin\AdminModuleRegistry;
use Illuminate\Http\Request;

$registry = app(AdminModuleRegistry::class);
$request = Request::create('/api/v1/admin/modules/coupons', 'GET', ['lang_id' => 1, 'page' => 1]);
$result = $registry->search('coupons', $request);

echo 'Total: '.($result['meta']['total'] ?? 0).PHP_EOL;
foreach (($result['data'] ?? []) as $row) {
    echo sprintf(
        "%d | %s | %s | expired=%s | %s\n",
        $row['id'],
        $row['coupon_code'],
        $row['available'],
        !empty($row['is_expired']) ? 'yes' : 'no',
        $row['coupon_active'] ? 'Active' : 'Inactive',
    );
}
