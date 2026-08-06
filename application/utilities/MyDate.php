<?php

/**
 * A Common MyDate Utility
 *
 * @package W3Mentors
 * @author Fatbit Team
 */
class MyDate extends FatDate
{

    const TYPE_TODAY = 1;
    const TYPE_THIS_WEEK = 2;
    const TYPE_LAST_WEEK = 3;
    const TYPE_THIS_MONTH = 4;
    const TYPE_LAST_MONTH = 5;
    const TYPE_THIS_YEAR = 6;
    const TYPE_LAST_YEAR = 7;
    const TYPE_LAST_12_MONTH = 8;
    const TYPE_ALL = 9;
    const TYPE_28_DAYS = 10;
    /* Format Types */
    const YMDHIS = 0;
    const DATEONLY = 1;
    const DATETIME = 2;
    const TIMEONLY = 3;

    /**TIME FORMAT AVAILABLE IN SYSTEM */
    const FORMAT_12_HR = 1;
    const FORMAT_24_HR = 2;

    /**
     * Format Date to display
     *
     * @param mixed $date
     * @param bool $time
     * @param int $langId
     * @return string
     */
    public static function showDate($date = null, bool $time = false, int $langId = 0): string
    {
        static::setCalendarLabels($langId);
        if (empty($date) || is_null($date) || (substr($date, 0, 10) === '0000-00-00') || $date == '--') {
            return Label::getLabel('LBL_NA', $langId);
        }
        $format = [0 => FatApp::getConfig('FRONTEND_DATE_FORMAT')];
        if ($time) {
            $format[1] = static::getFormatTime();
        }
        global $calendarLabels;
        $formattedDate = date(implode(" ", $format), strtotime($date));
        return str_replace(array_keys($calendarLabels), $calendarLabels, $formattedDate);
    }

    /**
     * Format Time to display
     *
     * @param mixed $date
     * @param int $langId
     * @return string
     */
    public static function showTime($date = null, int $langId = 0): string
    {
        static::setCalendarLabels($langId);
        if (empty($date) || is_null($date) || (substr($date, 0, 10) === '0000-00-00')) {
            return Label::getLabel('LBL_NA', $langId);
        }
        global $calendarLabels;
        $formattedTime = date(static::getFormatTime(), strtotime($date));
        return str_replace(array_keys($calendarLabels), $calendarLabels, $formattedTime);
    }

    /**
     * Set Calendar Labels
     *
     * @param int $langId
     * @global array $calendarLabels
     */
    public static function setCalendarLabels(int $langId = 0)
    {
        global $calendarLabels;
        if (is_null($calendarLabels) || $langId != 0) {
            $days = MyDate::dayNames(true, $langId);
            $months = MyDate::getAllMonthName(true, $langId);
            $calendarLabels = array_merge(
                $days['longName'],
                $days['shortName'],
                $months['longName'],
                $months['shortName'],
                MyDate::meridiems(true, $langId)
            );
        }
    }

    /**
     * Convert Date to defined|site timezone
     *
     * @param string $date
     * @param string $timezome
     * @return string
     */
    public static function convert(string $date = null, string $timezome = null): string
    {
        if (empty($date) || substr($date, 0, 10) === '0000-00-00') {
            return '';
        }
        $timezome = is_null($timezome) ? MyUtility::getSiteTimezone() : $timezome;
        return FatDate::changeDateTimezone($date, MyUtility::getSystemTimezone(), $timezome);
    }

    /**
     * Get Date Formats
     *
     * @return array
     */
    public static function getDateFormats(): array
    {
        $date = date('Y-m-d H:i:s');
        return [
            'Y-m-d' => date('Y-m-d', strtotime(static::convert($date))),
            'd/m/Y' => date('d/m/Y', strtotime(static::convert($date))),
            'm-d-Y' => date('m-d-Y', strtotime(static::convert($date))),
            'M d, Y' => date('M d, Y', strtotime(static::convert($date))),
            'l, M d, Y' => date('l, M d, Y', strtotime(static::convert($date))),
        ];
    }

