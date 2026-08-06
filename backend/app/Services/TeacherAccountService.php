<?php

namespace App\Services;

use App\Models\Configuration;
use App\Support\SlotPrice;
use Illuminate\Support\Facades\DB;

class TeacherAccountService
{
    private const PREF_TYPE_ACCENTS = 1;
    private const PREF_TYPE_TEACHES_LEVEL = 2;
    private const PREF_TYPE_LEARNER_AGES = 3;
    private const PREF_TYPE_LESSONS = 4;
    private const PREF_TYPE_TEST_PREPARATIONS = 6;

    public function getProfile(int $userId, int $langId): array
    {
        return [
            'progress' => $this->getProfileProgress($userId),
            'languages' => $this->getLanguagesSection($userId, $langId),
            'prices' => $this->getPricesSection($userId, $langId),
            'qualifications' => $this->getQualifications($userId),
            'preferences' => $this->getPreferencesSection($userId, $langId),
        ];
    }

    public function updateLanguages(int $userId, int $langId, array $teachLangIds, array $speakLanguages): void
    {
        $teachLangIds = array_values(array_unique(array_map('intval', $teachLangIds)));
        $speakLanguages = array_values(array_filter($speakLanguages, fn ($row) => ! empty($row['slang_id'])));

        if (empty($teachLangIds)) {
            throw new \InvalidArgumentException('At least one teach language is required.');
        }
        if (empty($speakLanguages)) {
            throw new \InvalidArgumentException('At least one speak language is required.');
        }

        $validCount = DB::table('tbl_teach_languages')
            ->whereIn('tlang_id', $teachLangIds)
            ->where('tlang_subcategories', 0)
            ->where('tlang_active', 1)
            ->count();
        if ($validCount !== count($teachLangIds)) {
            throw new \InvalidArgumentException('Invalid teach language selection.');
        }

        $speakIds = array_map(fn ($row) => (int) $row['slang_id'], $speakLanguages);
        $validSpeak = DB::table('tbl_speak_languages')
            ->whereIn('slang_id', $speakIds)
            ->where('slang_active', 1)
            ->count();
        if ($validSpeak !== count(array_unique($speakIds))) {
            throw new \InvalidArgumentException('Invalid speak language selection.');
        }

        DB::transaction(function () use ($userId, $teachLangIds, $speakLanguages) {
            $this->deleteTeachLanguagesNotIn($userId, $teachLangIds);
            foreach ($teachLangIds as $tlangId) {
                $exists = DB::table('tbl_user_teach_languages')
                    ->where('utlang_user_id', $userId)
                    ->where('utlang_tlang_id', $tlangId)
                    ->exists();
                if (! $exists) {
                    DB::table('tbl_user_teach_languages')->insert([
                        'utlang_user_id' => $userId,
                        'utlang_tlang_id' => $tlangId,
                        'utlang_price' => null,
                    ]);
                }
            }

            $keepSpeakIds = array_map(fn ($row) => (int) $row['slang_id'], $speakLanguages);
            DB::table('tbl_user_speak_languages')
                ->where('uslang_user_id', $userId)
                ->whereNotIn('uslang_slang_id', $keepSpeakIds)
                ->delete();

            foreach ($speakLanguages as $row) {
                DB::table('tbl_user_speak_languages')->updateOrInsert(
                    [
                        'uslang_user_id' => $userId,
                        'uslang_slang_id' => (int) $row['slang_id'],
                    ],
                    ['uslang_proficiency' => (int) ($row['proficiency'] ?? 0)]
                );
            }

            $this->syncSpeakLangStat($userId);
            $this->syncTeachLangPriceStat($userId);
        });
    }

