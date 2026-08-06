<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Ensure admin reported-issues pages have legacy + demo rows.
 */
class ReportedIssuesDemoSeeder extends Seeder
{
    private const STATUS_PROGRESS = 1;

    private const STATUS_RESOLVED = 2;

    private const STATUS_ESCALATED = 3;

    private const STATUS_CLOSED = 4;

    private const TYPE_LESSON = 1;

    private const TYPE_GCLASS = 2;

    public function run(): void
    {
        $lesson = DB::table('tbl_order_lessons')
            ->where('ordles_id', '>', 0)
            ->orderBy('ordles_id')
            ->first(['ordles_id', 'ordles_order_id']);

        $class = DB::table('tbl_order_classes as oc')
            ->join('tbl_group_classes as g', 'g.grpcls_id', '=', 'oc.ordcls_grpcls_id')
            ->orderBy('oc.ordcls_id')
            ->first(['oc.ordcls_id', 'oc.ordcls_order_id']);

        $learners = DB::table('tbl_users')
            ->whereNull('user_deleted')
            ->where('user_is_teacher', 0)
            ->orderBy('user_id')
            ->limit(5)
            ->pluck('user_id')
            ->all();

        if (! $lesson || ! $class || $learners === []) {
            $this->command?->warn('Skipping reported issues demo seed — missing lesson/class/learner records.');

            return;
        }

        $issues = [
            [
                'repiss_id' => 1,
                'repiss_title' => 'Teacher was absent',
                'repiss_record_id' => 84,
                'repiss_record_type' => self::TYPE_LESSON,
                'repiss_reported_on' => '2024-12-29 21:57:05',
                'repiss_reported_by' => 17,
                'repiss_status' => self::STATUS_PROGRESS,
                'repiss_last_action' => null,
                'repiss_comment' => 'The teacher was absent in the class',
                'repiss_updated_on' => null,
            ],
            [
                'repiss_id' => 2,
                'repiss_title' => 'Lesson ended early',
                'repiss_record_id' => (int) $lesson->ordles_id,
                'repiss_record_type' => self::TYPE_LESSON,
                'repiss_reported_on' => '2025-01-12 14:20:00',
                'repiss_reported_by' => (int) $learners[0],
                'repiss_status' => self::STATUS_ESCALATED,
                'repiss_last_action' => null,
                'repiss_comment' => 'The lesson finished 15 minutes before the scheduled end time.',
                'repiss_updated_on' => '2025-01-13 09:00:00',
            ],
            [
                'repiss_id' => 3,
                'repiss_title' => 'Poor audio quality in class',
                'repiss_record_id' => (int) $class->ordcls_id,
                'repiss_record_type' => self::TYPE_GCLASS,
                'repiss_reported_on' => '2025-02-03 18:45:00',
                'repiss_reported_by' => (int) ($learners[1] ?? $learners[0]),
                'repiss_status' => self::STATUS_ESCALATED,
                'repiss_last_action' => null,
                'repiss_comment' => 'Learner could not hear the teacher for most of the session.',
                'repiss_updated_on' => '2025-02-04 10:30:00',
            ],
            [
                'repiss_id' => 4,
                'repiss_title' => 'Teacher joined late',
                'repiss_record_id' => (int) ($lesson->ordles_id + 1),
                'repiss_record_type' => self::TYPE_LESSON,
                'repiss_reported_on' => '2025-03-08 11:10:00',
                'repiss_reported_by' => (int) ($learners[2] ?? $learners[0]),
                'repiss_status' => self::STATUS_RESOLVED,
                'repiss_last_action' => null,
                'repiss_comment' => 'Teacher joined 12 minutes after the lesson start time.',
                'repiss_updated_on' => '2025-03-09 08:15:00',
            ],
            [
                'repiss_id' => 5,
                'repiss_title' => 'Class recording unavailable',
                'repiss_record_id' => (int) ($class->ordcls_id + 1),
                'repiss_record_type' => self::TYPE_GCLASS,
                'repiss_reported_on' => '2025-04-18 16:00:00',
                'repiss_reported_by' => (int) ($learners[3] ?? $learners[0]),
                'repiss_status' => self::STATUS_CLOSED,
                'repiss_last_action' => null,
                'repiss_comment' => 'Requested class recording was never shared after completion.',
                'repiss_updated_on' => '2025-04-20 12:00:00',
            ],
        ];

        foreach ($issues as $issue) {
            $id = (int) $issue['repiss_id'];
            unset($issue['repiss_id']);

            $userExists = DB::table('tbl_users')
                ->where('user_id', (int) $issue['repiss_reported_by'])
                ->whereNull('user_deleted')
                ->exists();

            if (! $userExists) {
                continue;
            }

            if ($issue['repiss_record_type'] === self::TYPE_LESSON) {
                $recordExists = DB::table('tbl_order_lessons')
                    ->where('ordles_id', (int) $issue['repiss_record_id'])
                    ->exists();
            } else {
                $recordExists = DB::table('tbl_order_classes')
                    ->where('ordcls_id', (int) $issue['repiss_record_id'])
                    ->exists();
            }

            if (! $recordExists && $id !== 1) {
                continue;
            }

            $exists = DB::table('tbl_reported_issues')->where('repiss_id', $id)->exists();
            if ($exists) {
                DB::table('tbl_reported_issues')->where('repiss_id', $id)->update($issue);
            } else {
                DB::table('tbl_reported_issues')->insert(array_merge(['repiss_id' => $id], $issue));
            }
        }

        $total = (int) DB::table('tbl_reported_issues')->count();
        $escalated = (int) DB::table('tbl_reported_issues')->where('repiss_status', self::STATUS_ESCALATED)->count();
        $this->command?->info("Reported issues ready: {$total} total, {$escalated} escalated.");
    }
}