    /**
     * Get Time Formats
     *
     * @return array
     */
    public static function getTimeFormats(): array
    {
        $date = date('Y-m-d H:i:s');
        return [
            'H:i' => date('H:i', strtotime(static::convert($date))),
            'H:i:s' => date('H:i:s', strtotime(static::convert($date))),
            'h:i a' => date('h:i a', strtotime(static::convert($date))),
            'h:i A' => date('h:i A', strtotime(static::convert($date))),
        ];
    }

    /**
     * Format Date
     *
     * @param string $date
     * @param string $format
     * @param string $userTimezome
     * @return string
     */
    public static function formatDate(string $date = null, string $format = 'Y-m-d H:i:s', string $userTimezome = null, bool $timeZoneStr = false): string
    {
        if (is_null($date)) {
            return '--';
        }
        if (empty($date) || substr($date, 0, 10) === '0000-00-00') {
            return $date;
        }
        $userTimezome = is_null($userTimezome) ? MyUtility::getSiteTimezone() : $userTimezome;
        $date = FatDate::changeDateTimezone($date, MyUtility::getSystemTimezone(), $userTimezome);
        return date($format, strtotime($date)) . ($timeZoneStr ? ' (' . $userTimezome . ')' : '');
    }

    /**
     * Format To System Timezone
     *
     * @param string $date
     * @param string $format
     * @param string $userTimezome
     * @return string
     */
    public static function formatToSystemTimezone(string $date = null, string $format = 'Y-m-d H:i:s', string $userTimezome = null): string
    {
        if (is_null($date)) {
            return '--';
        }
        if (empty($date) || substr($date, 0, 10) === '0000-00-00') {
            return $date;
        }
        $userTimezome = is_null($userTimezome) ? MyUtility::getSiteTimezone() : $userTimezome;
        $date = FatDate::changeDateTimezone($date, $userTimezome, MyUtility::getSystemTimezone());
        return date($format, strtotime($date));
    }

    /**
     * Change Date Timezone
     *
     * @param string $date
     * @param string $fromTimezone
     * @param string $toTimezone
     * @return string
     */
    public static function changeDateTimezone($date, $fromTimezone, $toTimezone): string
    {
        return parent::changeDateTimezone($date, $fromTimezone, $toTimezone);
    }

    /**
     * Get Day Number
     *
     * @param string $date
     * @return int
     */
    public static function getDayNumber(string $date): int
    {
        $number = date('N', strtotime($date));
        return (7 == $number) ? 0 : FatUtility::int($number);
    }

    /**
     * Get Week Diff
     *
     * @param string $date1
     * @param string $date2
     * @return type
     */
    public static function weekDiff(string $date1, string $date2)
    {
        $first = new DateTime($date1);
        $second = new DateTime($date2);
        if ($date1 > $date2) {
            return self::weekDiff($date2, $date1);
        }
        return floor($first->diff($second)->days / 7);
    }

    /**
     * Get Offset
     *
     * @param string $timezone
     * @return string
     */
    public static function getOffset(string $timezone = 'UTC'): string
    {
        return (new DateTime("now", new DateTimeZone($timezone)))->format('P');
    }

    /**
     * Get Offset
     *
     * @param string $timezone
     * @return string
     */
    public static function formatTimeZoneLabel(string $timezone): string
    {
        $label = Label::getLabel('LBL_UTC_{offset}_{name}');
        return str_replace(['{offset}', '{name}'], [static::getOffset($timezone), Label::getLabel('TMZ_' . $timezone)], $label);
    }

    /**
     * Time Zone Listing
     *
     * @return array
     */
    public static function timeZoneListing(): array
    {
        $timeZoneList = DateTimeZone::listIdentifiers();
        $finalArray = [];
        foreach ($timeZoneList as $timezone) {
            $finalArray[$timezone] = static::formatTimeZoneLabel($timezone);
        }
        return $finalArray;
    }

