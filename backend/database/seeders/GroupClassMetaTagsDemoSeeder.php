<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Legacy meta-tags > Group classes only lists upcoming scheduled classes.
 * Demo imports often keep past start dates; roll a batch forward when none are upcoming.
 */
class GroupClassMetaTagsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $hasUpcoming = DB::table('tbl_group_classes')
            ->where('grpcls_status', 1)
            ->where('grpcls_parent', 0)
            ->where('grpcls_start_datetime', '>', now())
            ->exists();

        if ($hasUpcoming) {
            return;
        }

        $classes = DB::table('tbl_group_classes')
            ->join('tbl_users as u', 'u.user_id', '=', 'tbl_group_classes.grpcls_teacher_id')
            ->where('grpcls_status', 1)
            ->where('grpcls_parent', 0)
            ->whereNull('u.user_deleted')
            ->where('u.user_active', 1)
            ->where('u.user_is_teacher', 1)
            ->orderByDesc('grpcls_id')
            ->limit(30)
            ->get(['grpcls_id', 'grpcls_duration']);

        foreach ($classes as $index => $class) {
            $daysAhead = ($index % 28) + 1;
            $start = now()
                ->addDays($daysAhead)
                ->setTime(9 + ($index % 9), ($index * 11) % 60, 0);
            $end = (clone $start)->addMinutes(max(30, (int) $class->grpcls_duration));

            DB::table('tbl_group_classes')
                ->where('grpcls_id', $class->grpcls_id)
                ->update([
                    'grpcls_start_datetime' => $start,
                    'grpcls_end_datetime' => $end,
                ]);
        }
    }
}
