<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Align lesson-stats log demo rows with legacy W3Mentors admin screenshots.
 */
class LessonStatsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $jacklyn = DB::table('tbl_users')
            ->where('user_first_name', 'Jacklyn')
            ->where('user_last_name', 'Reichel')
            ->first(['user_id']);

        if (! $jacklyn) {
            $this->command?->warn('Skipping lesson stats demo seed — Jacklyn Reichel not found.');

            return;
        }

        $userId = (int) $jacklyn->user_id;

        // Rescheduled log — matches legacy screenshot (Prev timings, Action by, O-id, Lesson ID).
        DB::table('tbl_session_logs')
            ->where('sesslog_id', 18)
            ->update([
                'sesslog_user_id' => $userId,
                'sesslog_prev_status' => 2,
                'sesslog_changed_status' => 2,
                'sesslog_prev_starttime' => '2022-09-23 08:15:00',
                'sesslog_prev_endtime' => '2022-09-23 09:15:00',
                'sesslog_changed_starttime' => '2022-09-23 10:15:00',
                'sesslog_changed_endtime' => '2022-09-23 11:15:00',
                'sesslog_comment' => 'bb',
                'sesslog_created' => '2022-09-22 03:01:00',
            ]);

        $teacherId = DB::table('tbl_order_lessons')
            ->where('ordles_id', 28)
            ->value('ordles_teacher_id');

        if ($teacherId) {
            DB::table('tbl_users')
                ->where('user_id', $teacherId)
                ->update(['user_deleted' => now()]);
        }

        // Cancelled logs — keep legacy sample.sql reasons/dates for Jacklyn.
        DB::table('tbl_session_logs')
            ->whereIn('sesslog_id', [102, 103])
            ->update([
                'sesslog_user_id' => $userId,
                'sesslog_prev_status' => 2,
                'sesslog_changed_status' => 4,
                'sesslog_prev_starttime' => '2024-11-10 08:00:00',
                'sesslog_prev_endtime' => '2024-11-10 09:00:00',
                'sesslog_created' => '2024-11-12 04:30:36',
            ]);

        DB::table('tbl_session_logs')->where('sesslog_id', 102)->update([
            'sesslog_comment' => 'Not available',
        ]);

        DB::table('tbl_session_logs')->where('sesslog_id', 103)->update([
            'sesslog_comment' => 'Scheduling conflicts',
        ]);

        $this->command?->info("Lesson stats demo data updated for user #{$userId} (Jacklyn Reichel).");
        $this->command?->info('Open Rescheduled sessions: /admin/lesson-stats/'.$userId.'/logs/1');
    }
}
