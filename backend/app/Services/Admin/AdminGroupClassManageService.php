<?php

namespace App\Services\Admin;

use App\Models\Configuration;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminGroupClassManageService
{
    public const ADDED_BY_TEACHER = 1;

    public const ADDED_BY_ADMIN = 2;

    private const TYPE_GROUP_CLASS_BANNER = 55;

    private const TYPE_REGULAR = 1;

    private const TYPE_PACKAGE = 2;

    /** @return array<string, mixed> */
    public function createForm(int $langId = 1): array
    {
        return [
            'teach_languages' => $this->teachLanguages($langId),
            'site_languages' => $this->siteLanguages(),
            'durations' => $this->durationOptions(),
            'default_duration' => $this->defaultDuration(),
            'max_learners' => $this->maxLearners(),
            'offline_enabled' => $this->offlineEnabled(),
            'currency_code' => $this->systemCurrencyCode(),
            'service_types' => [
                ['value' => 0, 'label' => 'Online'],
                ['value' => 1, 'label' => 'Offline'],
            ],
        ];
    }

    /** @return array<int, array{id: int, full_name: string, email: string}> */
    public function teacherAutocomplete(string $keyword, int $limit = 20): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        return DB::table('tbl_users')
            ->where('user_is_teacher', 1)
            ->where('user_active', 1)
            ->whereNull('user_deleted')
            ->where(function ($q) use ($keyword) {
                $q->whereRaw('CONCAT(user_first_name, " ", COALESCE(user_last_name, "")) LIKE ?', ["%{$keyword}%"])
                    ->orWhere('user_email', 'like', "%{$keyword}%")
                    ->orWhere('user_username', 'like', "%{$keyword}%");
            })
            ->orderByRaw('CONCAT(user_first_name, " ", COALESCE(user_last_name, "")) ASC')
            ->limit(min(20, max(1, $limit)))
            ->get([
                'user_id as id',
                'user_email as email',
                DB::raw('CONCAT(user_first_name, " ", COALESCE(user_last_name, "")) as full_name'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'full_name' => trim((string) $row->full_name),
                'email' => (string) $row->email,
            ])
            ->all();
    }

    /** @return array<string, mixed>|null */
    public function viewDetails(int $classId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_group_classes as gc')
            ->leftJoin('tbl_group_classes_lang as gcl', function ($join) use ($langId) {
                $join->on('gcl.gclang_grpcls_id', '=', 'gc.grpcls_id')
                    ->where('gcl.gclang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'gc.grpcls_teacher_id')
            ->where('gc.grpcls_id', $classId)
            ->first([
                'gc.grpcls_id as id',
                DB::raw('IFNULL(gcl.grpcls_title, gc.grpcls_title) as title'),
                DB::raw('IFNULL(gcl.grpcls_description, gc.grpcls_description) as description'),
                'gc.grpcls_tlang_id as tlang_id',
                'gc.grpcls_offline as offline',
                'gc.grpcls_address_id as address_id',
                'teacher.user_email as teacher_email',
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as teacher_name'),
            ]);

        if (! $row) {
            return null;
        }

        $teacherName = trim((string) $row->teacher_name);
        $teacherEmail = (string) ($row->teacher_email ?? '');
        $teacherLabel = $teacherEmail !== ''
            ? $teacherName.' ('.$teacherEmail.')'
            : $teacherName;

        return [
            'id' => (int) $row->id,
            'title' => (string) $row->title,
            'description' => (string) $row->description,
            'teacher_label' => $teacherLabel,
            'language_label' => $this->teachLanguageLabel((int) $row->tlang_id, $langId),
            'service_type_label' => (int) $row->offline === 1 ? 'Offline' : 'Online',
            'class_address' => $this->classAddressLabel((int) $row->address_id, $langId, (int) $row->offline),
        ];
    }

    /** @return array<string, mixed>|null */
    public function show(int $classId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_group_classes as gc')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'gc.grpcls_teacher_id')
            ->where('gc.grpcls_id', $classId)
            ->first([
                'gc.grpcls_id as id',
                'gc.grpcls_slug as slug',
                'gc.grpcls_title as title',
                'gc.grpcls_description as description',
                'gc.grpcls_teacher_id as teacher_id',
                'gc.grpcls_tlang_id as tlang_id',
                'gc.grpcls_duration as duration',
                'gc.grpcls_start_datetime as start_at',
                'gc.grpcls_end_datetime as end_at',
                'gc.grpcls_total_seats as total_seats',
                'gc.grpcls_entry_fee as entry_fee',
                'gc.grpcls_offline as offline',
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as teacher_name'),
            ]);

        if (! $row) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'slug' => (string) $row->slug,
            'title' => (string) $row->title,
            'description' => (string) $row->description,
            'teacher_id' => (int) $row->teacher_id,
            'teacher_name' => trim((string) $row->teacher_name),
            'tlang_id' => (int) $row->tlang_id,
            'duration' => (int) $row->duration,
            'start_at' => (string) $row->start_at,
            'end_at' => (string) $row->end_at,
            'total_seats' => (int) $row->total_seats,
            'entry_fee' => (float) $row->entry_fee,
            'offline' => (int) $row->offline,
            'teach_languages' => $this->teachLanguages($langId),
            'site_languages' => $this->siteLanguages(),
            'durations' => $this->durationOptions(),
            'max_learners' => $this->maxLearners(),
            'offline_enabled' => $this->offlineEnabled(),
            'currency_code' => $this->systemCurrencyCode(),
            'has_banner' => $this->hasBanner($classId),
        ];
    }

    /** @param array<string, mixed> $data */
    public function store(array $data): array
    {
        return $this->saveGeneral(0, $data);
    }

    /** @param array<string, mixed> $data */
    public function update(int $classId, array $data): array
    {
        if ($classId <= 0) {
            return ['ok' => false, 'message' => 'Invalid class.'];
        }

        return $this->saveGeneral($classId, $data);
    }

    /** @return array<string, mixed>|null */
    public function langForm(int $classId, int $langId): ?array
    {
        if (! $this->classExists($classId)) {
            return null;
        }

        $row = DB::table('tbl_group_classes_lang')
            ->where('gclang_grpcls_id', $classId)
            ->where('gclang_lang_id', $langId)
            ->first(['grpcls_title as title', 'grpcls_description as description']);

        if (! $row) {
            $base = DB::table('tbl_group_classes')
                ->where('grpcls_id', $classId)
                ->first(['grpcls_title as title', 'grpcls_description as description']);
            $row = $base;
        }

        $packageClasses = DB::table('tbl_group_classes as gc')
            ->leftJoin('tbl_group_classes_lang as gcl', function ($join) use ($langId) {
                $join->on('gcl.gclang_grpcls_id', '=', 'gc.grpcls_id')
                    ->where('gcl.gclang_lang_id', '=', $langId);
            })
            ->where('gc.grpcls_parent', $classId)
            ->orderBy('gc.grpcls_start_datetime')
            ->orderBy('gc.grpcls_id')
            ->get([
                'gc.grpcls_id as id',
                DB::raw('IFNULL(gcl.grpcls_title, gc.grpcls_title) as title'),
            ])
            ->map(fn ($child) => ['id' => (int) $child->id, 'title' => (string) $child->title])
            ->all();

        return [
            'class_id' => $classId,
            'lang_id' => $langId,
            'title' => (string) ($row->title ?? ''),
            'description' => (string) ($row->description ?? ''),
            'package_classes' => $packageClasses,
            'site_languages' => $this->siteLanguages(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function storeLang(int $classId, int $langId, array $data): array
    {
        if (! $this->classExists($classId)) {
            return ['ok' => false, 'message' => 'Invalid class.'];
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));

        if ($title === '' || $description === '') {
            return ['ok' => false, 'message' => 'Please fill all required fields.'];
        }

        DB::table('tbl_group_classes_lang')->updateOrInsert(
            ['gclang_grpcls_id' => $classId, 'gclang_lang_id' => $langId],
            ['grpcls_title' => $title, 'grpcls_description' => $description],
        );

        $packageClasses = $data['package_classes'] ?? [];
        if (is_string($packageClasses)) {
            $decoded = json_decode($packageClasses, true);
            $packageClasses = is_array($decoded) ? $decoded : [];
        }
        if (is_array($packageClasses) && ! empty($packageClasses)) {
            $children = DB::table('tbl_group_classes')
                ->where('grpcls_parent', $classId)
                ->orderBy('grpcls_start_datetime')
                ->orderBy('grpcls_id')
                ->get(['grpcls_id', 'grpcls_description']);

            foreach ($children as $index => $child) {
                $childTitle = trim((string) ($packageClasses[$index]['title'] ?? ''));
                if ($childTitle === '') {
                    continue;
                }
                DB::table('tbl_group_classes_lang')->updateOrInsert(
                    ['gclang_grpcls_id' => (int) $child->grpcls_id, 'gclang_lang_id' => $langId],
                    ['grpcls_title' => $childTitle, 'grpcls_description' => (string) $child->grpcls_description],
                );
            }
        }

        return ['ok' => true, 'id' => $classId];
    }

    /** @return array<string, mixed>|null */
    public function mediaForm(int $classId): ?array
    {
        if (! $this->classExists($classId)) {
            return null;
        }

        return [
            'class_id' => $classId,
            'has_banner' => $this->hasBanner($classId),
            'site_languages' => $this->siteLanguages(),
        ];
    }

    public function uploadBanner(int $classId, UploadedFile $file): array
    {
        if (! $this->classExists($classId)) {
            return ['ok' => false, 'message' => 'Invalid class.'];
        }

        $ext = strtolower($file->getClientOriginalExtension());
        $allowed = ['png', 'jpg', 'jpeg', 'gif', 'webp'];
        if (! in_array($ext, $allowed, true)) {
            return ['ok' => false, 'message' => 'Invalid file type.'];
        }

        if ($file->getSize() > 4 * 1024 * 1024) {
            return ['ok' => false, 'message' => 'File is too large.'];
        }

        $uploadRoot = public_path('user-uploads');
        if (! is_dir($uploadRoot)) {
            @mkdir($uploadRoot, 0755, true);
        }

        $fileName = 'grpcls_'.$classId.'_'.time().'.'.$ext;
        $relativePath = date('Y/m').'/'.$fileName;
        $targetDir = $uploadRoot.'/'.dirname($relativePath);
        if (! is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $fileName);

        $oldFiles = DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_GROUP_CLASS_BANNER)
            ->where('file_record_id', $classId)
            ->get();

        DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_GROUP_CLASS_BANNER)
            ->where('file_record_id', $classId)
            ->delete();

        DB::table('tbl_attached_files')->insert([
            'file_type' => self::TYPE_GROUP_CLASS_BANNER,
            'file_lang_id' => 0,
            'file_record_id' => $classId,
            'file_name' => $fileName,
            'file_path' => $relativePath,
            'file_order' => 0,
            'file_added' => now()->format('Y-m-d H:i:s'),
        ]);

        foreach ($oldFiles as $old) {
            if (! empty($old->file_path)) {
                $oldPath = $uploadRoot.'/'.$old->file_path;
                if (is_file($oldPath)) {
                    @unlink($oldPath);
                }
            }
        }

        return ['ok' => true, 'id' => $classId];
    }

    public function createdByLabel(int $addedByType): string
    {
        return $addedByType === self::ADDED_BY_ADMIN ? 'Admin' : 'Teacher';
    }

    public function resolveCreatedByType(\Illuminate\Http\Request $request, array $row): int
    {
        if ($request->has('created_by_type') && isset($row['created_by_type'])) {
            return (int) $row['created_by_type'];
        }

        return self::ADDED_BY_TEACHER;
    }

    /** @param array<string, mixed> $data */
    private function saveGeneral(int $classId, array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $teacherId = (int) ($data['teacher_id'] ?? 0);
        $tlangId = (int) ($data['tlang_id'] ?? 0);
        $duration = (int) ($data['duration'] ?? 0);
        $totalSeats = max(1, (int) ($data['total_seats'] ?? 1));
        $offline = (int) ($data['offline'] ?? 0) === 1 ? 1 : 0;
        $entryFee = (float) ($data['entry_fee'] ?? 0);
        $classType = (int) ($data['class_type'] ?? self::TYPE_REGULAR);
        if (! in_array($classType, [self::TYPE_REGULAR, self::TYPE_PACKAGE], true)) {
            $classType = self::TYPE_REGULAR;
        }

        $packageClasses = [];
        if ($classType === self::TYPE_PACKAGE) {
            foreach ((array) ($data['package_classes'] ?? []) as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $packageClasses[] = [
                    'title' => trim((string) ($item['title'] ?? '')),
                    'start_at' => trim((string) ($item['start_at'] ?? '')),
                ];
            }
            if (empty($packageClasses)) {
                return ['ok' => false, 'message' => 'Please add a class.'];
            }
        }

        $startAt = $classType === self::TYPE_PACKAGE
            ? trim((string) ($packageClasses[0]['start_at'] ?? ''))
            : trim((string) ($data['start_at'] ?? ''));

        if ($title === '' || $slug === '' || $teacherId <= 0 || $tlangId <= 0 || $startAt === '' || $duration <= 0 || $description === '' || $entryFee <= 0) {
            return ['ok' => false, 'message' => 'Please fill all required fields.'];
        }

        if ($entryFee < 1 || $entryFee > 9999999999) {
            return ['ok' => false, 'message' => 'Invalid entry fee.'];
        }

        if ($totalSeats > $this->maxLearners()) {
            return ['ok' => false, 'message' => 'Max learners exceeds allowed limit.'];
        }

        $startTs = strtotime($startAt);
        if (! $startTs) {
            return ['ok' => false, 'message' => 'Invalid start time.'];
        }

        if ($classType === self::TYPE_PACKAGE) {
            foreach ($packageClasses as $item) {
                if ($item['title'] === '' || $item['start_at'] === '' || ! strtotime($item['start_at'])) {
                    return ['ok' => false, 'message' => 'Please fill all required fields.'];
                }
            }
        }

        $endAt = date('Y-m-d H:i:s', $startTs + ($duration * 60));

        $teacher = DB::table('tbl_users')
            ->where('user_id', $teacherId)
            ->where('user_is_teacher', 1)
            ->whereNull('user_deleted')
            ->exists();
        if (! $teacher) {
            return ['ok' => false, 'message' => 'Invalid teacher.'];
        }

        $slug = Str::slug($slug);
        if ($slug === '') {
            return ['ok' => false, 'message' => 'Invalid slug.'];
        }

        $slugQuery = DB::table('tbl_group_classes')->where('grpcls_slug', $slug);
        if ($classId > 0) {
            $slugQuery->where('grpcls_id', '!=', $classId);
        }
        if ($slugQuery->exists()) {
            return ['ok' => false, 'message' => 'Slug already exists.'];
        }

        $payload = [
            'grpcls_slug' => $slug,
            'grpcls_title' => $title,
            'grpcls_description' => $description,
            'grpcls_teacher_id' => $teacherId,
            'grpcls_tlang_id' => $tlangId,
            'grpcls_duration' => $duration,
            'grpcls_start_datetime' => date('Y-m-d H:i:s', $startTs),
            'grpcls_end_datetime' => $endAt,
            'grpcls_total_seats' => $totalSeats,
            'grpcls_entry_fee' => $entryFee,
            'grpcls_offline' => $offline,
        ];

        if ($classId > 0) {
            DB::table('tbl_group_classes')->where('grpcls_id', $classId)->update($payload);
        } else {
            $classId = DB::table('tbl_group_classes')->insertGetId(array_merge($payload, [
                'grpcls_type' => $classType,
                'grpcls_parent' => 0,
                'grpcls_booked_seats' => 0,
                'grpcls_status' => 1,
                'grpcls_metool_id' => 0,
                'grpcls_added_on' => now(),
                'grpcls_address_id' => null,
            ]));

            DB::table('tbl_group_classes_lang')->insert([
                'gclang_grpcls_id' => $classId,
                'gclang_lang_id' => 1,
                'grpcls_title' => $title,
                'grpcls_description' => $description,
            ]);
        }

        if ($classType === self::TYPE_PACKAGE) {
            $this->savePackageChildren($classId, $payload, $packageClasses);
        }

        return ['ok' => true, 'id' => $classId];
    }

    /**
     * @param array<string, mixed> $parentPayload
     * @param array<int, array{title: string, start_at: string}> $classes
     */
    private function savePackageChildren(int $parentId, array $parentPayload, array $classes): void
    {
        $oldChildIds = DB::table('tbl_group_classes')
            ->where('grpcls_parent', $parentId)
            ->pluck('grpcls_id')
            ->all();

        if (! empty($oldChildIds)) {
            DB::table('tbl_group_classes_lang')->whereIn('gclang_grpcls_id', $oldChildIds)->delete();
        }

        DB::table('tbl_group_classes')->where('grpcls_parent', $parentId)->delete();

        $duration = (int) ($parentPayload['grpcls_duration'] ?? 0);
        $starts = [];
        $ends = [];
        foreach ($classes as $index => $class) {
            $startTs = strtotime($class['start_at']);
            if (! $startTs) {
                continue;
            }

            $startAt = date('Y-m-d H:i:s', $startTs);
            $endAt = date('Y-m-d H:i:s', $startTs + ($duration * 60));
            $starts[] = $startAt;
            $ends[] = $endAt;

            $childId = DB::table('tbl_group_classes')->insertGetId(array_merge($parentPayload, [
                'grpcls_type' => self::TYPE_REGULAR,
                'grpcls_parent' => $parentId,
                'grpcls_slug' => $parentPayload['grpcls_slug'].'-class-'.$parentId.'-'.($index + 1),
                'grpcls_title' => $class['title'],
                'grpcls_description' => $parentPayload['grpcls_description'],
                'grpcls_start_datetime' => $startAt,
                'grpcls_end_datetime' => $endAt,
                'grpcls_entry_fee' => 0,
                'grpcls_booked_seats' => 0,
                'grpcls_status' => 1,
                'grpcls_metool_id' => 0,
                'grpcls_added_on' => now(),
                'grpcls_address_id' => null,
            ]));

            DB::table('tbl_group_classes_lang')->insert([
                'gclang_grpcls_id' => $childId,
                'gclang_lang_id' => 1,
                'grpcls_title' => $class['title'],
                'grpcls_description' => $parentPayload['grpcls_description'],
            ]);
        }

        if (! empty($starts) && ! empty($ends)) {
            DB::table('tbl_group_classes')->where('grpcls_id', $parentId)->update([
                'grpcls_start_datetime' => min($starts),
                'grpcls_end_datetime' => max($ends),
            ]);
        }
    }

    /** @return array<int, array{id: int, name: string}> */
    private function teachLanguages(int $langId): array
    {
        return DB::table('tbl_teach_languages as tl')
            ->leftJoin('tbl_teach_languages_lang as tll', function ($join) use ($langId) {
                $join->on('tll.tlanglang_tlang_id', '=', 'tl.tlang_id')
                    ->where('tll.tlanglang_lang_id', '=', $langId);
            })
            ->where('tl.tlang_active', 1)
            ->orderBy('tll.tlang_name')
            ->get([
                'tl.tlang_id as id',
                DB::raw('IFNULL(tll.tlang_name, tl.tlang_identifier) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    /** @return array<int, array{id: int, name: string}> */
    private function siteLanguages(): array
    {
        return DB::table('tbl_languages')
            ->where('language_active', 1)
            ->orderBy('language_id')
            ->get(['language_id as id', 'language_name as name'])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    /** @return array<int, string> */
    private function durationOptions(): array
    {
        $raw = (string) Configuration::getValue('CONF_GROUP_CLASS_DURATION', '15,30,45,60');
        $options = [];
        foreach (array_filter(array_map('trim', explode(',', $raw))) as $minutes) {
            $value = (int) $minutes;
            if ($value > 0) {
                $options[$value] = $value.' Minutes';
            }
        }

        return $options;
    }

    private function defaultDuration(): int
    {
        $options = $this->durationOptions();

        return (int) (array_key_first($options) ?: 15);
    }

    private function maxLearners(): int
    {
        return max(1, (int) Configuration::getValue('CONF_GROUP_CLASS_MAX_LEARNERS', 9999));
    }

    private function offlineEnabled(): bool
    {
        return (int) Configuration::getValue('CONF_ENABLE_OFFLINE_SESSIONS', 0) === 1;
    }

    private function systemCurrencyCode(): string
    {
        $currencyId = (int) Configuration::getValue('CONF_CURRENCY', 1);
        $code = DB::table('tbl_currencies')
            ->where('currency_id', $currencyId)
            ->value('currency_code');

        return $code ? (string) $code : 'USD';
    }

    private function teachLanguageLabel(int $tlangId, int $langId): string
    {
        if ($tlangId <= 0) {
            return '';
        }

        $row = DB::table('tbl_teach_languages as tl')
            ->leftJoin('tbl_teach_languages_lang as tll', function ($join) use ($langId) {
                $join->on('tll.tlanglang_tlang_id', '=', 'tl.tlang_id')
                    ->where('tll.tlanglang_lang_id', '=', $langId);
            })
            ->where('tl.tlang_id', $tlangId)
            ->first([
                'tl.tlang_parent',
                DB::raw('IFNULL(tll.tlang_name, tl.tlang_identifier) as name'),
            ]);

        if (! $row) {
            return '';
        }

        $name = (string) $row->name;
        $parentId = (int) $row->tlang_parent;
        if ($parentId > 0) {
            $parent = DB::table('tbl_teach_languages as tl')
                ->leftJoin('tbl_teach_languages_lang as tll', function ($join) use ($langId) {
                    $join->on('tll.tlanglang_tlang_id', '=', 'tl.tlang_id')
                        ->where('tll.tlanglang_lang_id', '=', $langId);
                })
                ->where('tl.tlang_id', $parentId)
                ->value(DB::raw('IFNULL(tll.tlang_name, tl.tlang_identifier)'));
            if ($parent) {
                $name = $parent.' » '.$name;
            }
        }

        return $name;
    }

    private function classAddressLabel(int $addressId, int $langId, int $offline): string
    {
        if ($offline !== 1 || $addressId <= 0) {
            return '';
        }

        $row = DB::table('tbl_user_addresses as usradd')
            ->join('tbl_states as st', 'st.state_id', '=', 'usradd.usradd_state_id')
            ->leftJoin('tbl_states_lang as stlang', function ($join) use ($langId) {
                $join->on('stlang.stlang_state_id', '=', 'st.state_id')
                    ->where('stlang.stlang_lang_id', '=', $langId);
            })
            ->join('tbl_countries as c', 'c.country_id', '=', 'usradd.usradd_country_id')
            ->leftJoin('tbl_countries_lang as clang', function ($join) use ($langId) {
                $join->on('clang.countrylang_country_id', '=', 'c.country_id')
                    ->where('clang.countrylang_lang_id', '=', $langId);
            })
            ->where('usradd.usradd_id', $addressId)
            ->whereNull('usradd.usradd_deleted')
            ->first([
                'usradd.usradd_address',
                'usradd.usradd_city',
                'usradd.usradd_zipcode',
                DB::raw('IFNULL(stlang.state_name, st.state_identifier) AS state_name'),
                DB::raw('IFNULL(clang.country_name, c.country_identifier) AS country_name'),
            ]);

        if (! $row) {
            return '';
        }

        return implode(', ', array_filter([
            (string) $row->usradd_address,
            (string) $row->usradd_city,
            (string) $row->state_name,
            (string) $row->country_name,
            (string) $row->usradd_zipcode,
        ]));
    }

    private function classExists(int $classId): bool
    {
        return DB::table('tbl_group_classes')->where('grpcls_id', $classId)->exists();
    }

    private function hasBanner(int $classId): bool
    {
        return DB::table('tbl_attached_files')
            ->where('file_type', self::TYPE_GROUP_CLASS_BANNER)
            ->where('file_record_id', $classId)
            ->exists();
    }
}