    public function updatePrices(int $userId, array $prices, array $slots): void
    {
        $managePrices = (int) Configuration::getValue('CONF_MANAGE_PRICES', 0);
        $slots = array_values(array_unique(array_map('intval', $slots)));

        if (empty($slots)) {
            throw new \InvalidArgumentException('At least one lesson duration is required.');
        }

        $activeSlots = $this->getActiveSlotDurations();
        foreach ($slots as $slot) {
            if (! in_array($slot, $activeSlots, true)) {
                throw new \InvalidArgumentException('Invalid lesson duration.');
            }
        }

        DB::transaction(function () use ($userId, $prices, $slots, $managePrices) {
            if (! $managePrices && ! empty($prices)) {
                foreach ($prices as $utlangId => $price) {
                    $utlangId = (int) $utlangId;
                    $row = DB::table('tbl_user_teach_languages as utlang')
                        ->join('tbl_teach_languages as tlang', 'tlang.tlang_id', '=', 'utlang.utlang_tlang_id')
                        ->where('utlang.utlang_id', $utlangId)
                        ->where('utlang.utlang_user_id', $userId)
                        ->select(['utlang.utlang_id', 'tlang.tlang_min_price', 'tlang.tlang_max_price'])
                        ->first();
                    if (! $row) {
                        continue;
                    }
                    $price = (float) $price;
                    $min = max((float) $row->tlang_min_price, 1);
                    $max = (float) $row->tlang_max_price;
                    if ($price < $min || $price > $max) {
                        throw new \InvalidArgumentException('Price is outside allowed range.');
                    }
                    DB::table('tbl_user_teach_languages')
                        ->where('utlang_id', $utlangId)
                        ->where('utlang_user_id', $userId)
                        ->update(['utlang_price' => $price]);
                }
            }

            DB::table('tbl_user_settings')->updateOrInsert(
                ['user_id' => $userId],
                ['user_slots' => json_encode($slots)]
            );

            $this->syncTeachLangPriceStat($userId);
        });
    }

    public function updatePreferences(int $userId, array $preferenceIds): void
    {
        $preferenceIds = array_values(array_unique(array_map('intval', $preferenceIds)));

        if (! empty($preferenceIds)) {
            $valid = DB::table('tbl_preferences')->whereIn('prefer_id', $preferenceIds)->count();
            if ($valid !== count($preferenceIds)) {
                throw new \InvalidArgumentException('Invalid preference selection.');
            }
        }

        DB::transaction(function () use ($userId, $preferenceIds) {
            DB::table('tbl_user_preferences')->where('uprefer_user_id', $userId)->delete();
            foreach ($preferenceIds as $preferId) {
                DB::table('tbl_user_preferences')->insert([
                    'uprefer_user_id' => $userId,
                    'uprefer_prefer_id' => $preferId,
                ]);
            }
            $this->syncPreferenceStat($userId, count($preferenceIds) > 0 ? 1 : 0);
        });
    }

    public function saveQualification(int $userId, array $data, ?int $id = null): array
    {
        $id = $id ? (int) $id : 0;
        if ($id > 0) {
            $exists = DB::table('tbl_user_qualifications')
                ->where('uqualification_id', $id)
                ->where('uqualification_user_id', $userId)
                ->exists();
            if (! $exists) {
                throw new \InvalidArgumentException('Qualification not found.');
            }
        }

        $payload = [
            'uqualification_user_id' => $userId,
            'uqualification_experience_type' => (int) $data['experience_type'],
            'uqualification_title' => (string) $data['title'],
            'uqualification_institute_name' => (string) $data['institute_name'],
            'uqualification_institute_address' => (string) $data['institute_address'],
            'uqualification_description' => (string) ($data['description'] ?? ''),
            'uqualification_start_year' => (int) $data['start_year'],
            'uqualification_end_year' => (int) $data['end_year'],
            'uqualification_active' => 1,
        ];

        if ($id > 0) {
            DB::table('tbl_user_qualifications')
                ->where('uqualification_id', $id)
                ->where('uqualification_user_id', $userId)
                ->update($payload);
        } else {
            $id = (int) DB::table('tbl_user_qualifications')->insertGetId($payload);
        }

        $this->syncQualificationStat($userId);

        return $this->formatQualification(
            DB::table('tbl_user_qualifications')->where('uqualification_id', $id)->first()
        );
    }

    public function deleteQualification(int $userId, int $id): void
    {
        $deleted = DB::table('tbl_user_qualifications')
            ->where('uqualification_id', $id)
            ->where('uqualification_user_id', $userId)
            ->delete();
        if (! $deleted) {
            throw new \InvalidArgumentException('Qualification not found.');
        }
        $this->syncQualificationStat($userId);
    }

