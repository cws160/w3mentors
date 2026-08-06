<?php

namespace App\Support\Admin;

use DateTime;
use DateTimeZone;

class AdminDateRange
{
    public const TYPE_TODAY = 1;

    public const TYPE_THIS_WEEK = 2;

    public const TYPE_LAST_WEEK = 3;

    public const TYPE_THIS_MONTH = 4;

    public const TYPE_LAST_MONTH = 5;

    public const TYPE_THIS_YEAR = 6;

    public const TYPE_LAST_YEAR = 7;

    public const TYPE_LAST_12_MONTH = 8;

    public const TYPE_ALL = 9;

    /**
     * @return array{startDate: string, endDate: string}
     */
    public static function bounds(
        int $duration,
        ?string $timezone = null,
        bool $convertInSystemTimezone = false,
        string $dateFormat = 'Y-m-d H:i:s'
    ): array {
        $timezone = $timezone ?: (string) config('app.timezone', 'UTC');
        $start = new DateTime('now', new DateTimeZone($timezone));
        $end = new DateTime('now', new DateTimeZone($timezone));
        $dayNumber = (int) $start->format('w');

        switch ($duration) {
            case self::TYPE_TODAY:
                $start->modify('today');
                $end->modify('today +1 day');
                break;
            case self::TYPE_THIS_WEEK:
                $startModif = 'this week monday -1 day';
                $endModify = 'this week sunday';
                if ($dayNumber === 0) {
                    $startModif = 'this week sunday';
                    $endModify = 'this week sunday +7 days';
                }
                $start->modify($startModif);
                $end->modify($endModify);
                break;
            case self::TYPE_LAST_WEEK:
                $startModif = 'last week monday -1 day';
                $endModify = 'last week monday +6 day';
                if ($dayNumber === 0) {
                    $startModif = 'last week sunday';
                    $endModify = 'this week sunday';
                }
                $start->modify($startModif);
                $end->modify($endModify);
                break;
            case self::TYPE_THIS_MONTH:
                $start->modify('first day of this month midnight');
                $end->modify('first day of next month midnight');
                break;
            case self::TYPE_LAST_MONTH:
                $start->modify('first day of previous month midnight');
                $end->modify('first day of this month midnight');
                break;
            case self::TYPE_THIS_YEAR:
                $start->modify('first day of January midnight');
                $end->modify('next year January 1st midnight');
                break;
            case self::TYPE_LAST_YEAR:
                $start->modify('last year January 1st midnight');
                $end->modify('first day of January midnight');
                break;
            case self::TYPE_LAST_12_MONTH:
                $start->modify('first day of this month midnight -11 months');
                $end->modify('first day of next month midnight');
                break;
            case self::TYPE_ALL:
            default:
                $start->modify('first day of January 2018 midnight');
                $end->modify('first day of next month midnight');
                break;
        }

        if ($convertInSystemTimezone) {
            $systemTz = (string) config('app.timezone', 'UTC');
            $start->setTimezone(new DateTimeZone($systemTz));
            $end->setTimezone(new DateTimeZone($systemTz));
        }

        return [
            'startDate' => $start->format($dateFormat),
            'endDate' => $end->format($dateFormat),
        ];
    }

    public static function normalizeInterval(int $interval): int
    {
        $allowed = [
            self::TYPE_TODAY,
            self::TYPE_THIS_WEEK,
            self::TYPE_LAST_WEEK,
            self::TYPE_THIS_MONTH,
            self::TYPE_LAST_MONTH,
            self::TYPE_THIS_YEAR,
            self::TYPE_LAST_YEAR,
            self::TYPE_LAST_12_MONTH,
            self::TYPE_ALL,
        ];

        return in_array($interval, $allowed, true) ? $interval : self::TYPE_ALL;
    }
}
