<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$rows = DB::table('tbl_affiliate_commissions as afcomm')
    ->leftJoin('tbl_users as user', 'afcomm.afcomm_user_id', '=', 'user.user_id')
    ->select([
        'afcomm.afcomm_id',
        'afcomm.afcomm_commission',
        'afcomm.afcomm_user_id',
        'user.user_id',
        'user.user_first_name',
        'user.user_last_name',
    ])
    ->get();

echo 'count='.$rows->count()."\n";
foreach ($rows as $row) {
    $name = $row->afcomm_user_id ? trim($row->user_first_name.' '.$row->user_last_name) : 'GLOBAL';
    echo "{$row->afcomm_id} | {$name} | {$row->afcomm_commission}\n";
}

$affiliates = DB::table('tbl_users')
    ->where('user_is_affiliate', 1)
    ->whereNull('user_deleted')
    ->limit(5)
    ->get(['user_id', 'user_first_name', 'user_last_name']);
echo 'affiliates='.$affiliates->count()."\n";
