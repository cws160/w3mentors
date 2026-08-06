<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\DB;

class StudentOfferService
{
    /**
     * @return array<string, mixed>|null
     */
    public function getOfferForm(int $teacherId, int $learnerId): ?array
    {
        $row = DB::table('tbl_offer_prices as offer')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'offer.offpri_learner_id')
            ->where('offer.offpri_teacher_id', $teacherId)
            ->where('offer.offpri_learner_id', $learnerId)
            ->whereNull('learner.user_deleted')
            ->first([
                'offer.offpri_id',
                'offer.offpri_lesson_price',
                'offer.offpri_class_price',
                'offer.offpri_package_price',
                'learner.user_first_name',
                'learner.user_last_name',
            ]);

        if (! $row) {
            return null;
        }

        $lessonPrices = $this->pricesByDuration((string) ($row->offpri_lesson_price ?? ''));
        $classPrices = $this->pricesByDuration((string) ($row->offpri_class_price ?? ''));

        return [
            'offpri_id' => (int) $row->offpri_id,
            'learner_id' => $learnerId,
            'learner_name' => trim((string) $row->user_first_name.' '.(string) ($row->user_last_name ?? '')),
            'lesson_slots' => $this->teacherLessonSlots($teacherId),
            'class_slots' => $this->classSlots(),
            'lesson_prices' => $lessonPrices,
            'class_prices' => $classPrices,
            'package_price' => $row->offpri_package_price !== null ? (float) $row->offpri_package_price : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     */
    public function saveOffer(int $teacherId, int $learnerId, array $input): bool
    {
        $existing = DB::table('tbl_offer_prices')
            ->where('offpri_teacher_id', $teacherId)
            ->where('offpri_learner_id', $learnerId)
            ->first(['offpri_id']);

        if (! $existing) {
            return false;
        }

        $lessonPrice = $this->buildPriceJson($input['lesson_prices'] ?? []);
        $classPrice = $this->buildPriceJson($input['class_prices'] ?? []);
        $packagePrice = $this->normalizePercent($input['package_price'] ?? null);

        DB::table('tbl_offer_prices')
            ->where('offpri_id', $existing->offpri_id)
            ->update([
                'offpri_lesson_price' => $lessonPrice,
                'offpri_class_price' => $classPrice,
                'offpri_package_price' => $packagePrice,
            ]);

        return true;
    }

    /**
     * @return array<int, int>
     */
    private function teacherLessonSlots(int $teacherId): array
    {
        $raw = DB::table('tbl_user_settings')
            ->where('user_id', $teacherId)
            ->value('user_slots');

        $slots = json_decode((string) ($raw ?? ''), true);

        return array_values(array_filter(array_map('intval', is_array($slots) ? $slots : [])));
    }

    /**
     * @return array<int, int>
     */
    private function classSlots(): array
    {
        $raw = (string) Configuration::getValue('CONF_GROUP_CLASS_DURATION', '15,30,45,60');

        return array_values(array_filter(array_map('intval', explode(',', $raw))));
    }

    /**
     * @return array<string, float>
     */
    private function pricesByDuration(string $json): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            $duration = (int) ($item['duration'] ?? 0);
            $offer = $this->normalizePercent($item['offer'] ?? null);
            if ($duration > 0 && $offer !== null) {
                $out[(string) $duration] = $offer;
            }
        }

        return $out;
    }

    /**
     * @param  array<string|int, mixed>  $prices
     */
    private function buildPriceJson(array $prices): ?string
    {
        $items = [];
        foreach ($prices as $duration => $offer) {
            $value = $this->normalizePercent($offer);
            if ($value === null) {
                continue;
            }
            $items[] = [
                'duration' => (int) $duration,
                'offer' => $value,
            ];
        }

        return $items === [] ? null : json_encode($items);
    }

    private function normalizePercent(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $num = round((float) $value, 2);
        if ($num < 1 || $num > 100) {
            return null;
        }

        return $num;
    }
}
