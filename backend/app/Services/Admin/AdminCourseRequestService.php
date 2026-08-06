<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminCourseRequestService
{
    private const STATUS_PENDING = 0;

    private const STATUS_APPROVED = 1;

    private const STATUS_DECLINED = 2;

    private const COURSE_DRAFTED = 1;

    private const COURSE_PUBLISHED = 3;

    /** @return array<string, mixed>|null */
    public function show(int $requestId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_course_approval_requests as coapre')
            ->join('tbl_courses as course', 'course.course_id', '=', 'coapre.coapre_course_id')
            ->join('tbl_users as u', 'u.user_id', '=', 'course.course_user_id')
            ->leftJoin('tbl_categories_lang as cat', function ($join) use ($langId) {
                $join->on('cat.catelang_cate_id', '=', 'coapre.coapre_cate_id')
                    ->where('cat.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories_lang as subcat', function ($join) use ($langId) {
                $join->on('subcat.catelang_cate_id', '=', 'coapre.coapre_subcate_id')
                    ->where('subcat.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_course_languages as clang', 'clang.clang_id', '=', 'coapre.coapre_clang_id')
            ->leftJoin('tbl_course_languages_lang as clanglang', function ($join) use ($langId) {
                $join->on('clanglang.clanglang_clang_id', '=', 'coapre.coapre_clang_id')
                    ->where('clanglang.clanglang_lang_id', '=', $langId);
            })
            ->where('coapre.coapre_id', $requestId)
            ->select([
                'coapre.coapre_id as id',
                'coapre.coapre_course_id as course_id',
                'coapre.coapre_status as status',
                'coapre.coapre_remark as remark',
                'coapre.coapre_created as created_at',
                'coapre.coapre_title as title',
                'coapre.coapre_subtitle as subtitle',
                'coapre.coapre_details as details',
                'coapre.coapre_price as price',
                'coapre.coapre_duration as duration',
                'coapre.coapre_level as level',
                'coapre.coapre_certificate as certificate',
                'coapre.coapre_certificate_type as certificate_type',
                'coapre.coapre_preview_video as preview_video',
                'coapre.coapre_learners as learners',
                'coapre.coapre_learnings as learnings',
                'coapre.coapre_requirements as requirements',
                'coapre.coapre_srchtags as search_tags',
                'coapre.coapre_quilin_id as quiz_id',
                'u.user_first_name as teacher_first_name',
                'u.user_last_name as teacher_last_name',
                'u.user_gender as teacher_gender',
                DB::raw('IFNULL(cat.cate_name, "") as category_name'),
                DB::raw('IFNULL(subcat.cate_name, "") as subcategory_name'),
                DB::raw('IFNULL(clanglang.clang_name, clang.clang_identifier) as language_name'),
                'u.user_email as teacher_email',
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $quizTitle = '';
        if ((int) ($row->quiz_id ?? 0) > 0) {
            $quizTitle = (string) (DB::table('tbl_quiz_linked')
                ->where('quilin_id', (int) $row->quiz_id)
                ->value('quilin_title') ?? '');
        }

        return [
            'id' => (int) $row->id,
            'course_id' => (int) $row->course_id,
            'status' => (int) $row->status,
            'status_label' => $this->statusLabel((int) $row->status),
            'remark' => (string) ($row->remark ?? ''),
            'created_at' => (string) ($row->created_at ?? ''),
            'title' => (string) ($row->title ?? ''),
            'subtitle' => (string) ($row->subtitle ?? ''),
            'details' => (string) ($row->details ?? ''),
            'price' => (float) ($row->price ?? 0),
            'duration' => (int) ($row->duration ?? 0),
            'level' => (int) ($row->level ?? 0),
            'level_label' => $this->levelLabel((int) ($row->level ?? 0)),
            'certificate' => (int) ($row->certificate ?? 0),
            'certificate_label' => $this->yesNoLabel((int) ($row->certificate ?? 0)),
            'certificate_type' => (int) ($row->certificate_type ?? 0),
            'certificate_type_label' => $this->certificateTypeLabel((int) ($row->certificate_type ?? 0)),
            'preview_video' => (string) ($row->preview_video ?? ''),
            'category_name' => (string) ($row->category_name ?? ''),
            'subcategory_name' => (string) ($row->subcategory_name ?? ''),
            'language_name' => (string) ($row->language_name ?? ''),
            'teacher_first_name' => (string) ($row->teacher_first_name ?? ''),
            'teacher_last_name' => (string) ($row->teacher_last_name ?? ''),
            'teacher_gender' => $this->genderLabel((int) ($row->teacher_gender ?? 0)),
            'teacher_email' => (string) ($row->teacher_email ?? ''),
            'learners' => $this->decodeIntendedLearnerList($row->learners ?? ''),
            'learnings' => $this->decodeIntendedLearnerList($row->learnings ?? ''),
            'requirements' => $this->decodeIntendedLearnerList($row->requirements ?? ''),
            'search_tags' => $this->decodeTagList($row->search_tags ?? ''),
            'quiz_title' => $quizTitle,
        ];
    }

    public function updateStatus(int $requestId, int $status, string $remark = ''): void
    {
        if (! in_array($status, [self::STATUS_APPROVED, self::STATUS_DECLINED], true)) {
            throw new RuntimeException('Invalid status', 422);
        }

        if ($status === self::STATUS_DECLINED && trim($remark) === '') {
            throw new RuntimeException('Comment is required when declining a request', 422);
        }

        $request = DB::table('tbl_course_approval_requests')
            ->where('coapre_id', $requestId)
            ->where('coapre_status', self::STATUS_PENDING)
            ->first(['coapre_id', 'coapre_course_id']);

        if (! $request) {
            throw new RuntimeException('Invalid request', 404);
        }

        DB::transaction(function () use ($request, $requestId, $status, $remark) {
            DB::table('tbl_course_approval_requests')
                ->where('coapre_id', $requestId)
                ->update([
                    'coapre_status' => $status,
                    'coapre_remark' => $remark,
                    'coapre_updated' => now(),
                ]);

            DB::table('tbl_courses')
                ->where('course_id', (int) $request->coapre_course_id)
                ->update([
                    'course_status' => $status === self::STATUS_APPROVED
                        ? self::COURSE_PUBLISHED
                        : self::COURSE_DRAFTED,
                ]);
        });
    }

    public function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_DECLINED => 'Declined',
            default => 'Unknown',
        };
    }

    private function genderLabel(int $gender): string
    {
        return match ($gender) {
            1 => 'Male',
            2 => 'Female',
            3 => 'Non binary',
            4 => 'Prefer not to say',
            default => '-',
        };
    }

    private function yesNoLabel(int $value): string
    {
        return $value === 1 ? 'Yes' : 'No';
    }

    private function levelLabel(int $level): string
    {
        return match ($level) {
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced',
            default => '',
        };
    }

    private function certificateTypeLabel(int $type): string
    {
        return match ($type) {
            1 => 'Quiz evaluation',
            2 => 'Course completion',
            3 => 'Course evaluation',
            default => '',
        };
    }

    /** @return list<string> */
    private function decodeIntendedLearnerList(mixed $value): array
    {
        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (is_array($item) && isset($item['coinle_response'])) {
                $text = trim((string) $item['coinle_response']);
                if ($text !== '') {
                    $items[] = $text;
                }
                continue;
            }
            if (is_string($item) && trim($item) !== '') {
                $items[] = trim($item);
            }
        }

        return array_values($items);
    }

    /** @return list<string> */
    private function decodeTagList(mixed $value): array
    {
        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (! is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $item) {
            if (is_string($item) && trim($item) !== '') {
                $items[] = trim($item);
                continue;
            }
            if (is_array($item)) {
                $text = trim((string) ($item['tag_name'] ?? $item['name'] ?? $item['coinle_response'] ?? ''));
                if ($text !== '') {
                    $items[] = $text;
                }
            }
        }

        return array_values($items);
    }
}
