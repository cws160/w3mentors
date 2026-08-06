<?php

namespace App\Services;

use App\Models\LanguageLabel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LessonListingService
{
    public const STATUS_UNSCHEDULED = 1;

    public const STATUS_SCHEDULED = 2;

    public const STATUS_COMPLETED = 3;

    public const STATUS_CANCELLED = 4;

    public const TYPE_FREE_TRIAL = 1;

    public const ORDER_PAID = 1;

    public const ORDER_COMPLETED = 2;

    /**
     * @param  array{status?: int, keyword?: string, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, groups: array<int, array{key: string, lessons: array<int, array<string, mixed>>}>}
     */
    public function list(int $userId, bool $isTeacher, int $langId, array $filters): array
    {
        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 20)));
        $page = max(1, (int) ($filters['page'] ?? 1));
        $status = isset($filters['status']) ? (int) $filters['status'] : self::STATUS_SCHEDULED;

        $query = DB::table('tbl_order_lessons as ordles')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordles.ordles_order_id')
            ->join('tbl_users as learner', 'learner.user_id', '=', 'orders.order_user_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'ordles.ordles_teacher_id')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'ordles.ordles_tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_countries_lang as learner_country', function ($join) use ($langId) {
                $join->on('learner_country.countrylang_country_id', '=', 'learner.user_country_id')
                    ->where('learner_country.countrylang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_countries_lang as teacher_country', function ($join) use ($langId) {
                $join->on('teacher_country.countrylang_country_id', '=', 'teacher.user_country_id')
                    ->where('teacher_country.countrylang_lang_id', '=', $langId);
            })
            ->where('orders.order_payment_status', self::ORDER_PAID)
            ->where('orders.order_status', self::ORDER_COMPLETED)
            ->whereNull('learner.user_deleted')
            ->whereNull('teacher.user_deleted');

        if ($isTeacher) {
            $query->where('ordles.ordles_teacher_id', $userId)
                ->where('teacher.user_is_teacher', 1);
        } else {
            $query->where('orders.order_user_id', $userId);
        }

        if ($status >= 0) {
            $query->where('ordles.ordles_status', $status);
        }

        if (! empty($filters['upcoming_only'])) {
            $query->where('ordles.ordles_lesson_starttime', '>=', now()->format('Y-m-d H:i:s'));
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword, $isTeacher) {
                if ($isTeacher) {
                    $q->whereRaw(
                        "CONCAT(learner.user_first_name, ' ', COALESCE(learner.user_last_name, '')) LIKE ?",
                        ['%'.$keyword.'%']
                    );
                } else {
                    $q->whereRaw(
                        "CONCAT(teacher.user_first_name, ' ', COALESCE(teacher.user_last_name, '')) LIKE ?",
                        ['%'.$keyword.'%']
                    );
                }
            });
        }

        if ($status === self::STATUS_UNSCHEDULED) {
            $query->orderByDesc('ordles.ordles_id');
        } elseif ($status >= 0) {
            $query->orderBy('ordles.ordles_lesson_starttime');
        } else {
            $query->orderBy('ordles.ordles_status')->orderBy('ordles.ordles_lesson_starttime');
        }

        $total = (clone $query)->count('ordles.ordles_id');
        $rows = $query
            ->forPage($page, $perPage)
            ->get([
                'ordles.ordles_id',
                'ordles.ordles_status',
                'ordles.ordles_duration',
                'ordles.ordles_amount',
                'ordles.ordles_offline',
                'ordles.ordles_type',
                'ordles.ordles_tlang_id',
                'ordles.ordles_lesson_starttime',
                'ordles.ordles_lesson_endtime',
                'ordles.ordles_address',
                'orders.order_user_id',
                'learner.user_id as learner_id',
                'learner.user_first_name as learner_first_name',
                'learner.user_last_name as learner_last_name',
                'teacher.user_id as teacher_id',
                'teacher.user_first_name as teacher_first_name',
                'teacher.user_last_name as teacher_last_name',
                'tlanglang.tlang_name as teach_lang_name',
                'learner_country.country_name as learner_country_name',
                'teacher_country.country_name as teacher_country_name',
            ]);

        $teachLangNames = $this->teachLangNamesById($langId);
        $labels = LanguageLabel::forLanguage($langId);
        $items = $rows->map(fn ($row) => $this->formatRow($row, $isTeacher, $teachLangNames, $labels))->all();
        $groups = $this->groupLessons(collect($items));

        return [
            'items' => $items,
            'groups' => $groups,
            'meta' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    /**
     * @param  array<int, string>  $teachLangNames
     * @param  array<string, string>  $labels
     * @return array<string, mixed>
     */
    private function formatRow(object $row, bool $isTeacher, array $teachLangNames, array $labels): array
    {
        $tlangId = (int) ($row->ordles_tlang_id ?? 0);
        $teachLang = $teachLangNames[$tlangId] ?? (string) ($row->teach_lang_name ?? '');
        if ((int) $row->ordles_type === self::TYPE_FREE_TRIAL) {
            $teachLang = $labels['LBL_FREE_TRIAL'] ?? 'Free trial';
        }

        $counterpartyId = $isTeacher ? (int) $row->learner_id : (int) $row->teacher_id;
        $firstName = $isTeacher ? (string) $row->learner_first_name : (string) $row->teacher_first_name;
        $lastName = $isTeacher ? (string) ($row->learner_last_name ?? '') : (string) ($row->teacher_last_name ?? '');
        $country = $isTeacher
            ? (string) ($row->learner_country_name ?? '')
            : (string) ($row->teacher_country_name ?? '');

        return [
            'id' => (int) $row->ordles_id,
            'status' => (int) $row->ordles_status,
            'duration' => (int) $row->ordles_duration,
            'amount' => (float) $row->ordles_amount,
            'offline' => (int) $row->ordles_offline === 1,
            'start_time' => $row->ordles_lesson_starttime
                ? (string) $row->ordles_lesson_starttime
                : null,
            'end_time' => $row->ordles_lesson_endtime
                ? (string) $row->ordles_lesson_endtime
                : null,
            'address' => (string) ($row->ordles_address ?? ''),
            'teach_language' => $teachLang,
            'lesson_title' => $this->lessonTitle($teachLang, (int) $row->ordles_duration, $labels),
            'counterparty' => [
                'id' => $counterpartyId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'full_name' => trim($firstName.' '.$lastName),
                'country_name' => $country,
            ],
            'can_schedule' => (int) $row->ordles_status === self::STATUS_UNSCHEDULED,
        ];
    }

    /**
     * @param  array<string, string>  $labels
     */
    private function lessonTitle(string $teachLang, int $duration, array $labels): string
    {
        $lang = $teachLang !== '' ? $teachLang : ($labels['LBL_LESSON'] ?? 'Lesson');
        $template = $labels['LBL_{teach-lang},_{n}_minutes_of_Lesson']
            ?? '{teach-lang}, {n} minutes of lesson';

        return str_replace(['{teach-lang}', '{n}'], [$lang, (string) $duration], $template);
    }

    /**
     * Hierarchical teach language names (legacy TeachLanguage::getTeachLangNames).
     *
     * @return array<int, string>
     */
    private function teachLangNamesById(int $langId): array
    {
        $rows = DB::table('tbl_teach_languages as tlang')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('tlang.tlang_active', 1)
            ->get([
                'tlang.tlang_id',
                'tlang.tlang_parent',
                DB::raw('COALESCE(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name'),
            ]);

        $byParent = [];
        foreach ($rows as $row) {
            $byParent[(int) $row->tlang_parent][] = $row;
        }

        return $this->flattenTeachLangTree($byParent, 0, null);
    }

    /**
     * @param  array<int, array<int, object>>  $byParent
     * @return array<int, string>
     */
    private function flattenTeachLangTree(array $byParent, int $parentId, ?string $parentPath): array
    {
        $out = [];
        foreach ($byParent[$parentId] ?? [] as $row) {
            $name = (string) $row->tlang_name;
            if ($parentPath !== null && $parentPath !== '') {
                $name = $parentPath.' » '.$name;
            }
            $out[(int) $row->tlang_id] = $name;
            $out += $this->flattenTeachLangTree($byParent, (int) $row->tlang_id, $name);
        }

        return $out;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $items
     * @return array<int, array{key: string, lessons: array<int, array<string, mixed>>}>
     */
    private function groupLessons(Collection $items): array
    {
        $grouped = [];
        foreach ($items as $lesson) {
            $key = $this->statusLabel((int) $lesson['status']);
            if (! empty($lesson['start_time'])) {
                $key = date('Y-m-d', strtotime((string) $lesson['start_time']));
            }
            if (! isset($grouped[$key])) {
                $grouped[$key] = [];
            }
            $grouped[$key][] = $lesson;
        }

        $out = [];
        foreach ($grouped as $key => $lessons) {
            $out[] = ['key' => (string) $key, 'lessons' => $lessons];
        }

        return $out;
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_UNSCHEDULED => 'Unscheduled',
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Canceled',
            default => 'Lesson',
        };
    }
}
