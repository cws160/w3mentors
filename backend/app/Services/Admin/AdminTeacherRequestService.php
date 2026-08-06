<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminTeacherRequestService
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_CANCELLED = 2;

    private const USER_TEACHER = 2;

    private const TYPE_TEACHER_APPROVAL_IMAGE = 1;

    private const TYPE_USER_PROFILE_IMAGE = 4;

    private const TYPE_USER_QUALIFICATION_FILE = 30;

    /** @return array<string, mixed> */
    public function view(int $requestId, int $langId = 1): array
    {
        $row = DB::table('tbl_teacher_requests as tr')
            ->join('tbl_users as u', 'u.user_id', '=', 'tr.tereq_user_id')
            ->whereNull('u.user_deleted')
            ->where('tr.tereq_id', $requestId)
            ->where('tr.tereq_step', 5)
            ->first([
                'tr.tereq_id as id',
                'tr.tereq_user_id as user_id',
                'tr.tereq_reference as reference',
                'tr.tereq_date as requested_on',
                'tr.tereq_status as status',
                'tr.tereq_comments as comments',
                'tr.tereq_first_name as first_name',
                'tr.tereq_last_name as last_name',
                'tr.tereq_gender as gender',
                'tr.tereq_phone_code as phone_code',
                'tr.tereq_phone_number as phone_number',
                'tr.tereq_video_link as video_link',
                'tr.tereq_biography as biography',
                'tr.tereq_teach_langs as teach_langs_json',
                'tr.tereq_speak_langs as speak_langs_json',
                'tr.tereq_slang_proficiency as speak_proficiency_json',
            ]);

        if (! $row) {
            throw new \RuntimeException('User or request not found', 404);
        }

        $teachLangIds = json_decode((string) ($row->teach_langs_json ?? '[]'), true) ?: [];
        $speakLangIds = json_decode((string) ($row->speak_langs_json ?? '[]'), true) ?: [];
        $speakProficiency = json_decode((string) ($row->speak_proficiency_json ?? '[]'), true) ?: [];

        $dialCode = '';
        if ($row->phone_code) {
            $dialCode = (string) (DB::table('tbl_countries')
                ->where('country_id', (int) $row->phone_code)
                ->value('country_dial_code') ?? '');
        }

        $profileFile = $this->latestFile(self::TYPE_TEACHER_APPROVAL_IMAGE, (int) $row->user_id)
            ?? $this->latestFile(self::TYPE_USER_PROFILE_IMAGE, (int) $row->user_id);
        $photoIdFile = $this->latestFile(2, (int) $row->user_id);

        $speakLanguages = [];
        foreach ($speakLangIds as $index => $slangId) {
            $name = $this->speakLanguageName((int) $slangId, $langId);
            $profId = (int) ($speakProficiency[$index] ?? 0);
            $proficiency = $profId > 0 ? $this->speakLevelName($profId, $langId) : '';
            $speakLanguages[] = trim($name.($proficiency !== '' ? ' : '.$proficiency : ''));
        }

        return [
            'id' => (int) $row->id,
            'user_id' => (int) $row->user_id,
            'reference' => (string) $row->reference,
            'requested_on' => (string) $row->requested_on,
            'status' => (int) $row->status,
            'status_label' => $this->statusLabel((int) $row->status),
            'comments' => (string) ($row->comments ?? ''),
            'first_name' => (string) $row->first_name,
                'last_name' => (string) ($row->last_name ?? ''),
            'gender' => $this->genderLabel((int) $row->gender),
            'phone_display' => trim($dialCode.' '.($row->phone_number ?? '')),
            'video_link' => (string) ($row->video_link ?? ''),
            'biography' => (string) ($row->biography ?? ''),
            'teach_languages' => $this->teachLanguageNames($teachLangIds, $langId),
            'speak_languages' => $speakLanguages,
            'profile_image_type' => $profileFile['file_type'] ?? self::TYPE_USER_PROFILE_IMAGE,
            'profile_image_user_id' => (int) $row->user_id,
            'photo_id_file_id' => $photoIdFile['file_id'] ?? null,
            'photo_id_file_name' => $photoIdFile['file_name'] ?? '',
        ];
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function qualifications(int $userId, Request $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = min(50, max(1, $request->integer('per_page', 10)));

        $query = DB::table('tbl_user_qualifications as uq')
            ->leftJoin('tbl_attached_files as file', function ($join) {
                $join->on('file.file_record_id', '=', 'uq.uqualification_id')
                    ->where('file.file_type', '=', self::TYPE_USER_QUALIFICATION_FILE);
            })
            ->where('uq.uqualification_user_id', $userId)
            ->select([
                'uq.uqualification_id as id',
                'uq.uqualification_experience_type as experience_type',
                'uq.uqualification_title as title',
                'uq.uqualification_description as description',
                'uq.uqualification_institute_name as institute_name',
                'uq.uqualification_institute_address as institute_address',
                'uq.uqualification_start_year as start_year',
                'uq.uqualification_end_year as end_year',
                'file.file_id',
                'file.file_name',
            ]);

        $total = (clone $query)->count();
        $rows = $query
            ->orderByDesc('uq.uqualification_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => (int) $row->id,
                    'experience_type' => $this->experienceTypeLabel((int) $row->experience_type),
                    'experience_years' => trim((string) ($row->start_year ?? '').'-'.(string) ($row->end_year ?? ''), '-'),
                    'title' => (string) ($row->title ?? ''),
                    'description' => (string) ($row->description ?? ''),
                    'institute' => trim((string) ($row->institute_name ?? '')."\n".(string) ($row->institute_address ?? '')),
                    'file_id' => $row->file_id ? (int) $row->file_id : null,
                    'file_name' => (string) ($row->file_name ?? ''),
                ];
            })
            ->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function statusForm(int $requestId): array
    {
        $exists = DB::table('tbl_teacher_requests')
            ->where('tereq_id', $requestId)
            ->where('tereq_step', 5)
            ->exists();

        if (! $exists) {
            throw new \RuntimeException('Invalid request', 404);
        }

        return [
            'request_id' => $requestId,
            'statuses' => [
                ['id' => self::STATUS_APPROVED, 'label_key' => 'LBL_Approved'],
                ['id' => self::STATUS_CANCELLED, 'label_key' => 'LBL_Cancelled_Teacher_Req'],
            ],
        ];
    }

    /** @param  array<string, mixed>  $data */
    public function updateStatus(int $requestId, array $data): void
    {
        $status = (int) ($data['status'] ?? -1);
        $comments = trim((string) ($data['comments'] ?? ''));

        if (! in_array($status, [self::STATUS_APPROVED, self::STATUS_CANCELLED], true)) {
            throw new \InvalidArgumentException('Please select a status.');
        }

        $requestRow = DB::table('tbl_teacher_requests as tr')
            ->join('tbl_users as u', 'u.user_id', '=', 'tr.tereq_user_id')
            ->leftJoin('tbl_user_settings as us', 'us.user_id', '=', 'u.user_id')
            ->where('tr.tereq_id', $requestId)
            ->where('tr.tereq_status', self::STATUS_PENDING)
            ->where('tr.tereq_step', 5)
            ->first([
                'tr.tereq_user_id',
                'tr.tereq_language_id',
                'tr.tereq_first_name',
                'tr.tereq_last_name',
                'tr.tereq_gender',
                'tr.tereq_phone_code',
                'tr.tereq_phone_number',
                'tr.tereq_video_link',
                'tr.tereq_biography',
                'tr.tereq_teach_langs',
                'tr.tereq_speak_langs',
                'tr.tereq_slang_proficiency',
            ]);

        if (! $requestRow) {
            throw new \RuntimeException('Invalid request', 404);
        }

        DB::transaction(function () use ($requestId, $status, $comments, $requestRow) {
            DB::table('tbl_teacher_requests')
                ->where('tereq_id', $requestId)
                ->update([
                    'tereq_status' => $status,
                    'tereq_comments' => $comments,
                    'tereq_status_updated' => now(),
                ]);

            if ($status !== self::STATUS_APPROVED) {
                return;
            }

            $userId = (int) $requestRow->tereq_user_id;

            DB::table('tbl_users')->where('user_id', $userId)->update([
                'user_is_teacher' => 1,
                'user_dashboard' => self::USER_TEACHER,
                'user_first_name' => $requestRow->tereq_first_name,
                'user_last_name' => $requestRow->tereq_last_name,
                'user_gender' => $requestRow->tereq_gender,
            ]);

            DB::table('tbl_user_settings')->updateOrInsert(
                ['user_id' => $userId],
                [
                    'user_id' => $userId,
                    'user_phone_code' => $requestRow->tereq_phone_code,
                    'user_phone_number' => $requestRow->tereq_phone_number,
                    'user_video_link' => $requestRow->tereq_video_link,
                    'user_registered_as' => self::USER_TEACHER,
                    'user_dashboard' => self::USER_TEACHER,
                ]
            );

            $langId = (int) ($requestRow->tereq_language_id ?: 1);
            DB::table('tbl_users_lang')->updateOrInsert(
                ['userlang_user_id' => $userId, 'userlang_lang_id' => $langId],
                ['user_biography' => $requestRow->tereq_biography ?? '']
            );

            $teachLangIds = json_decode((string) ($requestRow->tereq_teach_langs ?? '[]'), true) ?: [];
            foreach ($teachLangIds as $langIdValue) {
                $tlangId = (int) $langIdValue;
                if ($tlangId < 1) {
                    continue;
                }
                DB::table('tbl_user_teach_languages')->updateOrInsert(
                    ['utlang_user_id' => $userId, 'utlang_tlang_id' => $tlangId],
                    ['utlang_price' => null]
                );
            }

            $speakLangIds = json_decode((string) ($requestRow->tereq_speak_langs ?? '[]'), true) ?: [];
            $proficiency = json_decode((string) ($requestRow->tereq_slang_proficiency ?? '[]'), true) ?: [];
            foreach ($speakLangIds as $index => $langIdValue) {
                $slangId = (int) $langIdValue;
                if ($slangId < 1) {
                    continue;
                }
                DB::table('tbl_user_speak_languages')->updateOrInsert(
                    ['uslang_user_id' => $userId, 'uslang_slang_id' => $slangId],
                    ['uslang_proficiency' => (int) ($proficiency[$index] ?? 0)]
                );
            }

            DB::table('tbl_user_qualifications')
                ->where('uqualification_user_id', $userId)
                ->update(['uqualification_active' => 1]);

            $approvalImage = $this->latestFile(self::TYPE_TEACHER_APPROVAL_IMAGE, $userId);
            if ($approvalImage && ! empty($approvalImage['file_path'])) {
                $copy = $approvalImage;
                unset($copy['file_id']);
                $copy['file_type'] = self::TYPE_USER_PROFILE_IMAGE;
                DB::table('tbl_attached_files')->insert($copy);
            }
        });
    }

    public function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_CANCELLED => 'Cancelled',
            default => (string) $status,
        };
    }

    private function genderLabel(int $gender): string
    {
        return match ($gender) {
            1 => 'Male',
            2 => 'Female',
            3 => 'Non binary',
            4 => 'Prefer not to say',
            default => 'N/A',
        };
    }

    private function experienceTypeLabel(int $type): string
    {
        return match ($type) {
            1 => 'Education',
            2 => 'Certification',
            3 => 'Work Experience',
            default => 'N/A',
        };
    }

    /** @return array<int, string> */
    private function teachLanguageNames(array $ids, int $langId): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('tbl_teach_languages as tl')
            ->leftJoin('tbl_teach_languages_lang as tll', function ($join) use ($langId) {
                $join->on('tll.tlanglang_tlang_id', '=', 'tl.tlang_id')
                    ->where('tll.tlanglang_lang_id', '=', $langId);
            })
            ->whereIn('tl.tlang_id', array_map('intval', $ids))
            ->selectRaw('IFNULL(tll.tlang_name, tl.tlang_identifier) as name')
            ->pluck('name')
            ->all();
    }

    private function speakLanguageName(int $langId, int $uiLangId): string
    {
        return (string) (DB::table('tbl_speak_languages_lang')
            ->where('slanglang_slang_id', $langId)
            ->where('slanglang_lang_id', $uiLangId)
            ->value('slang_name') ?? '');
    }

    private function speakLevelName(int $levelId, int $langId): string
    {
        return (string) (DB::table('tbl_speak_language_levels_lang')
            ->where('slanglvllang_slanglvl_id', $levelId)
            ->where('slanglvllang_lang_id', $langId)
            ->value('slanglvl_name') ?? '');
    }

    /** @return array<string, mixed>|null */
    private function latestFile(int $fileType, int $recordId): ?array
    {
        $row = DB::table('tbl_attached_files')
            ->where('file_type', $fileType)
            ->where('file_record_id', $recordId)
            ->orderByDesc('file_id')
            ->first();

        return $row ? (array) $row : null;
    }
}
