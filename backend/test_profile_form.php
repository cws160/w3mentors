<?php

require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$userId = (int) ($argv[1] ?? 22);
$service = $app->make(App\Services\UserProfileService::class);

try {
    $data = $service->getGeneralForm($userId, 1, true);
    echo "OK username={$data['values']['username']}\n";
} catch (Throwable $e) {
    echo 'ERR: ' . $e->getMessage() . "\n";
    exit(1);
}
