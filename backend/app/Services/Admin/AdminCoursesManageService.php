<?php

namespace App\Services\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCoursesManageService
{
    private const STATUS_PUBLISHED = 3;

    /** @return array{ok: bool, message?: string} */
    public function updateActiveStatus(int $courseId, int $currentStatus): array
    {
        $course = DB::table('tbl_courses')
            ->where('course_id', $courseId)
            ->whereNull('course_deleted')
            ->first(['course_id', 'course_status']);

        if (! $course) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $nextStatus = (int) $currentStatus === 1 ? 0 : 1;

        DB::table('tbl_courses')
            ->where('course_id', $courseId)
            ->update([
                'course_active' => $nextStatus,
                'course_updated' => now(),
            ]);

        return ['ok' => true];
    }

    /** @return array<string, mixed>|null */
    public function show(int $courseId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_courses as course')
            ->join('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'course.course_user_id')
            ->join('tbl_course_approval_requests as coapre', 'coapre.coapre_course_id', '=', 'course.course_id')
            ->leftJoin('tbl_categories_lang as catelang', function ($join) use ($langId) {
                $join->on('catelang.catelang_cate_id', '=', 'course.course_cate_id')
                    ->where('catelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories_lang as subcatelang', function ($join) use ($langId) {
                $join->on('subcatelang.catelang_cate_id', '=', 'course.course_subcate_id')
                    ->where('subcatelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_course_languages as clang', 'clang.clang_id', '=', 'course.course_clang_id')
            ->leftJoin('tbl_course_languages_lang as clanglang', function ($join) use ($langId) {
                $join->on('clanglang.clanglang_clang_id', '=', 'clang.clang_id')
                    ->where('clanglang.clanglang_lang_id', '=', $langId);
            })
            ->whereNull('course.course_deleted')
            ->where('course.course_id', $courseId)
            ->where('course.course_status', self::STATUS_PUBLISHED)
            ->select([
                'course.course_id as id',
                'crsdetail.course_title as title',
                'crsdetail.course_subtitle as subtitle',
                'crsdetail.course_details as details',
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as teacher_name'),
                'course.course_duration as duration',
                DB::raw('IFNULL(catelang.cate_name, "") as category_name'),
                DB::raw('IFNULL(subcatelang.cate_name, "") as subcategory_name'),
                'course.course_level as level',
                DB::raw('IFNULL(clanglang.clang_name, clang.clang_identifier) as course_language'),
                'course.course_status as status',
                'course.course_price as price',
                'coapre.coapre_updated as published_at',
                'course.course_sections as sections',
                'course.course_lectures as lectures',
                'course.course_reviews as reviews',
                'course.course_students as students',
                'course.course_certificate as certificate',
                'course.course_certificate_type as certificate_type',
                'course.course_quilin_id as quiz_id',
                'course.course_ratings as ratings',
                'course.course_preview_video as preview_video',
            ])
            ->first();

        if (! $row) {
            return null;
        }

        $quizTitle = '';
        if ((int) $row->quiz_id > 0) {
            $quizTitle = (string) (DB::table('tbl_quiz_linked')
                ->where('quilin_id', (int) $row->quiz_id)
                ->value('quilin_title') ?? '');
        }

        return [
            'id' => (int) $row->id,
            'title' => (string) ($row->title ?? ''),
            'subtitle' => (string) ($row->subtitle ?? ''),
            'teacher_name' => trim((string) ($row->teacher_name ?? '')),
            'duration' => (int) ($row->duration ?? 0),
            'category_name' => (string) ($row->category_name ?? ''),
            'subcategory_name' => (string) ($row->subcategory_name ?? ''),
            'level' => (int) ($row->level ?? 0),
            'course_language' => (string) ($row->course_language ?? ''),
            'status' => (int) ($row->status ?? 0),
            'price' => (float) ($row->price ?? 0),
            'published_at' => (string) ($row->published_at ?? ''),
            'sections' => (int) ($row->sections ?? 0),
            'lectures' => (int) ($row->lectures ?? 0),
            'reviews' => (int) ($row->reviews ?? 0),
            'students' => (int) ($row->students ?? 0),
            'certificate' => (int) ($row->certificate ?? 0),
            'certificate_type' => (int) ($row->certificate_type ?? 0),
            'quiz_id' => (int) ($row->quiz_id ?? 0),
            'quiz_title' => $quizTitle,
            'ratings' => (float) ($row->ratings ?? 0),
            'preview_video' => (string) ($row->preview_video ?? ''),
            'details' => (string) ($row->details ?? ''),
        ];
    }

    /** @return array<int, array{id: int, name: string}> */
    public function rootCategories(int $langId = 1): array
    {
        return DB::table('tbl_categories as c')
            ->leftJoin('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('cl.catelang_cate_id', '=', 'c.cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->whereNull('c.cate_deleted')
            ->where('c.cate_parent', 0)
            ->where('c.cate_type', 1)
            ->orderBy('c.cate_order')
            ->get([
                'c.cate_id as id',
                DB::raw('IFNULL(cl.cate_name, c.cate_identifier) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    /** @return array<int, array{id: int, name: string}> */
    public function subcategories(int $parentId, int $langId = 1): array
    {
        if ($parentId < 1) {
            return [];
        }

        return DB::table('tbl_categories as c')
            ->leftJoin('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('cl.catelang_cate_id', '=', 'c.cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->whereNull('c.cate_deleted')
            ->where('c.cate_parent', $parentId)
            ->where('c.cate_type', 1)
            ->orderBy('c.cate_order')
            ->get([
                'c.cate_id as id',
                DB::raw('IFNULL(cl.cate_name, c.cate_identifier) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }

    /** @return array<int, array{id: int, name: string}> */
    public function courseLanguageAutocomplete(string $keyword, int $langId = 1): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        return DB::table('tbl_course_languages as clang')
            ->leftJoin('tbl_course_languages_lang as clanglang', function ($join) use ($langId) {
                $join->on('clanglang.clanglang_clang_id', '=', 'clang.clang_id')
                    ->where('clanglang.clanglang_lang_id', '=', $langId);
            })
            ->where('clang.clang_active', 1)
            ->whereNull('clang.clang_deleted')
            ->where(function ($q) use ($keyword) {
                $q->where('clanglang.clang_name', 'like', "%{$keyword}%")
                    ->orWhere('clang.clang_identifier', 'like', "%{$keyword}%");
            })
            ->orderBy('clanglang.clang_name')
            ->limit(20)
            ->get([
                'clang.clang_id as id',
                DB::raw('IFNULL(clanglang.clang_name, clang.clang_identifier) as name'),
            ])
            ->map(fn ($row) => ['id' => (int) $row->id, 'name' => (string) $row->name])
            ->all();
    }
}
