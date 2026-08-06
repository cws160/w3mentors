<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$global = DB::table('tbl_affiliate_commissions')->whereNull('afcomm_user_id')->first();
if (! $global) {
    $now = now()->format('Y-m-d H:i:s');
    DB::table('tbl_affiliate_commissions')->insert([
        'afcomm_user_id' => null,
        'afcomm_commission' => 10.00,
        'afcomm_created' => $now,
    ]);
    DB::table('tbl_affiliate_commission_history')->insert([
        'afcomhis_user_id' => null,
        'afcomhis_commission' => 10.00,
        'afcomhis_created' => $now,
    ]);
    echo "inserted_global=1\n";
} else {
    echo "global_exists=1\n";
    $globalHistory = (int) DB::table('tbl_affiliate_commission_history')->whereNull('afcomhis_user_id')->count();
    if ($globalHistory < 1) {
        DB::table('tbl_affiliate_commission_history')->insert([
            'afcomhis_user_id' => null,
            'afcomhis_commission' => (float) ($global->afcomm_commission ?? 10),
            'afcomhis_created' => now()->format('Y-m-d H:i:s'),
        ]);
        echo "global_history_inserted=1\n";
    }
}

$affiliates = DB::table('tbl_users as user')
    ->leftJoin('tbl_affiliate_commissions as afcomm', 'user.user_id', '=', 'afcomm.afcomm_user_id')
    ->where('user.user_is_affiliate', 1)
    ->whereNull('user.user_deleted')
    ->whereNull('afcomm.afcomm_user_id')
    ->orderBy('user.user_id')
    ->limit(3)
    ->get(['user.user_id']);

$rates = [12.5, 15.0, 8.75];
$inserted = 0;
$now = now()->format('Y-m-d H:i:s');

foreach ($affiliates as $index => $affiliate) {
    $rate = $rates[$index] ?? 10.0;
    DB::table('tbl_affiliate_commissions')->insert([
        'afcomm_user_id' => $affiliate->user_id,
        'afcomm_commission' => $rate,
        'afcomm_created' => $now,
    ]);
    DB::table('tbl_affiliate_commission_history')->insert([
        'afcomhis_user_id' => $affiliate->user_id,
        'afcomhis_commission' => $rate,
        'afcomhis_created' => $now,
    ]);
    $inserted++;
}

echo "affiliate_commissions_inserted={$inserted}\n";
