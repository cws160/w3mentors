<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Admin\Listings\AdminAffiliateCommissionListingService;
use Illuminate\Http\Request;

$request = Request::create('/api/v1/admin/modules/affiliate-commission', 'GET', ['page' => 1]);
$result = app(AdminAffiliateCommissionListingService::class)->search($request);

echo 'count='.count($result['data']).' total='.$result['meta']['total']."\n";
foreach (array_slice($result['data'], 0, 5) as $row) {
    $name = $row['is_global'] ? 'GLOBAL' : $row['affiliate_name'];
    echo "{$row['id']} | {$name} | {$row['commission']}\n";
}
