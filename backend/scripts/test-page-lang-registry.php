<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Admin\AdminModuleRegistry;
use Illuminate\Http\Request;

$registry = app(AdminModuleRegistry::class);
$request = Request::create('/api/v1/admin/modules/page-lang-data', 'GET', ['page' => 1]);
$result = $registry->search('page-lang-data', $request);

echo 'registry_ok total='.$result['meta']['total']."\n";
