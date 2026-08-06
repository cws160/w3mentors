<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/admin/modules/themes', 'GET', ['page' => 1]);
$registry = app(App\Services\Admin\AdminModuleRegistry::class);
$result = $registry->search('themes', $request);
echo json_encode([
    'count' => count($result['data'] ?? []),
    'first' => $result['data'][0] ?? null,
    'meta' => $result['meta'] ?? null,
], JSON_PRETTY_PRINT);
