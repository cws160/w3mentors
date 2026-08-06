<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$data = app(App\Services\Admin\AdminSitemapViewService::class)->xmlIndex();
echo 'content_bytes='.strlen($data['content']).PHP_EOL;
echo 'files='.count($data['files']).PHP_EOL;
echo substr($data['content'], 0, 120).PHP_EOL;
