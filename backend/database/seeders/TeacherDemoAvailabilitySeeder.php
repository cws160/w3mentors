<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds upcoming weekly availability for active teachers (demo / local dev).
 * Run: php artisan db:seed --class=TeacherDemoAvailabilitySeeder
 */
class TeacherDemoAvailabilitySeeder extends Seeder
{
    private const WEEKS_AHEAD = 8;

    private const LESSON_SLOTS_JSON = '["15","30","60"]';

    public function run(): void
    {
        $teacherIds = DB::table('tbl_users')
            ->where('user_is_teacher', 1)
            ->where('user_active', 1)
            ->whereNotNull('user_verified')
            ->whereNull('user_deleted')
            ->orderBy('user_id')
            ->limit(25)
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($teacherIds === []) {
            $this->command?->warn('No active teachers found — skip availability seed.');

            return;
        }

        $rangeStart = Carbon::today('UTC');
        $rangeEnd = $rangeStart->copy()->addWeeks(self::WEEKS_AHEAD);
        $cutoff = $rangeStart->format('Y-m-d 00:00:00');

        $totalRows = 0;

        foreach ($teacherIds as $teacherId) {
            $this->ensureTeacherSettings($teacherId);

            DB::table('tbl_availability')
                ->where('avail_user_id', $teacherId)
                ->where('avail_starttime', '>=', $cutoff)
                ->delete();

            $rows = [];
            for ($day = $rangeStart->copy(); $day->lt($rangeEnd); $day->addDay()) {
                $date = $day->format('Y-m-d');
                $rows[] = [
                    'avail_user_id' => $teacherId,
                    'avail_starttime' => $date.' 06:00:00',
                    'avail_endtime' => $date.' 20:00:00',
                ];
            }

            foreach (array_chunk($rows, 50) as $chunk) {
                DB::table('tbl_availability')->insert($chunk);
            }

            $totalRows += count($rows);
        }

        $this->command?->info(sprintf(
            'Seeded %d availability day(s) for %d teacher(s) (%s → %s UTC).',
            $totalRows,
            count($teacherIds),
            $rangeStart->toDateString(),
            $rangeEnd->copy()->subDay()->toDateString()
        ));
    }

    private function ensureTeacherSettings(int $teacherId): void
    {
        $settings = DB::table('tbl_user_settings')->where('user_id', $teacherId)->first();

        if (!$settings) {
            return;
        }

        $updates = [];
        if ($settings->user_book_before === null || (int) $settings->user_book_before > 2) {
            $updates['user_book_before'] = 0;
        }
        if (empty($settings->user_slots)) {
            $updates['user_slots'] = self::LESSON_SLOTS_JSON;
        }

        if ($updates !== []) {
            DB::table('tbl_user_settings')->where('user_id', $teacherId)->update($updates);
        }
    }
}
