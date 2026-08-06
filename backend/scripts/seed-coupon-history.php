<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$orders = DB::table('tbl_orders as orders')
    ->join('tbl_users as users', 'users.user_id', '=', 'orders.order_user_id')
    ->orderByDesc('orders.order_id')
    ->get([
        'orders.order_id',
        'orders.order_addedon',
        'orders.order_total_amount',
    ]);

$coupons = DB::table('tbl_coupons')
    ->orderBy('coupon_id')
    ->get([
        'coupon_id',
        'coupon_code',
        'coupon_discount_type',
        'coupon_discount_value',
    ]);

if ($orders->isEmpty() || $coupons->isEmpty()) {
    echo "skipped=missing_orders_or_coupons\n";
    exit(0);
}

$existingPairs = DB::table('tbl_coupons_history')
    ->get(['couhis_order_id', 'couhis_coupon_id'])
    ->map(fn ($row) => $row->couhis_order_id.':'.$row->couhis_coupon_id)
    ->flip();

$historyCounts = DB::table('tbl_coupons_history')
    ->select('couhis_coupon_id', DB::raw('COUNT(*) as total'))
    ->groupBy('couhis_coupon_id')
    ->pluck('total', 'couhis_coupon_id');

$orderCount = $orders->count();
$orderOffset = 0;
$inserted = 0;

foreach ($coupons as $coupon) {
    $couponId = (int) $coupon->coupon_id;
    $currentCount = (int) ($historyCounts[$couponId] ?? 0);
    $targetCount = max(3, $currentCount);
    $needed = max(0, $targetCount - $currentCount);

    if ($needed === 0) {
        continue;
    }

    for ($i = 0; $i < $needed; $i++) {
        $assigned = false;

        for ($attempt = 0; $attempt < $orderCount; $attempt++) {
            $order = $orders[($orderOffset + $attempt) % $orderCount];
            $pairKey = $order->order_id.':'.$couponId;

            if ($existingPairs->has($pairKey)) {
                continue;
            }

            $payload = json_encode([
                'coupon_id' => $couponId,
                'coupon_code' => (string) $coupon->coupon_code,
                'coupon_discount_type' => (int) $coupon->coupon_discount_type,
                'coupon_discount_value' => (string) $coupon->coupon_discount_value,
            ]);

            DB::table('tbl_coupons_history')->insert([
                'couhis_order_id' => (int) $order->order_id,
                'couhis_coupon_id' => $couponId,
                'couhis_coupon' => $payload,
                'couhis_created' => (string) ($order->order_addedon ?: now()->subDays($attempt + 1)->format('Y-m-d H:i:s')),
                'couhis_released' => ($inserted + $i) % 5 === 0
                    ? now()->subDays(1)->format('Y-m-d H:i:s')
                    : null,
            ]);

            $existingPairs->put($pairKey, true);
            $orderOffset = ($orderOffset + $attempt + 1) % $orderCount;
            $inserted++;
            $assigned = true;
            break;
        }

        if (! $assigned) {
            break;
        }
    }
}

echo "history_inserted={$inserted}\n";
echo 'history_total='.DB::table('tbl_coupons_history')->count()."\n";
echo 'coupon_47='.DB::table('tbl_coupons_history')->where('couhis_coupon_id', 47)->count()."\n";