    /**
     * Get Week Start and End Date
     *
     * @param DateTime $dateTime
     * @param string $format
     * @param bool $midNight
     * @return array
     */
    public static function getWeekStartAndEndDate(DateTime $dateTime, string $format = 'Y-m-d', bool $midNight = false): array
    {
        $dateTime = $dateTime->modify('last saturday')->modify('+1 day');
        $weekEndModify = ($midNight) ? 'next sunday midnight' : 'next saturday';
        return [
            'weekStart' => $dateTime->format($format),
            'weekEnd' => $dateTime->modify($weekEndModify)->format($format),
        ];
    }

    /**
     * Change Week Days To Date
     *
     * @param array $weekDays
     * @param array $timeSlotArr
     * @return array
     */
    public static function changeWeekDaysToDate(array $weekDays, array $timeSlotArr = []): array
    {
        $weekStartUnix = strtotime(Availability::GENERAL_WEEKSTART);
        $newWeekDayArray = [];
        foreach ($weekDays as $key => $day) {
            $unixDate = strtotime("+" . $day . " days", $weekStartUnix);
            $date = date("Y-m-d", $unixDate);
            if (!empty($timeSlotArr)) {
                foreach ($timeSlotArr as $timeKey => $timeSlot) {
                    $startDateTime = $date . ' ' . $timeSlot['startTime'];
                    $endDateTime = $date . ' ' . $timeSlot['endTime'];
                    $startDateTime = MyDate::formatToSystemTimezone($startDateTime);
                    $endDateTime = MyDate::formatToSystemTimezone($endDateTime);
                    $newWeekDayArray[] = [
                        'startDate' => $startDateTime,
                        'endDate' => $endDateTime
                    ];
                }
            } else {
                $dateStart = date("Y-m-d H:i:s", $unixDate);
                $dateEnd = date('Y-m-d H:i:s', strtotime(" +1 day", $unixDate));
                $dateStart = MyDate::formatToSystemTimezone($dateStart);
                $dateEnd = MyDate::formatToSystemTimezone($dateEnd);
                $newWeekDayArray[] = [
                    'startDate' => $dateStart,
                    'endDate' => $dateEnd,
                ];
            }
        }
        return $newWeekDayArray;
    }

    /**
     * Hours Difference
     *
     * @param string $toDate
     * @param string $fromDate
     * @param int $roundUpTo
     * @return float
     */
    public static function hoursDiff(string $toDate, string $fromDate = '', int $roundUpTo = 2): float
    {
        $fromDate = $fromDate ?: date('Y-m-d H:i:s');
        return round((strtotime($toDate) - strtotime($fromDate)) / 3600, $roundUpTo);
    }

    /**
     * Is DST
     *
     * @param string $dateTime
     * @param string $timezone
     * @return type
     */
    public static function isDST(string $dateTime = '', string $timezone = null)
    {
        $dateTime = (empty($dateTime)) ? date('Y-m-d H:i:s') : $dateTime;
        $timezone = is_null($timezone) ? MyUtility::getSiteTimezone() : $timezone;
        $tz = new DateTimeZone($timezone);
        $theTime = strtotime($dateTime);
        $transition = $tz->getTransitions($theTime, $theTime);
        $transition = current($transition);
        return $transition['isdst'];
    }

    /**
     * Get hours and minutes formatted string.
     *
     * @param integer $seconds
     * @param string  $format
     *
     * @return string
     */
    public static function getHoursMinutes(int $seconds, string $format = '%02d:%02d'): string
    {
        if (empty($seconds) || !is_numeric($seconds)) {
            return false;
        }
        $minutes = round($seconds / 60);
        $hours = floor($minutes / 60);
        $remainMinutes = ($minutes % 60);
        return sprintf($format, $hours, $remainMinutes);
    }

