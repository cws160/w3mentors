<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$count = (int) DB::table('tbl_blog_contributions')->count();
echo "existing_contributions={$count}\n";

if ($count > 0) {
    echo "skip: contributions already exist\n";
    exit(0);
}

$now = now()->format('Y-m-d H:i:s');
$samples = [
    ['Sarah', 'Miller', 'sarah.miller@example.com', '+1-555-0101', 0],
    ['David', 'Patel', 'david.patel@example.com', '+1-555-0102', 1],
    ['Maria', 'Lopez', 'maria.lopez@example.com', '+1-555-0103', 2],
    ['James', 'Wilson', 'james.wilson@example.com', '+1-555-0104', 3],
    ['Priya', 'Sharma', 'priya.sharma@example.com', '+1-555-0105', 0],
];

foreach ($samples as [$first, $last, $email, $phone, $status]) {
    DB::table('tbl_blog_contributions')->insert([
        'bcontributions_author_first_name' => $first,
        'bcontributions_author_last_name' => $last,
        'bcontributions_author_email' => $email,
        'bcontributions_author_phone' => $phone,
        'bcontributions_status' => $status,
        'bcontributions_added_on' => $now,
        'bcontributions_user_id' => 0,
    ]);
}

echo 'inserted='.count($samples)."\n";
