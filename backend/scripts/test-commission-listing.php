<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/admin/modules/commission', 'GET', ['lang_id' => 1]);
$registry = app(App\Services\Admin\AdminModuleRegistry::class);
$result = $registry->search('commission', $request);
echo json_encode($result, JSON_PRETTY_PRINT);