    /**
     * Get All Month Name
     *
     * @param bool $getWithKeys
     * @return array
     */
    public static function getAllMonthName(bool $getWithKeys = false, int $langId = 0): array
    {
        $monthName = [
            'longName' => [
                'January' => Label::getLabel('LBL_JANUARY', $langId),
                'February' => Label::getLabel('LBL_FEBRUARY', $langId),
                'March' => Label::getLabel('LBL_MARCH', $langId),
                'April' => Label::getLabel('LBL_APRIL', $langId),
                'May' => Label::getLabel('LBL_MAY', $langId),
                'June' => Label::getLabel('LBL_JUNE', $langId),
                'July' => Label::getLabel('LBL_JULY', $langId),
                'August' => Label::getLabel('LBL_AUGUST', $langId),
                'September' => Label::getLabel('LBL_SEPTEMBER', $langId),
                'October' => Label::getLabel('LBL_OCTOBER', $langId),
                'November' => Label::getLabel('LBL_NOVEMBER', $langId),
                'December' => Label::getLabel('LBL_DECEMBER', $langId)
            ],
            'shortName' => [
                'Jan' => Label::getLabel('LBL_JAN', $langId),
                'Feb' => Label::getLabel('LBL_FEB', $langId),
                'Mar' => Label::getLabel('LBL_MAR', $langId),
                'Apr' => Label::getLabel('LBL_APR', $langId),
                'May' => Label::getLabel('LBL_MAY', $langId),
                'Jun' => Label::getLabel('LBL_JUN', $langId),
                'Jul' => Label::getLabel('LBL_JUL', $langId),
                'Aug' => Label::getLabel('LBL_AUG', $langId),
                'Sep' => Label::getLabel('LBL_SEP', $langId),
                'Oct' => Label::getLabel('LBL_OCT', $langId),
                'Nov' => Label::getLabel('LBL_NOV', $langId),
                'Dec' => Label::getLabel('LBL_DEC', $langId)
            ]
        ];
        if (!$getWithKeys) {
            return [
                'longName' => array_values($monthName['longName']),
                'shortName' => array_values($monthName['shortName']),
            ];
        }
        return $monthName;
    }

    /**
     * Day Names
     *
     * @param bool $getWithKeys
     * @param int $langId
     * @return array
     * Note : Do not change the index of days
     */
    public static function dayNames(bool $getWithKeys = false, int $langId = 0): array
    {
        $dayNames = [
            'longName' => [
                'Monday' => Label::getLabel('LBL_MONDAY', $langId),
                'Tuesday' => Label::getLabel('LBL_TUESDAY', $langId),
                'Wednesday' => Label::getLabel('LBL_WEDNESDAY', $langId),
                'Thursday' => Label::getLabel('LBL_THURSDAY', $langId),
                'Friday' => Label::getLabel('LBL_FRIDAY', $langId),
                'Saturday' => Label::getLabel('LBL_SATURDAY', $langId),
                'Sunday' => Label::getLabel('LBL_SUNDAY', $langId),
            ],
            'shortName' => [
                'Mon' => Label::getLabel('LBL_MON', $langId),
                'Tue' => Label::getLabel('LBL_TUE', $langId),
                'Wed' => Label::getLabel('LBL_WED', $langId),
                'Thu' => Label::getLabel('LBL_THU', $langId),
                'Fri' => Label::getLabel('LBL_FRI', $langId),
                'Sat' => Label::getLabel('LBL_SAT', $langId),
                'Sun' => Label::getLabel('LBL_SUN', $langId),
            ],
        ];
        if (!$getWithKeys) {
            return [
                'longName' => array_values($dayNames['longName']),
                'shortName' => array_values($dayNames['shortName']),
            ];
        }
        return $dayNames;
    }

