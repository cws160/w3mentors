<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/admin/modules/currency-management', 'GET', ['lang_id' => 1]);
$registry = app(App\Services\Admin\AdminModuleRegistry::class);
$result = $registry->search('currency-management', $request);
echo 'count: ' . count($result['data'] ?? []) . PHP_EOL;
