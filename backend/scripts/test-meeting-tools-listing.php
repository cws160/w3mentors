<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Admin\AdminModuleRegistry;
use Illuminate\Http\Request;

$registry = app(AdminModuleRegistry::class);
$request = Request::create('/api/v1/admin/modules/meeting-tools', 'GET', ['lang_id' => 1, 'page' => 1]);
$result = $registry->search('meeting-tools', $request);

echo 'Total: '.($result['meta']['total'] ?? 0).PHP_EOL;
foreach (($result['data'] ?? []) as $row) {
    echo sprintf(
        "%d | %s | status=%s | toggle=%s\n",
        $row['id'],
        $row['code'],
        $row['status'],
        $row['can_toggle_status'] ? 'yes' : 'no',
    );
}
