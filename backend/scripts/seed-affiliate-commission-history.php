<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$globalHistory = (int) DB::table('tbl_affiliate_commission_history')
    ->whereNull('afcomhis_user_id')
    ->count();

if ($globalHistory < 1) {
    $samples = [
        ['commission' => 8.00, 'days_ago' => 120],
        ['commission' => 8.50, 'days_ago' => 90],
        ['commission' => 9.00, 'days_ago' => 60],
        ['commission' => 9.50, 'days_ago' => 30],
        ['commission' => 10.00, 'days_ago' => 0],
    ];
    foreach ($samples as $sample) {
        DB::table('tbl_affiliate_commission_history')->insert([
            'afcomhis_user_id' => null,
            'afcomhis_commission' => $sample['commission'],
            'afcomhis_created' => now()->subDays($sample['days_ago'])->format('Y-m-d H:i:s'),
        ]);
    }
    echo 'global_history_inserted='.count($samples)."\n";
} else {
    echo "global_history_exists={$globalHistory}\n";
}

$commissions = DB::table('tbl_affiliate_commissions')
    ->whereNotNull('afcomm_user_id')
    ->get(['afcomm_user_id', 'afcomm_commission']);

$affiliateHistoryAdded = 0;
foreach ($commissions as $commission) {
    $userId = (int) $commission->afcomm_user_id;
    $count = (int) DB::table('tbl_affiliate_commission_history')
        ->where('afcomhis_user_id', $userId)
        ->count();

    if ($count >= 2) {
        continue;
    }

    $current = (float) $commission->afcomm_commission;
    $older = max(1, round($current - 2.5, 2));

    if ($count === 0) {
        DB::table('tbl_affiliate_commission_history')->insert([
            'afcomhis_user_id' => $userId,
            'afcomhis_commission' => $older,
            'afcomhis_created' => now()->subDays(45)->format('Y-m-d H:i:s'),
        ]);
        DB::table('tbl_affiliate_commission_history')->insert([
            'afcomhis_user_id' => $userId,
            'afcomhis_commission' => $current,
            'afcomhis_created' => now()->subDays(1)->format('Y-m-d H:i:s'),
        ]);
        $affiliateHistoryAdded += 2;
        continue;
    }

    DB::table('tbl_affiliate_commission_history')->insert([
        'afcomhis_user_id' => $userId,
        'afcomhis_commission' => $older,
        'afcomhis_created' => now()->subDays(45)->format('Y-m-d H:i:s'),
    ]);
    $affiliateHistoryAdded++;
}

echo "affiliate_history_inserted={$affiliateHistoryAdded}\n";
echo 'history_total='.DB::table('tbl_affiliate_commission_history')->count()."\n";
