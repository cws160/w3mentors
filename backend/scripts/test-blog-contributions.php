<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/admin/modules/blog-contributions', 'GET', ['page' => 1]);
$result = app(App\Services\Admin\Listings\AdminBlogContributionsListingService::class)->search($request);

echo 'count='.count($result['data']).' total='.$result['meta']['total'].PHP_EOL;
foreach (array_slice($result['data'], 0, 3) as $row) {
    echo $row['id'].' | '.$row['author_name'].' | '.$row['status'].PHP_EOL;
}
