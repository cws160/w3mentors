<?php

namespace App\Services;

use App\Support\DashboardDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TeacherAvailabilityCalendarService
{
    private const GENERAL_WEEKSTART = '2018-01-21 00:00:00';

    private const GENERAL_WEEKEND = '2018-01-28 00:00:00';

    public function context(int $userId, string $userTimezone): array
    {
        $tz = $userTimezone !== '' ? $userTimezone : 'UTC';
        $now = DashboardDate::toUser(Carbon::now()->format('Y-m-d H:i:s'), $tz);

        return [
            'current_time' => $now,
            'timezone' => $tz,
            'timezone_offset' => DashboardDate::offset($tz),
        ];
    }

    /**
     * @return array<int, array{start: string, end: string, className: string}>
     */
    public function getGeneral(int $userId, string $userTimezone): array
    {
        $rows = DB::table('tbl_general_availability')
            ->where('gavail_user_id', $userId)
            ->orderBy('gavail_starttime')
            ->get(['gavail_starttime', 'gavail_endtime']);

        return $rows->map(fn ($row) => [
            'start' => DashboardDate::toUser($row->gavail_starttime, $userTimezone),
            'end' => DashboardDate::toUser($row->gavail_endtime, $userTimezone),
            'className' => 'slot_available',
        ])->all();
    }

    /**
     * @return array<int, array{start: string, end: string, className: string}>
     */
    public function getWeekly(int $userId, string $userTimezone, string $start, string $end): array
    {
        $startSys = DashboardDate::toSystem($start, $userTimezone);
        $endSys = DashboardDate::toSystem($end, $userTimezone);

        $rows = DB::table('tbl_availability')
            ->where('avail_user_id', $userId)
            ->where('avail_starttime', '<', $endSys)
            ->where('avail_endtime', '>', $startSys)
            ->orderBy('avail_starttime')
            ->get(['avail_starttime', 'avail_endtime']);

        return $rows->map(fn ($row) => [
            'start' => DashboardDate::toUser($row->avail_starttime, $userTimezone),
            'end' => DashboardDate::toUser($row->avail_endtime, $userTimezone),
            'className' => 'slot_available',
        ])->all();
    }

    /**
     * @param  array<int, array{start?: string, end?: string}>  $events
     */
    public function saveGeneral(int $userId, string $userTimezone, array $events): void
    {
        $formatted = $this->formatGeneral($events, $userTimezone);
        $weekNo = (int) (DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_AVAILABILITY_UPDATE_WEEK_NO')
            ->value('conf_val') ?? 30);

        DB::transaction(function () use ($userId, $userTimezone, $formatted, $weekNo) {
            DB::table('tbl_general_availability')->where('gavail_user_id', $userId)->delete();

            foreach ($formatted as $slot) {
                DB::table('tbl_general_availability')->insert([
                    'gavail_user_id' => $userId,
                    'gavail_starttime' => $slot['start'],
                    'gavail_endtime' => $slot['end'],
                ]);
            }

            DB::table('tbl_availability')->where('avail_user_id', $userId)->delete();

            $weekData = DashboardDate::thisWeekRange($userTimezone);
            $weekStart = DashboardDate::toSystem($weekData['startDate'], $userTimezone);
            $weekly = $this->getWeeklyOverrides($userId, $weekStart);

            $this->rebuildAvailability($userId, $userTimezone, $formatted, $weekStart, $weekNo, $weekly);

            $available = $formatted === [] ? 0 : 1;
            DB::table('tbl_teacher_stats')
                ->where('testat_user_id', $userId)
                ->update(['testat_availability' => $available]);
        });
    }

    /**
     * @param  array<int, array{start?: string, end?: string}>  $events
     */
    public function saveWeekly(
        int $userId,
        string $userTimezone,
        string $start,
        string $end,
        array $events
    ): void {
        $startSys = DashboardDate::toSystem($start, $userTimezone);
        $endSys = DashboardDate::toSystem($end, $userTimezone);
        $formatted = $this->formatAvailability($events, $userTimezone);

        $startUnix = strtotime($startSys);
        $endUnix = strtotime($endSys);
        $prevWeekStart = date('Y-m-d H:i:s', strtotime('-1 week', $startUnix));
        $nextWeekEnd = date('Y-m-d H:i:s', strtotime('+1 week', $endUnix));

        DB::transaction(function () use ($userId, $startSys, $endSys, $formatted, $startUnix, $endUnix, $prevWeekStart, $nextWeekEnd) {
            DB::table('tbl_availability')
                ->where('avail_user_id', $userId)
                ->where('avail_starttime', '<', $nextWeekEnd)
                ->where('avail_endtime', '>', $prevWeekStart)
                ->delete();

            $weeklyAvailability = [];
            foreach ($formatted as $value) {
                DB::table('tbl_availability')->insert([
                    'avail_user_id' => $userId,
                    'avail_starttime' => $value['start'],
                    'avail_endtime' => $value['end'],
                ]);
                $slotStart = strtotime($value['start']);
                $slotEnd = strtotime($value['end']);
                if ($endUnix > $slotStart && $slotEnd > $startUnix) {
                    $weeklyAvailability[] = [
                        'start' => $startUnix > $slotStart ? $startSys : $value['start'],
                        'end' => $endUnix < $slotEnd ? $endSys : $value['end'],
                    ];
                }
            }

            DB::table('tbl_weekly_availability')->updateOrInsert(
                [
                    'wavail_user_id' => $userId,
                    'wavail_startdate' => $startSys,
                    'wavail_enddate' => $endSys,
                ],
                ['wavail_availability' => json_encode($weeklyAvailability)]
            );
        });
    }

    /**
     * @param  array<int, array{start?: string, end?: string}>  $events
     * @return array<int, array{start: string, end: string}>
     */
    private function formatGeneral(array $events, string $userTimezone): array
    {
        $weekStartUnix = strtotime(self::GENERAL_WEEKSTART);
        $weekEndUnix = strtotime(self::GENERAL_WEEKEND);
        $general = [];

        foreach ($events as $value) {
            if (empty($value['start']) || empty($value['end'])) {
                continue;
            }
            $startUnix = strtotime($value['start']);
            $endUnix = strtotime($value['end']);
            if ($startUnix >= $endUnix || $startUnix >= $weekEndUnix || $weekStartUnix >= $endUnix) {
                continue;
            }
            $start = $weekStartUnix > $startUnix ? self::GENERAL_WEEKSTART : $value['start'];
            $end = $endUnix > $weekEndUnix ? self::GENERAL_WEEKEND : $value['end'];
            $general[] = [
                'start' => DashboardDate::toSystem($start, $userTimezone),
                'end' => DashboardDate::toSystem($end, $userTimezone),
            ];
        }

        return $general;
    }

    /**
     * @param  array<int, array{start?: string, end?: string}>  $events
     * @return array<int, array{start: string, end: string}>
     */
    private function formatAvailability(array $events, string $userTimezone): array
    {
        $formatted = [];
        foreach ($events as $value) {
            if (empty($value['start']) || empty($value['end'])) {
                continue;
            }
            $formatted[] = [
                'start' => DashboardDate::toSystem($value['start'], $userTimezone),
                'end' => DashboardDate::toSystem($value['end'], $userTimezone),
            ];
        }

        return $formatted;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getWeeklyOverrides(int $userId, string $weekStartDate): array
    {
        $rows = DB::table('tbl_weekly_availability')
            ->where('wavail_user_id', $userId)
            ->where('wavail_startdate', '>=', $weekStartDate)
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[$row->wavail_startdate] = (array) $row;
        }

        return $map;
    }

    /**
     * @param  array<int, array{start: string, end: string}>  $generalAvailability
     * @param  array<string, array<string, mixed>>  $weeklyAvailability
     */
    private function rebuildAvailability(
        int $userId,
        string $userTimezone,
        array $generalAvailability,
        string $weekStartDate,
        int $totalWeeks,
        array $weeklyAvailability
    ): void {
        $weekDiff = DashboardDate::weekDiff(self::GENERAL_WEEKSTART, $weekStartDate);
        $weekEndUnix = null;

        for ($week = $weekDiff; $week < ($weekDiff + $totalWeeks); $week++) {
            $weekStartUnix = strtotime(self::GENERAL_WEEKSTART.' + '.$week.' weeks');
            $weekEndUnix = strtotime(self::GENERAL_WEEKEND.' + '.$week.' weeks');
            $weekStart = DashboardDate::toSystem(date('Y-m-d H:i:s', $weekStartUnix), $userTimezone);
            $availability = $generalAvailability;
            $addWeekToTime = true;

            if (! empty($weeklyAvailability[$weekStart])) {
                $decoded = json_decode($weeklyAvailability[$weekStart]['wavail_availability'] ?? '[]', true);
                if (is_array($decoded) && $decoded !== []) {
                    $availability = $decoded;
                    $addWeekToTime = false;
                }
            }

            foreach ($availability as $value) {
                $start = strtotime($value['start']);
                $end = strtotime($value['end']);
                if ($addWeekToTime) {
                    $start = strtotime('+ '.$week.' weeks', $start);
                    $end = strtotime('+ '.$week.' weeks', $end);
                }
                DB::table('tbl_availability')->insert([
                    'avail_user_id' => $userId,
                    'avail_starttime' => date('Y-m-d H:i:s', $start),
                    'avail_endtime' => date('Y-m-d H:i:s', $end),
                ]);
            }
        }

        if ($weekEndUnix !== null) {
            $endDate = date('Y-m-d H:i:s', $weekEndUnix);
            DB::table('tbl_user_settings')
                ->where('user_id', $userId)
                ->update(['user_availability_date' => $endDate]);
        }
    }
}
