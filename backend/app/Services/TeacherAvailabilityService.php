<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TeacherAvailabilityService
{
    private const LESSON_SCHEDULED = 2;

    private const ORDER_PAID = 1;

    private const ORDER_COMPLETED = 2;

    private const CLASS_SCHEDULED = 1;

    private const GROUP_CLASS_REGULAR = 1;

    public function meta(int $teacherId, string $timezone): array
    {
        $teacher = DB::table('tbl_users')
            ->where('user_id', $teacherId)
            ->first(['user_first_name', 'user_last_name']);

        $name = trim(($teacher->user_first_name ?? '') . ' ' . ($teacher->user_last_name ?? ''));

        return [
            'teacher_id' => $teacherId,
            'teacher_name' => $name,
            'min_date' => Carbon::now($timezone)->format('Y-m-d'),
            'timezone' => $timezone,
            'timezone_label' => 'GMT ' . Carbon::now($timezone)->format('P'),
        ];
    }

    /**
     * @return array{date_heading: string, entries: array<int, array{start: string, label: string}>, empty: bool}
     */
    public function slotsForDate(
        int $teacherId,
        string $selectedDate,
        string $timezone,
        ?int $viewerId = null,
        int $duration = 15
    ): array {
        $tz = $timezone ?: 'UTC';
        $now = Carbon::now($tz);
        $selected = Carbon::parse($selectedDate, $tz)->startOfDay();

        $timeSuffix = $selected->isSameDay($now) ? $now->format(' H:i:s') : ' 00:00:00';
        $startLocal = $selectedDate . $timeSuffix;
        $endLocal = $selectedDate . ' 23:59:59';

        $startUtc = Carbon::parse($startLocal, $tz)->utc()->format('Y-m-d H:i:s');
        $endUtc = Carbon::parse($endLocal, $tz)->utc()->format('Y-m-d H:i:s');

        $settings = DB::table('tbl_user_settings')
            ->where('user_id', $teacherId)
            ->first(['user_book_before', 'user_slots']);

        $userAvailability = $this->fetchAvailability($teacherId, $startUtc, $endUtc);
        $scheduled = $this->fetchScheduledSessions($teacherId, $viewerId, $startUtc, $endUtc, $tz);
        $bookBefore = (int) ($settings->user_book_before ?? 0);

        $groups = $this->computeSlots(
            $startLocal,
            $endLocal,
            $scheduled,
            $userAvailability,
            [
                'user_book_before' => $bookBefore,
                'duration' => $duration > 0 ? $duration : 15,
                'timezone' => $tz,
                'useSubEndDate' => false,
            ]
        );

        $daySlots = $groups[0]['slots'] ?? [];
        $entries = array_map(
            fn (array $slot) => [
                'start' => $slot['start'],
                'label' => Carbon::parse($slot['start'], $tz)->format('h:i A'),
            ],
            $daySlots
        );

        return [
            'date_heading' => Carbon::parse($startUtc)->timezone($tz)->format('D, M d'),
            'entries' => $entries,
            'empty' => $entries === [],
        ];
    }

    private function fetchAvailability(int $teacherId, string $startUtc, string $endUtc): array
    {
        $rows = DB::table('tbl_availability')
            ->where('avail_user_id', $teacherId)
            ->where('avail_starttime', '<', $endUtc)
            ->where('avail_endtime', '>', $startUtc)
            ->orderBy('avail_starttime')
            ->get(['avail_starttime', 'avail_endtime']);

        return $rows->map(fn ($row) => [
            'start' => $row->avail_starttime,
            'end' => $row->avail_endtime,
        ])->all();
    }

    private function fetchScheduledSessions(
        int $teacherId,
        ?int $viewerId,
        string $startUtc,
        string $endUtc,
        string $timezone
    ): array {
        $userIds = [$teacherId];
        if ($viewerId && $viewerId !== $teacherId) {
            $userIds[] = $viewerId;
        }

        $lessons = DB::table('tbl_order_lessons as ordles')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->where('ordles.ordles_status', self::LESSON_SCHEDULED)
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('orders.order_status', self::ORDER_COMPLETED)
            ->where('ordles.ordles_lesson_starttime', '<', $endUtc)
            ->where('ordles.ordles_lesson_endtime', '>', $startUtc)
            ->where(function ($q) use ($userIds) {
                $q->whereIn('ordles.ordles_teacher_id', $userIds)
                    ->orWhereIn('orders.order_user_id', $userIds);
            })
            ->get(['ordles_lesson_starttime', 'ordles_lesson_endtime']);

        $classes = DB::table('tbl_orders as orders')
            ->join('tbl_order_classes as ordcls', 'ordcls.ordcls_order_id', '=', 'orders.order_id')
            ->join('tbl_group_classes as grpcls', 'grpcls.grpcls_id', '=', 'ordcls.ordcls_grpcls_id')
            ->where('grpcls.grpcls_type', self::GROUP_CLASS_REGULAR)
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('orders.order_status', self::ORDER_COMPLETED)
            ->where('ordcls.ordcls_status', self::CLASS_SCHEDULED)
            ->where('grpcls.grpcls_start_datetime', '<', $endUtc)
            ->where('grpcls.grpcls_end_datetime', '>', $startUtc)
            ->where(function ($q) use ($userIds) {
                $q->whereIn('grpcls.grpcls_teacher_id', $userIds)
                    ->orWhereIn('orders.order_user_id', $userIds);
            })
            ->groupBy('grpcls.grpcls_id')
            ->get(['grpcls_start_datetime', 'grpcls_end_datetime']);

        $sessions = [];
        foreach ($lessons as $lesson) {
            $sessions[] = [
                'start' => Carbon::parse($lesson->ordles_lesson_starttime, 'UTC')->timezone($timezone)->format('Y-m-d H:i:s'),
                'end' => Carbon::parse($lesson->ordles_lesson_endtime, 'UTC')->timezone($timezone)->format('Y-m-d H:i:s'),
            ];
        }
        foreach ($classes as $class) {
            $sessions[] = [
                'start' => Carbon::parse($class->grpcls_start_datetime, 'UTC')->timezone($timezone)->format('Y-m-d H:i:s'),
                'end' => Carbon::parse($class->grpcls_end_datetime, 'UTC')->timezone($timezone)->format('Y-m-d H:i:s'),
            ];
        }

        return $sessions;
    }

    /**
     * Port of Availability::getAvailabiltySlots (view-only).
     */
    private function computeSlots(
        string $startTime,
        string $endTime,
        array $scheduledData,
        array $userAvailability,
        array $userData
    ): array {
        $minutes = (int) date('i', ceil(time() / (15 * 60)) * (15 * 60));
        $currentDateTime = strtotime(date('Y-m-d H:' . sprintf('%02d', $minutes) . ':00'));

        $bookingBeforeDate = strtotime('+ ' . (int) $userData['user_book_before'] . ' Hours', $currentDateTime);
        $slotsDuration = 15;

        foreach ($scheduledData as $key => $scheduled) {
            $scheduledData[$key]['start'] = strtotime($scheduled['start']);
            $scheduledData[$key]['end'] = strtotime($scheduled['end']);
        }

        $slots = [];
        foreach ($userAvailability as $avail) {
            $startDateTime = strtotime($avail['start']);
            $endDateTime = strtotime($avail['end']);

            if ($bookingBeforeDate < $endDateTime) {
                if ($bookingBeforeDate > $startDateTime) {
                    $startDateTime = $bookingBeforeDate;
                }
                while ($startDateTime <= $endDateTime) {
                    if (date('Y-m-d', $startDateTime) !== date('Y-m-d', strtotime($startTime))) {
                        $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                        continue;
                    }

                    $end = strtotime('+ ' . (int) $userData['duration'] . ' minutes', $startDateTime);
                    $isScheduled = false;
                    foreach ($scheduledData as $scheduled) {
                        if (
                            (($startDateTime <= $scheduled['start'] && $scheduled['start'] < $end)
                                || ($scheduled['end'] > $startDateTime && $scheduled['end'] <= $end))
                            || ($startDateTime >= $scheduled['start'] && $startDateTime < $scheduled['end'])
                            || ($end > $scheduled['start'] && $end <= $scheduled['end'])
                        ) {
                            $isScheduled = true;
                            break;
                        }
                    }

                    if ($isScheduled) {
                        $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                        continue;
                    }

                    if ($end > $endDateTime) {
                        $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                        break;
                    }

                    if (($startDateTime < strtotime($startTime) || $startDateTime > strtotime($endTime)) || $end < strtotime($startTime)) {
                        $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                        continue;
                    }

                    if (!empty($userData['useSubEndDate']) && ($end > strtotime($endTime))) {
                        break;
                    }

                    $slots[] = [
                        'start' => date('Y-m-d H:i:s', $startDateTime),
                        'end' => date('Y-m-d H:i:s', $end),
                    ];
                    $startDateTime = strtotime('+ ' . $slotsDuration . ' minutes', $startDateTime);
                }
            }
        }

        $availability = [];
        foreach ($slots as $slot) {
            $key = date('Y-m-d', strtotime($slot['start']));
            $availability[$key]['date'] = $key;
            $availability[$key]['slots'][] = $slot;
        }

        if ($availability === []) {
            $availability[] = [
                'date' => date('Y-m-d', strtotime($startTime)),
                'slots' => [],
            ];
        }

        return array_values($availability);
    }
}
