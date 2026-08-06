<?php

namespace App\Support;

final class SlotPrice
{
    public static function calculate(float $hourlyPrice, int $durationMinutes): float
    {
        return round($hourlyPrice / 60 * $durationMinutes, 2);
    }
}
