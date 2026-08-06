<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/admin/modules/blog-post-categories', 'GET', ['parent_id' => 0, 'lang_id' => 1]);
$result = app(App\Services\Admin\Listings\AdminBlogPostCategoriesListingService::class)->search($request);

echo 'count='.count($result['data']).PHP_EOL;
foreach ($result['data'] as $row) {
    echo $row['id'].' | '.$row['title'].' | '.$row['identifier'].' | '.$row['active'].PHP_EOL;
}
