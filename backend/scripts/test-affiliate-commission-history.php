<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\Admin\AdminAffiliateCommissionManageService;
use Illuminate\Support\Facades\DB;

$histCount = DB::table('tbl_affiliate_commission_history')->count();
echo "history_total={$histCount}\n";

$rows = DB::table('tbl_affiliate_commission_history')->orderBy('afcomhis_id')->get();
foreach ($rows as $row) {
    $uid = $row->afcomhis_user_id === null ? 'GLOBAL' : $row->afcomhis_user_id;
    echo "{$row->afcomhis_id} | user={$uid} | {$row->afcomhis_commission} @ {$row->afcomhis_created}\n";
}

$svc = app(AdminAffiliateCommissionManageService::class);
echo 'api_global='.count($svc->history(0))."\n";

$affiliate = DB::table('tbl_affiliate_commissions')->whereNotNull('afcomm_user_id')->first();
if ($affiliate) {
    echo "affiliate_user_id={$affiliate->afcomm_user_id} history=".count($svc->history((int) $affiliate->afcomm_user_id))."\n";
}
