<?php

namespace App\Services\Admin;

class AdminOrderHelper
{
    public const TYPE_LESSON = 1;

    public const TYPE_SUBSCR = 2;

    public const TYPE_GCLASS = 3;

    public const TYPE_PACKGE = 4;

    public const TYPE_COURSE = 5;

    public const TYPE_WALLET = 6;

    public const TYPE_GFTCRD = 7;

    public const TYPE_SUBPLAN = 18;

    public const STATUS_INPROCESS = 1;

    public const STATUS_COMPLETED = 2;

    public const STATUS_CANCELLED = 3;

    public const UNPAID = 0;

    public const ISPAID = 1;

    public const SERVICE_ONLINE = 0;

    public const SERVICE_OFFLINE = 1;

    public const LESSON_UNSCHEDULED = 1;

    public const LESSON_SCHEDULED = 2;

    public const LESSON_COMPLETED = 3;

    public const LESSON_CANCELLED = 4;

    public const BANK_PENDING = 0;

    public const BANK_APPROVED = 1;

    public const BANK_DECLINED = 2;

    public const TXN_MONEY_DEPOSIT = 11;

    /** @return array<int, string> */
    public static function orderTypes(bool $coursesEnabled = true, bool $groupClassesEnabled = true, bool $subscriptionPlanEnabled = true): array
    {
        $types = [
            self::TYPE_LESSON => 'Lesson',
            self::TYPE_SUBSCR => 'Recurring lessons',
            self::TYPE_GCLASS => 'Group classes',
            self::TYPE_PACKGE => 'Class packages',
            self::TYPE_COURSE => 'Course purchased',
            self::TYPE_WALLET => 'Wallet recharge',
            self::TYPE_GFTCRD => 'Giftcard purchased',
            self::TYPE_SUBPLAN => 'Subscription plan',
        ];
        if (! $coursesEnabled) {
            unset($types[self::TYPE_COURSE]);
        }
        if (! $groupClassesEnabled) {
            unset($types[self::TYPE_GCLASS], $types[self::TYPE_PACKGE]);
        }
        if (! $subscriptionPlanEnabled) {
            unset($types[self::TYPE_SUBPLAN]);
        }

        return $types;
    }

    public static function orderTypeLabel(int $type): string
    {
        return self::orderTypes()[$type] ?? '—';
    }

    /** @return array<int, string> */
    public static function orderStatuses(): array
    {
        return [
            self::STATUS_INPROCESS => 'In process',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function orderStatusLabel(int $status): string
    {
        return self::orderStatuses()[$status] ?? '—';
    }

    /** @return array<int, string> */
    public static function paymentStatuses(): array
    {
        return [
            self::UNPAID => 'Unpaid',
            self::ISPAID => 'Paid',
        ];
    }

    public static function paymentStatusLabel(int $status): string
    {
        return self::paymentStatuses()[$status] ?? '—';
    }

    /** @return array<int, string> */
    public static function serviceTypes(): array
    {
        return [
            self::SERVICE_ONLINE => 'Online',
            self::SERVICE_OFFLINE => 'Offline',
        ];
    }

    public static function serviceTypeLabel(int|string|null $offline): string
    {
        if ($offline === null || $offline === '') {
            return 'N/A';
        }

        return self::serviceTypes()[(int) $offline] ?? 'N/A';
    }

    /** @return array<int, string> */
    public static function lessonStatuses(): array
    {
        return [
            self::LESSON_UNSCHEDULED => 'Unscheduled',
            self::LESSON_SCHEDULED => 'Scheduled',
            self::LESSON_COMPLETED => 'Completed',
            self::LESSON_CANCELLED => 'Cancelled',
        ];
    }

    public static function lessonStatusLabel(int $status): string
    {
        return self::lessonStatuses()[$status] ?? '—';
    }

    /** @return array<int, string> */
    public static function courseOrderStatuses(): array
    {
        return [
            1 => 'Pending',
            2 => 'In progress',
            3 => 'Completed',
            4 => 'Cancelled',
        ];
    }

    public static function courseOrderStatusLabel(int $status): string
    {
        return self::courseOrderStatuses()[$status] ?? '—';
    }

    public static function formatOrderId(int $orderId): string
    {
        return 'O'.str_pad((string) $orderId, 6, '0', STR_PAD_LEFT);
    }

    public static function parseOrderId(string $value): int
    {
        return (int) preg_replace('/\D/', '', $value);
    }

    public static function formatMoney(float $amount, string $currency = ''): string
    {
        $formatted = number_format($amount, 2, '.', ',');

        return $currency !== '' ? $currency.' '.$formatted : $formatted;
    }

    public static function lessonNetAmount(float $amount, float $discount, float $rewardDiscount): float
    {
        return round($amount - $discount - $rewardDiscount, 2);
    }
}