    /**
     * Get Meridiems
     *
     * @param bool $getWithKeys
     * @param int $langId
     * @return type
     */
    public static function meridiems(bool $getWithKeys = false, int $langId = 0): array
    {
        $meridiems = [
            'AM' => Label::getLabel('LBL_AM', $langId),
            'PM' => Label::getLabel('LBL_PM', $langId),
        ];
        if (!$getWithKeys) {
            return array_values($meridiems);
        }
        return $meridiems;
    }

    /**
     * Get Start End Date
     *
     * @param int $duration
     * @param string $timezone
     * @param bool $convertInSystemTimezone
     * @param string $dateFormat
     * @return array
     */
    public static function getStartEndDate(int $duration, string $timezone = null, bool $convertInSystemTimezone = false, string $dateFormat = 'Y-m-d H:i:s'): array
    {
        $timezone = (is_null($timezone)) ? MyUtility::getSiteTimezone() : $timezone;
        $sDateTime = new dateTime('now', new DateTimeZone($timezone));
        $eDateTime = new dateTime('now', new DateTimeZone($timezone));
        $dayNumber = $sDateTime->format('w');
        switch ($duration) {
            case static::TYPE_TODAY:
                $sDateTime->modify('today');
                $eDateTime->modify('today +1 day');
                break;
            case static::TYPE_THIS_WEEK:
                $startModif = 'this week monday -1 day';
                $endModify = 'this week sunday';
                if ($dayNumber == 0) {
                    $startModif = 'this week sunday';
                    $endModify = 'this week sunday +7 days';
                }
                $sDateTime->modify($startModif);
                $eDateTime->modify($endModify);
                break;
            case static::TYPE_LAST_WEEK:
                $startModif = 'last week monday -1 day';
                $endModify = 'last week monday +6 day';
                if ($dayNumber == 0) {
                    $startModif = 'last week sunday';
                    $endModify = 'this week sunday';
                }
                $sDateTime->modify($startModif);
                $eDateTime->modify($endModify);
                break;
            case static::TYPE_THIS_MONTH:
                $sDateTime->modify('first day of this month midnight');
                $eDateTime->modify('first day of next month midnight');
                break;
            case static::TYPE_LAST_MONTH:
                $sDateTime->modify('first day of previous month midnight');
                $eDateTime->modify('first day of this month midnight');
                break;
            case static::TYPE_THIS_YEAR:
                $sDateTime->modify('first day of January midnight');
                $eDateTime->modify('next year January 1st midnight');
                break;
            case static::TYPE_LAST_YEAR:
                $sDateTime->modify('last year January 1st midnight');
                $eDateTime->modify('first day of January midnight');
                break;
            case static::TYPE_LAST_12_MONTH:
                $sDateTime->modify('first day of this month midnight -11 months');
                $eDateTime->modify('first day of next month midnight');
                break;
            case static::TYPE_28_DAYS:
                $sDateTime->modify('today');
                $eDateTime->modify('+28 days midnight');
                break;
            case static::TYPE_ALL:
            default:
                $sDateTime->modify('first day of January 2018 midnight');
                break;
        }
        $start = $sDateTime->format($dateFormat);
        $end = $eDateTime->format($dateFormat);
        if ($convertInSystemTimezone) {
            $start = static::formatToSystemTimezone($sDateTime->format('Y-m-d H:i:s'), 'Y-m-d H:i:s', $timezone);
            $end = static::formatToSystemTimezone($eDateTime->format('Y-m-d H:i:s'), 'Y-m-d H:i:s', $timezone);
            if ($dateFormat != 'Y-m-d H:i:s') {
                $start = date($dateFormat, strtotime($start));
                $end = date($dateFormat, strtotime($end));
            }
        }
        return [
            'startDate' => $start,
            'endDate' => $end
        ];
    }

