<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$data = app(App\Services\Admin\AdminSitemapHtmlService::class)->sections(1);
echo 'groups='.count($data['sections'][0]['groups'] ?? []).PHP_EOL;
foreach ($data['sections'][0]['groups'] ?? [] as $group) {
    echo $group['title'].': '.count($group['links']).PHP_EOL;
}