    public function getProfileProgress(int $userId): array
    {
        $row = DB::table('tbl_users as user')
            ->leftJoin('tbl_teacher_stats as testat', 'testat.testat_user_id', '=', 'user.user_id')
            ->where('user.user_id', $userId)
            ->where('user.user_is_teacher', 1)
            ->select([
                DB::raw('IF(IFNULL(testat.testat_minprice,0) > 0 AND IFNULL(testat.testat_maxprice,0) > 0, 1, 0) as price_count'),
                DB::raw('IF(IFNULL(testat.testat_teachlang,0) = 1 AND IFNULL(testat.testat_speaklang,0) = 1, 1, 0) as languages_count'),
                DB::raw('IF(user.user_country_id > 0 AND user.user_timezone != "" AND user.user_username != "", 1, 0) as general_profile'),
                DB::raw('IFNULL(testat.testat_preference, 0) as preference_count'),
                DB::raw('IFNULL(testat.testat_qualification, 0) as qualification_count'),
                DB::raw('IFNULL(testat.testat_availability, 0) as general_availability_count'),
            ])
            ->first();

        if (! $row) {
            return [
                'percentage' => 0,
                'total_fields' => 0,
                'total_filled' => 0,
                'is_completed' => false,
                'sections' => [],
            ];
        }

        $fields = [
            'price_count' => (int) $row->price_count,
            'languages_count' => (int) $row->languages_count,
            'general_profile' => (int) $row->general_profile,
            'preference_count' => (int) $row->preference_count,
            'qualification_count' => (int) $row->qualification_count,
            'general_availability_count' => (int) $row->general_availability_count,
        ];
        $totalFields = count($fields);
        $totalFilled = array_sum($fields);

        return [
            'percentage' => $totalFields > 0 ? round(($totalFilled * 100) / $totalFields, 2) : 0,
            'total_fields' => $totalFields,
            'total_filled' => $totalFilled,
            'is_completed' => $totalFields === $totalFilled,
            'sections' => [
                'general_profile' => $fields['general_profile'],
                'languages' => $fields['languages_count'],
                'price' => $fields['price_count'],
                'qualification' => $fields['qualification_count'],
                'preference' => $fields['preference_count'],
                'availability' => $fields['general_availability_count'],
            ],
        ];
    }

