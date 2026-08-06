<?php

namespace App\Services;

use App\Models\Configuration;
use App\Models\User;
use App\Support\SlotPrice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TeacherBookingService
{
    public const LESSON_TYPE_REGULAR = 2;

    public const LESSON_TYPE_SUBSCRIPTION = 3;

    public function __construct(private TeacherAddressService $addresses)
    {
    }

    public function getLangSlots(int $teacherId, int $langId = 1): array
    {
        $managePrices = (int) Configuration::getValue('CONF_MANAGE_PRICES', 0);

        $rows = DB::table('tbl_teach_languages as tlang')
            ->join('tbl_user_teach_languages as utlang', 'utlang.utlang_tlang_id', '=', 'tlang.tlang_id')
            ->join('tbl_user_settings as user', 'user.user_id', '=', 'utlang.utlang_user_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('utlang.utlang_user_id', $teacherId)
            ->orderBy('tlanglang.tlang_name')
            ->get([
                'tlang.tlang_id',
                DB::raw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name'),
                DB::raw('IFNULL(user.user_slots, "") as user_slots'),
                DB::raw("IF({$managePrices}, tlang.tlang_hourly_price, utlang.utlang_price) as utlang_price"),
            ]);

        $languages = [];
        foreach ($rows as $row) {
            $slots = array_values(array_filter(array_map('intval', json_decode($row->user_slots, true) ?: [])));
            if ($slots === []) {
                continue;
            }
            $languages[] = [
                'id' => (int) $row->tlang_id,
                'name' => (string) $row->tlang_name,
                'hourly_price' => (float) $row->utlang_price,
                'slots' => $slots,
                'prices' => collect($slots)->mapWithKeys(fn (int $slot) => [
                    $slot => SlotPrice::calculate((float) $row->utlang_price, $slot),
                ])->all(),
            ];
        }

        return $languages;
    }

    public function getBookingOptions(int $teacherId, int $langId = 1, ?int $tlangId = null, ?int $duration = null): array
    {
        $languages = $this->getLangSlots($teacherId, $langId);
        if ($languages === []) {
            return ['languages' => [], 'defaults' => []];
        }

        $defaultLang = $tlangId && collect($languages)->contains('id', $tlangId)
            ? $tlangId
            : $languages[0]['id'];
        $lang = collect($languages)->firstWhere('id', $defaultLang);
        $slots = $lang['slots'] ?? [];
        $defaultDuration = $duration && in_array($duration, $slots, true)
            ? $duration
            : ($slots[0] ?? 15);

        $settings = DB::table('tbl_user_settings')->where('user_id', $teacherId)->first();
        $trialEnabled = (int) ($settings->user_trial_enabled ?? 0) === 1;
        $offlineEnabled = $this->addresses->teacherOfflineEnabled($teacherId);
        $defaultAddress = $offlineEnabled
            ? $this->addresses->defaultAddress($teacherId, $langId)
            : null;

        return [
            'languages' => $languages,
            'defaults' => [
                'ordles_tlang_id' => $defaultLang,
                'ordles_duration' => $defaultDuration,
                'ordles_quantity' => 1,
                'ordles_type' => self::LESSON_TYPE_REGULAR,
                'ordles_offline' => 0,
                'ordles_address_id' => $defaultAddress['id'] ?? 0,
            ],
            'trial_enabled' => $trialEnabled,
            'subscription_weeks' => (int) Configuration::getValue('CONF_RECURRING_SUBSCRIPTION_WEEKS', 4),
            'offline_sessions_enabled' => $offlineEnabled,
            'default_address' => $defaultAddress ? [
                'id' => $defaultAddress['id'],
                'formatted' => $defaultAddress['formatted'],
            ] : null,
        ];
    }

    public function resolveTeacher(string|int $teacher): ?User
    {
        $query = User::active()->verified()->teachers();
        if (is_numeric($teacher)) {
            $query->where('user_id', (int) $teacher);
        } else {
            $query->where('user_username', $teacher);
        }

        return $query->first();
    }

    public function pricingTable(int $teacherId, int $langId = 1): array
    {
        return $this->getLangSlots($teacherId, $langId);
    }
}