    /**
     * Get Duration Types
     *
     * @return array
     */
    public static function getDurationTypesArr(): array
    {
        return [
            static::TYPE_TODAY => Label::getLabel('LBL_TODAY'),
            static::TYPE_THIS_WEEK => Label::getLabel('LBL_THIS_WEEK'),
            static::TYPE_LAST_WEEK => Label::getLabel('LBL_LAST_WEEK'),
            static::TYPE_THIS_MONTH => Label::getLabel('LBL_THIS_MONTH'),
            static::TYPE_LAST_MONTH => Label::getLabel('LBL_LAST_MONTH'),
            static::TYPE_THIS_YEAR => Label::getLabel('LBL_THIS_YEAR'),
            static::TYPE_LAST_YEAR => Label::getLabel('LBL_LAST_YEAR'),
            static::TYPE_LAST_12_MONTH => Label::getLabel('LBL_LAST_12_MONTH'),
            static::TYPE_ALL => Label::getLabel('LBL_ALL'),
        ];
    }

    public static function getSubscriptionDates(int $days): array
    {
        $currentdate = date('Y-m-d H:i:s');
        $date = new DateTime($currentdate);
        $date->modify('+' . $days . ' day');
        return [
            'ordsub_startdate' => $currentdate,
            'ordsub_enddate' => $date->format('Y-m-d H:i:s')
        ];
    }

    public static function getSubscriptionPlanDates(int $days): array
    {
        $currentdate = date('Y-m-d H:i:s');
        $date = new DateTime($currentdate);
        $date->modify('+' . $days . ' day');
        return [
            'ordsplan_start_date' => $currentdate,
            'ordsplan_end_date' => $date->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Get Date Difference
     *
     * @return string
     */
    public static function getDateTimeDifference($date1, $date2, $addedOnDateLbl = false)
    {
        $diff = abs(strtotime($date2) - strtotime($date1));
        $years = floor($diff / (365 * 60 * 60 * 24));
        $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
        $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));
        $hours = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24) / (60 * 60));
        $minuts = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60) / 60);
        $seconds = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24 - $days * 60 * 60 * 24 - $hours * 60 * 60 - $minuts * 60));
        $dateData = ['years' => $years, 'months' => $months, 'days' => $days, 'hours' => $hours, 'min' => $minuts, 'sec' => $seconds];
        if (true == $addedOnDateLbl) {
            return static::getAddedOnDateLbl($dateData, $date1);
        }
        return $dateData;
    }

    /**
     * Get Added on Date Labels
     *
     * @return string
     */
    public static function getAddedOnDateLbl($dateArr, $date)
    {
        if ($dateArr['years'] > 0 || $dateArr['months'] > 0 || $dateArr['days'] > 0) {
            return MyDate::formatDate($date, static::getFormatTime().' M d, Y');
        }
        if ($dateArr['hours'] > 0) {
            return $dateArr['hours'] . ' ' . Label::getLabel('LBL_Hours_Ago');
        }
        if ($dateArr['min'] > 0) {
            return $dateArr['min'] . ' ' . Label::getLabel('LBL_Min_Ago');
        }
        if ($dateArr['sec'] > 0) {
            return $dateArr['sec'] . ' ' . Label::getLabel('LBL_Sec_Ago');
        }
        return Label::getLabel('LBL_JUST_NOW');
    }

    public static function getFormatTime($inJs = false, $removeSeconds = true): string
    {
        $timeFormatKey = $inJs ? 'FRONTEND_TIME_FORMAT_JS' : 'FRONTEND_TIME_FORMAT_PHP';
        $timeFormat = FatApp::getConfig($timeFormatKey);
        if ($removeSeconds) {
            $timeFormat = preg_replace('/:(\w+)(?:\s+(a|A))?\s*$/', ' $2', $timeFormat);
        }
        return $timeFormat;
    }

    public static function getSysTimeFormatOpt($key = null): array
    {
        $arr = [
            static::FORMAT_12_HR => Label::getLabel('LBL_12_HOUR_FORMAT'),
            static::FORMAT_24_HR => Label::getLabel('LBL_24_HOUR_FORMAT'),
        ];
        return AppConstant::returArrValue($arr, $key);
    }
}