    private function getLanguagesSection(int $userId, int $langId): array
    {
        $selectedTeach = DB::table('tbl_user_teach_languages')
            ->where('utlang_user_id', $userId)
            ->pluck('utlang_tlang_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $speakOptions = DB::table('tbl_speak_languages as slang')
            ->leftJoin('tbl_speak_languages_lang as slanglang', function ($join) use ($langId) {
                $join->on('slanglang.slanglang_slang_id', '=', 'slang.slang_id')
                    ->where('slanglang.slanglang_lang_id', '=', $langId);
            })
            ->where('slang.slang_active', 1)
            ->orderByRaw('IFNULL(slanglang.slang_name, slang.slang_identifier)')
            ->get([
                'slang.slang_id as id',
                DB::raw('IFNULL(slanglang.slang_name, slang.slang_identifier) as name'),
            ]);

        $selectedSpeak = DB::table('tbl_user_speak_languages as uslang')
            ->leftJoin('tbl_speak_language_levels_lang as sll', function ($join) use ($langId) {
                $join->on('sll.slanglvllang_slanglvl_id', '=', 'uslang.uslang_proficiency')
                    ->where('sll.slanglvllang_lang_id', '=', $langId);
            })
            ->where('uslang.uslang_user_id', $userId)
            ->get([
                'uslang.uslang_slang_id as slang_id',
                'uslang.uslang_proficiency as proficiency',
                DB::raw('IFNULL(sll.slanglvl_name, "") as proficiency_name'),
            ]);

        $proficiencyLevels = DB::table('tbl_speak_language_levels as sllv')
            ->leftJoin('tbl_speak_language_levels_lang as sllvl', function ($join) use ($langId) {
                $join->on('sllvl.slanglvllang_slanglvl_id', '=', 'sllv.slanglvl_id')
                    ->where('sllvl.slanglvllang_lang_id', '=', $langId);
            })
            ->where('sllv.slanglvl_active', 1)
            ->orderBy('sllv.slanglvl_order')
            ->get([
                'sllv.slanglvl_id as id',
                DB::raw('IFNULL(sllvl.slanglvl_name, sllv.slanglvl_identifier) as name'),
            ]);

        return [
            'teach_language_tree' => $this->buildTeachLanguageTree($langId, 0),
            'selected_teach_lang_ids' => $selectedTeach,
            'speak_languages' => $speakOptions,
            'selected_speak' => $selectedSpeak,
            'proficiency_levels' => $proficiencyLevels,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildTeachLanguageTree(int $langId, int $parentId): array
    {
        $rows = DB::table('tbl_teach_languages as tlang')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('tlang.tlang_active', 1)
            ->where('tlang.tlang_parent', $parentId)
            ->whereNotNull('tlang.tlang_slug')
            ->orderBy('tlang.tlang_order')
            ->orderByDesc('tlang.tlang_id')
            ->get([
                'tlang.tlang_id as id',
                DB::raw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as name'),
                'tlang.tlang_subcategories as subcategories',
                'tlang.tlang_available as available',
                'tlang.tlang_level as level',
            ]);

        $tree = [];
        foreach ($rows as $row) {
            $node = [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'subcategories' => (int) $row->subcategories,
                'available' => (int) $row->available,
                'level' => (int) $row->level,
                'children' => [],
            ];
            if ($node['subcategories'] > 0) {
                $node['children'] = $this->buildTeachLanguageTree($langId, $node['id']);
            }
            $tree[] = $node;
        }

        return $tree;
    }

    private function getPricesSection(int $userId, int $langId): array
    {
        $managePrices = (int) Configuration::getValue('CONF_MANAGE_PRICES', 0);
        $adminManage = $managePrices === 1;

        $userLangs = DB::table('tbl_teach_languages as tlang')
            ->join('tbl_user_teach_languages as utlang', 'utlang.utlang_tlang_id', '=', 'tlang.tlang_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('utlang.utlang_user_id', $userId)
            ->orderByRaw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier)')
            ->get([
                'utlang.utlang_id',
                'tlang.tlang_id',
                'tlang.tlang_min_price',
                'tlang.tlang_max_price',
                DB::raw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as name'),
                DB::raw($adminManage
                    ? 'tlang.tlang_hourly_price as price'
                    : 'utlang.utlang_price as price'),
            ]);

        $settings = DB::table('tbl_user_settings')->where('user_id', $userId)->first();
        $selectedSlots = json_decode($settings?->user_slots ?? '[]', true) ?: [];
        $selectedSlots = array_map('intval', $selectedSlots);

        $currencyId = (int) Configuration::getValue('CONF_CURRENCY', 1);
        $currency = DB::table('tbl_currencies')->where('currency_id', $currencyId)->first();

        return [
            'manage_prices' => $adminManage,
            'user_languages' => $userLangs->map(fn ($row) => [
                'utlang_id' => (int) $row->utlang_id,
                'tlang_id' => (int) $row->tlang_id,
                'name' => (string) $row->name,
                'price' => $row->price !== null ? (float) $row->price : null,
                'min_price' => max((float) $row->tlang_min_price, 1),
                'max_price' => (float) $row->tlang_max_price,
            ]),
            'slot_options' => $this->getActiveSlotDurations(),
            'selected_slots' => $selectedSlots,
            'currency_code' => $currency?->currency_code ?? 'USD',
        ];
    }

    private const QUALIFICATION_FILE_TYPE = 30;

    private function getQualifications(int $userId): array
    {
        $rows = DB::table('tbl_user_qualifications as uq')
            ->leftJoin('tbl_attached_files as file', function ($join) {
                $join->on('file.file_record_id', '=', 'uq.uqualification_id')
                    ->where('file.file_type', '=', self::QUALIFICATION_FILE_TYPE);
            })
            ->where('uq.uqualification_user_id', $userId)
            ->where('uq.uqualification_active', 1)
            ->orderByDesc('uq.uqualification_id')
            ->select(['uq.*', 'file.file_name'])
            ->get();

        return $rows->map(fn ($row) => $this->formatQualification($row))->all();
    }

    private function formatQualification(object $row): array
    {
        return [
            'id' => (int) $row->uqualification_id,
            'experience_type' => (int) $row->uqualification_experience_type,
            'title' => (string) $row->uqualification_title,
            'institute_name' => (string) $row->uqualification_institute_name,
            'institute_address' => (string) $row->uqualification_institute_address,
            'description' => (string) ($row->uqualification_description ?? ''),
            'start_year' => (int) $row->uqualification_start_year,
            'end_year' => (int) $row->uqualification_end_year,
            'file_name' => isset($row->file_name) ? (string) $row->file_name : null,
        ];
    }

    private function getPreferencesSection(int $userId, int $langId): array
    {
        $options = DB::table('tbl_preferences as p')
            ->leftJoin('tbl_preferences_lang as pl', function ($join) use ($langId) {
                $join->on('pl.preferlang_prefer_id', '=', 'p.prefer_id')
                    ->where('pl.preferlang_lang_id', '=', $langId);
            })
            ->orderBy('p.prefer_order')
            ->get([
                'p.prefer_id as id',
                'p.prefer_type as type',
                DB::raw('IFNULL(pl.prefer_title, p.prefer_identifier) as title'),
            ]);

        $selected = DB::table('tbl_user_preferences')
            ->where('uprefer_user_id', $userId)
            ->pluck('uprefer_prefer_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $groups = [];
        foreach ($options as $option) {
            $type = (int) $option->type;
            if (! isset($groups[$type])) {
                $groups[$type] = [
                    'type' => $type,
                    'options' => [],
                    'selected_ids' => [],
                ];
            }
            $groups[$type]['options'][] = [
                'id' => (int) $option->id,
                'title' => (string) $option->title,
            ];
            if (in_array((int) $option->id, $selected, true)) {
                $groups[$type]['selected_ids'][] = (int) $option->id;
            }
        }
        ksort($groups);

        return array_values($groups);
    }

    public function getExperienceTypes(): array
    {
        return [
            ['id' => 1, 'key' => 'LBL_Education'],
            ['id' => 2, 'key' => 'LBL_Certification'],
            ['id' => 3, 'key' => 'LBL_Work_Experience'],
        ];
    }

    private function deleteTeachLanguagesNotIn(int $userId, array $keepTlangIds): void
    {
        DB::table('tbl_user_teach_languages')
            ->where('utlang_user_id', $userId)
            ->when($keepTlangIds, fn ($q) => $q->whereNotIn('utlang_tlang_id', $keepTlangIds))
            ->delete();
    }

    private function syncTeachLangPriceStat(int $userId): void
    {
        $settings = DB::table('tbl_user_settings')->where('user_id', $userId)->first();
        $slots = json_decode($settings?->user_slots ?? '[]', true) ?: [];
        $rows = DB::table('tbl_user_teach_languages')
            ->where('utlang_user_id', $userId)
            ->get(['utlang_price']);

        $priceSets = [];
        foreach ($rows as $row) {
            foreach ($slots as $slot) {
                $priceSets[] = SlotPrice::calculate((float) ($row->utlang_price ?? 0), (int) $slot);
            }
        }

        DB::table('tbl_teacher_stats')->updateOrInsert(
            ['testat_user_id' => $userId],
            [
                'testat_teachlang' => $rows->count() > 0 ? 1 : 0,
                'testat_minprice' => count($priceSets) > 0 ? min($priceSets) : 0,
                'testat_maxprice' => count($priceSets) > 0 ? max($priceSets) : 0,
            ]
        );
    }

    private function syncSpeakLangStat(int $userId): void
    {
        $has = DB::table('tbl_user_speak_languages')->where('uslang_user_id', $userId)->exists();
        DB::table('tbl_teacher_stats')->updateOrInsert(
            ['testat_user_id' => $userId],
            ['testat_speaklang' => $has ? 1 : 0]
        );
    }

    private function syncPreferenceStat(int $userId, int $value): void
    {
        DB::table('tbl_teacher_stats')->updateOrInsert(
            ['testat_user_id' => $userId],
            ['testat_preference' => $value]
        );
    }

    private function syncQualificationStat(int $userId): void
    {
        $has = DB::table('tbl_user_qualifications')
            ->where('uqualification_user_id', $userId)
            ->exists();
        DB::table('tbl_teacher_stats')->updateOrInsert(
            ['testat_user_id' => $userId],
            ['testat_qualification' => $has ? 1 : 0]
        );
    }

    /** @return int[] */
    private function getActiveSlotDurations(): array
    {
        $raw = (string) Configuration::getValue('CONF_PAID_LESSON_DURATION', '60');
        $slots = array_filter(array_map('intval', explode(',', $raw)));

        return $slots ?: [60];
    }
}
