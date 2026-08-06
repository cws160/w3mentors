<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    $count = DB::table('tbl_currencies')->count();
    echo "count: $count\n";
    $rows = DB::table('tbl_currencies')->limit(2)->get();
    echo json_encode($rows, JSON_PRETTY_PRINT);
} catch (Throwable $e) {
    echo $e->getMessage();
}
