<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/admin/modules/currency-management', 'GET', ['lang_id' => 1]);
try {
    $svc = app(App\Services\Admin\Listings\AdminCurrencyListingService::class);
    $result = $svc->search($request);
    echo json_encode($result, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
