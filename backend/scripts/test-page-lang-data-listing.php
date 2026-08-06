<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Admin\Listings\AdminPageLangDataListingService;
use Illuminate\Http\Request;

$request = Request::create('/api/v1/admin/modules/page-lang-data', 'GET', ['page' => 1]);
$result = app(AdminPageLangDataListingService::class)->search($request);

echo 'count='.count($result['data']).' total='.$result['meta']['total']."\n";
foreach (array_slice($result['data'], 0, 5) as $row) {
    echo "{$row['id']} | {$row['page_key']} | {$row['title']}\n";
}
