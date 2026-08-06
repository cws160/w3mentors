<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$content = app(App\Services\Admin\AdminRobotsTxtService::class)->content();
echo 'bytes='.strlen($content).PHP_EOL;
echo substr($content, 0, 120).PHP_EOL;
