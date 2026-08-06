<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$svc = app(App\Services\Admin\Listings\AdminGenericListingService::class);
$req = Illuminate\Http\Request::create('/admin/modules/url-rewriting', 'GET', ['page' => 1]);
$result = $svc->search('url-rewriting', $req);

echo 'total='.($result['meta']['total'] ?? 0).PHP_EOL;
echo 'count='.count($result['data'] ?? []).PHP_EOL;

foreach (array_slice($result['data'] ?? [], 0, 5) as $r) {
    echo json_encode($r, JSON_UNESCAPED_UNICODE).PHP_EOL;
}
