<?php

namespace App\Support;

use Carbon\Carbon;

class DashboardDate
{
    public static function systemTimezone(): string
    {
        return config('app.dashboard_system_timezone', 'UTC');
    }

    public static function toSystem(string $date, string $userTimezone, ?string $format = 'Y-m-d H:i:s'): string
    {
        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return $date;
        }

        return Carbon::parse($date, $userTimezone)
            ->timezone(self::systemTimezone())
            ->format($format);
    }

    public static function toUser(string $date, string $userTimezone, ?string $format = 'Y-m-d H:i:s'): string
    {
        if ($date === '' || str_starts_with($date, '0000-00-00')) {
            return $date;
        }

        return Carbon::parse($date, self::systemTimezone())
            ->timezone($userTimezone)
            ->format($format);
    }

    public static function offset(string $timezone): string
    {
        return Carbon::now($timezone)->format('P');
    }

    public static function weekDiff(string $date1, string $date2): int
    {
        $first = Carbon::parse($date1);
        $second = Carbon::parse($date2);
        if ($first->greaterThan($second)) {
            return self::weekDiff($date2, $date1);
        }

        return (int) floor($first->diffInDays($second) / 7);
    }

    /**
     * @return array{startDate: string, endDate: string}
     */
    public static function thisWeekRange(string $timezone): array
    {
        $now = Carbon::now($timezone);
        $dayNumber = (int) $now->format('w');
        $start = $now->copy();
        $end = $now->copy();
        if ($dayNumber === 0) {
            $start->modify('this week sunday');
            $end->modify('this week sunday +7 days');
        } else {
            $start->modify('this week monday -1 day');
            $end->modify('this week sunday');
        }

        return [
            'startDate' => $start->format('Y-m-d H:i:s'),
            'endDate' => $end->format('Y-m-d H:i:s'),
        ];
    }
}
