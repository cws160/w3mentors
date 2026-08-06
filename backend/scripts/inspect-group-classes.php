<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$now = now();
echo 'now='.$now.PHP_EOL;

$total = DB::table('tbl_group_classes')->count();
echo "total_group_classes={$total}".PHP_EOL;

$future = DB::table('tbl_group_classes')
    ->where('grpcls_status', 1)
    ->where('grpcls_parent', 0)
    ->where('grpcls_start_datetime', '>', $now)
    ->count();
echo "future_scheduled_parent0={$future}".PHP_EOL;

$futureWithTeacher = DB::table('tbl_group_classes as g')
    ->join('tbl_users as u', 'u.user_id', '=', 'g.grpcls_teacher_id')
    ->where('g.grpcls_status', 1)
    ->where('g.grpcls_parent', 0)
    ->where('g.grpcls_start_datetime', '>', $now)
    ->whereNull('u.user_deleted')
    ->where('u.user_active', 1)
    ->where('u.user_is_teacher', 1)
    ->count();
echo "future_with_active_teacher={$futureWithTeacher}".PHP_EOL;

$sample = DB::table('tbl_group_classes as g')
    ->join('tbl_users as u', 'u.user_id', '=', 'g.grpcls_teacher_id')
    ->where('g.grpcls_status', 1)
    ->where('g.grpcls_parent', 0)
    ->where('g.grpcls_start_datetime', '>', $now)
    ->whereNull('u.user_deleted')
    ->where('u.user_active', 1)
    ->where('u.user_is_teacher', 1)
    ->orderByDesc('g.grpcls_id')
    ->limit(3)
    ->get(['g.grpcls_id', 'g.grpcls_title', 'g.grpcls_start_datetime', 'u.user_first_name', 'u.user_last_name']);

foreach ($sample as $row) {
    echo json_encode($row).PHP_EOL;
}

$conf = DB::table('tbl_configurations')->where('conf_name', 'CONF_GROUP_CLASSES_DISABLED')->value('conf_val');
echo 'CONF_GROUP_CLASSES_DISABLED='.$conf.PHP_EOL;
