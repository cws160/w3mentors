<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\TeacherResource;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    private const BLOCK = [
        'WHY_US' => 2,
        'BROWSE_TUTOR' => 3,
        'SERVICES' => 10,
        'CLASSES' => 12,
        'ONLINE_COURSES' => 13,
        'TOP_TEACHERS' => 14,
        'TESTIMONIALS' => 15,
        'BLOGS' => 16,
        'CATEGORIES' => 17,
        'HOW_TO_START' => 18,
        'FEATURED_LANGUAGES' => 20,
        'BROWSE_COURSES' => 21,
        'CREATING_COMMUNITY' => 22,
        'COURSE_WITH_CATEGORIES' => 23,
        'JOIN_NOW' => 24,
        'SUBSCRIPTION' => 25,
    ];

    public function index(): JsonResponse
    {
        $langId = 1;

        $contentBlocks = DB::table('tbl_extra_pages as ep')
            ->leftJoin('tbl_extra_pages_lang as epl', function ($join) use ($langId) {
                $join->on('ep.epage_id', '=', 'epl.epagelang_epage_id')
                    ->where('epl.epagelang_lang_id', '=', $langId);
            })
            ->where('ep.epage_active', 1)
            ->where('ep.epage_type', 1)
            ->orderBy('ep.epage_order')
            ->get([
                'ep.epage_id as id',
                'ep.epage_identifier as identifier',
                'ep.epage_block_type as block_type',
                DB::raw(
                    'COALESCE(NULLIF(TRIM(epl.epage_content), ""), NULLIF(TRIM(ep.epage_default_content), "")) as content'
                ),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'identifier' => (string) $row->identifier,
                'block_type' => (int) $row->block_type,
                'content' => $row->content,
            ]);

        $bannerBg = DB::table('tbl_attached_files')
            ->where('file_type', 34)
            ->where('file_record_id', 0)
            ->where('file_lang_id', $langId)
            ->value('file_path');

        $slide = DB::table('tbl_slides')
            ->where('slide_active', 1)
            ->orderBy('slide_order')
            ->first(['slide_id as id', 'slide_identifier as identifier']);

        $categories = $this->getTopCategories($langId);

        $featuredLanguages = DB::table('tbl_teach_languages as tl')
            ->join('tbl_teach_languages_lang as tll', 'tl.tlang_id', '=', 'tll.tlanglang_tlang_id')
            ->where('tll.tlanglang_lang_id', $langId)
            ->where('tl.tlang_active', 1)
            ->where('tl.tlang_featured', 1)
            ->orderBy('tl.tlang_order')
            ->limit(12)
            ->get(['tl.tlang_id as id', 'tll.tlang_name as name', 'tl.tlang_slug as slug']);

        $courses = Course::published()
            ->with(['details', 'teacher'])
            ->orderByDesc('course_students')
            ->limit(8)
            ->get();

        $coursesByCategory = $this->coursesGroupedByCategory($langId);

        $teachers = User::active()
            ->verified()
            ->teachers()
            ->leftJoin('tbl_teacher_stats as ts', 'ts.testat_user_id', '=', 'tbl_users.user_id')
            ->select([
                'tbl_users.*',
                'ts.testat_ratings',
                'ts.testat_students',
                'ts.testat_lessons',
                'ts.testat_classes',
                'ts.testat_courses',
            ])
            ->orderByDesc('user_featured')
            ->orderByDesc('user_lastseen')
            ->limit(8)
            ->get();

        $testimonials = DB::table('tbl_testimonials as t')
            ->join('tbl_testimonials_lang as tl', 't.testimonial_id', '=', 'tl.testimoniallang_testimonial_id')
            ->where('tl.testimoniallang_lang_id', $langId)
            ->where('t.testimonial_active', 1)
            ->where('t.testimonial_deleted', '0')
            ->orderByDesc('t.testimonial_id')
            ->limit(6)
            ->get([
                't.testimonial_id as id',
                'tl.testimonial_text as text',
                't.testimonial_user_name as user_name',
            ]);

        $blogs = DB::table('tbl_blog_post as bp')
            ->join('tbl_blog_post_lang as bpl', 'bp.post_id', '=', 'bpl.postlang_post_id')
            ->where('bpl.postlang_lang_id', $langId)
            ->where('bp.post_published', 1)
            ->where('bp.post_deleted', 0)
            ->orderByDesc('bp.post_published_on')
            ->limit(4)
            ->get([
                'bp.post_id as id',
                'bpl.post_title as title',
                'bpl.post_short_description as excerpt',
                'bp.post_published_on as published_at',
            ]);

        $classes = DB::table('tbl_group_classes as gc')
            ->join('tbl_users as u', 'gc.grpcls_teacher_id', '=', 'u.user_id')
            ->leftJoin('tbl_teacher_stats as ts', 'ts.testat_user_id', '=', 'u.user_id')
            ->where('gc.grpcls_status', 1)
            ->where('gc.grpcls_parent', 0)
            ->where('gc.grpcls_start_datetime', '>', now())
            ->orderBy('gc.grpcls_start_datetime')
            ->limit(8)
            ->get([
                'gc.grpcls_id as id',
                'gc.grpcls_slug as slug',
                'gc.grpcls_title as title',
                'gc.grpcls_start_datetime as start_at',
                'gc.grpcls_duration as duration',
                'gc.grpcls_total_seats as total_seats',
                'gc.grpcls_entry_fee as entry_fee',
                'gc.grpcls_teacher_id as teacher_id',
                'u.user_username as teacher_username',
                DB::raw("CONCAT(u.user_first_name, ' ', u.user_last_name) as teacher_name"),
                'ts.testat_ratings as teacher_ratings',
                'ts.testat_reviewes as teacher_reviews',
            ]);

        return response()->json([
            'hero' => [
                'banner_background' => $bannerBg ? '/image/show/34/0/LARGE/'.$langId : null,
                'slide_id' => $slide->id ?? null,
                'slide_image' => isset($slide->id)
                    ? '/image/show/49/'.$slide->id.'/ORIGINAL/'.$langId
                    : null,
                'slide_identifier' => $slide->identifier ?? null,
            ],
            'content_blocks' => $contentBlocks,
            'block_types' => self::BLOCK,
            'categories' => $categories,
            'featured_languages' => $featuredLanguages,
            'courses' => CourseResource::collection($courses)->resolve(),
            'courses_by_category' => $coursesByCategory,
            'teachers' => TeacherResource::collection($teachers)->resolve(),
            'testimonials' => $testimonials,
            'blogs' => $blogs,
            'classes' => $classes,
            'is_course_available' => true,
        ]);
    }

    /**
     * Legacy Category::getTopCategories() — popular parent categories by paid orders, with fallback.
     *
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    private function getTopCategories(int $langId, int $limit = 10): array
    {
        $rows = DB::table('tbl_categories as c')
            ->join('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('c.cate_id', '=', 'cl.catelang_cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->join('tbl_courses as course', 'course.course_cate_id', '=', 'c.cate_id')
            ->join('tbl_users as teacher', 'course.course_user_id', '=', 'teacher.user_id')
            ->join('tbl_order_courses as ordcrs', 'ordcrs.ordcrs_course_id', '=', 'course.course_id')
            ->join('tbl_orders as orders', 'orders.order_id', '=', 'ordcrs.ordcrs_order_id')
            ->whereNull('course.course_deleted')
            ->where(function ($query) {
                $query->whereNull('c.cate_deleted')->orWhere('c.cate_deleted', 0);
            })
            ->where('c.cate_status', 1)
            ->where('orders.order_payment_status', 1)
            ->where('c.cate_parent', 0)
            ->groupBy('c.cate_id', 'cl.cate_name', 'c.cate_identifier')
            ->orderByDesc(DB::raw('COUNT(ordcrs.ordcrs_id)'))
            ->orderBy('cl.cate_name')
            ->limit($limit)
            ->get([
                'c.cate_id as id',
                DB::raw('IFNULL(cl.cate_name, c.cate_identifier) as name'),
                'c.cate_identifier as slug',
            ]);

        if ($rows->isNotEmpty()) {
            return $rows->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'slug' => (string) $row->slug,
            ])->all();
        }

        return DB::table('tbl_categories as c')
            ->join('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('c.cate_id', '=', 'cl.catelang_cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->where('c.cate_status', 1)
            ->where(function ($query) {
                $query->whereNull('c.cate_deleted')->orWhere('c.cate_deleted', 0);
            })
            ->where('c.cate_parent', 0)
            ->orderBy('c.cate_order')
            ->limit($limit)
            ->get(['c.cate_id as id', 'cl.cate_name as name', 'c.cate_identifier as slug'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) ($row->name ?: $row->slug),
                'slug' => (string) $row->slug,
            ])
            ->all();
    }

    private function coursesGroupedByCategory(int $langId): array
    {
        $parentCategories = DB::table('tbl_categories as c')
            ->join('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('c.cate_id', '=', 'cl.catelang_cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->where('c.cate_status', 1)
            ->whereNull('c.cate_deleted')
            ->where('c.cate_parent', 0)
            ->orderBy('c.cate_order')
            ->limit(6)
            ->get(['c.cate_id as id', 'cl.cate_name as name']);

        $categories = [];
        $courses = [];

        foreach ($parentCategories as $cat) {
            $categories[$cat->id] = $cat->name;
            $catCourses = Course::published()
                ->with(['details', 'teacher'])
                ->where('course_cate_id', $cat->id)
                ->orderByDesc('course_students')
                ->limit(8)
                ->get();

            if ($catCourses->isNotEmpty()) {
                $courses[$cat->id] = CourseResource::collection($catCourses)->resolve();
            }
        }

        return [
            'categories' => $categories,
            'courses' => $courses,
        ];
    }
}
