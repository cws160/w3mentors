<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CourseResource;
use App\Http\Resources\TeacherListingResource;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    public function __construct(
        private \App\Services\CourseService $courses,
        private \App\Services\CoursePublicService $public,
        private \App\Services\CourseEnrollmentService $enrollment,
    ) {
    }

    public function filters(): JsonResponse
    {
        $langId = 1;

        $categories = DB::table('tbl_categories as c')
            ->join('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('c.cate_id', '=', 'cl.catelang_cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->where('c.cate_status', 1)
            ->whereNull('c.cate_deleted')
            ->where('c.cate_parent', 0)
            ->orderBy('c.cate_order')
            ->get(['c.cate_id as id', 'cl.cate_name as name']);

        $subCategories = DB::table('tbl_categories as c')
            ->join('tbl_categories_lang as cl', function ($join) use ($langId) {
                $join->on('c.cate_id', '=', 'cl.catelang_cate_id')
                    ->where('cl.catelang_lang_id', '=', $langId);
            })
            ->where('c.cate_status', 1)
            ->whereNull('c.cate_deleted')
            ->where('c.cate_parent', '>', 0)
            ->orderBy('c.cate_order')
            ->get(['c.cate_id as id', 'c.cate_parent as parent_id', 'cl.cate_name as name']);

        $subsByParent = $subCategories->groupBy('parent_id');

        $categoryTree = $categories->map(fn ($cat) => [
            'id' => $cat->id,
            'name' => $cat->name,
            'sub_categories' => ($subsByParent[$cat->id] ?? collect())->values(),
        ]);

        $priceRange = Course::published()
            ->selectRaw('MIN(course_price) as min_price, MAX(course_price) as max_price')
            ->first();

        $languages = DB::table('tbl_course_languages as cl')
            ->join('tbl_course_languages_lang as cll', 'cl.clang_id', '=', 'cll.clanglang_clang_id')
            ->where('cll.clanglang_lang_id', $langId)
            ->where('cl.clang_active', 1)
            ->orderBy('cl.clang_order')
            ->get(['cl.clang_id as id', 'cll.clang_name as name']);

        return response()->json([
            'categories' => $categoryTree,
            'price_range' => [
                'min' => (float) floor($priceRange->min_price ?? 0),
                'max' => (float) ceil($priceRange->max_price ?? 0),
            ],
            'levels' => [
                ['id' => 1, 'name' => 'Beginner'],
                ['id' => 2, 'name' => 'Intermediate'],
                ['id' => 3, 'name' => 'Advanced'],
            ],
            'ratings' => [
                ['id' => 4, 'name' => '4 & up'],
                ['id' => 3, 'name' => '3 & up'],
                ['id' => 2, 'name' => '2 & up'],
                ['id' => 1, 'name' => '1 & up'],
            ],
            'languages' => $languages,
            'sort_options' => [
                'newest' => 'Newest',
                'popular' => 'Most popular',
                'rating' => 'Top rated',
                'price_asc' => 'Price: low to high',
                'price_desc' => 'Price: high to low',
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Course::published()->with(['details', 'teacher']);

        if ($search = $request->string('search')->trim()) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('details', function ($d) use ($search) {
                    $d->where('course_title', 'like', "%{$search}%")
                        ->orWhere('course_subtitle', 'like', "%{$search}%");
                })->orWhereHas('teacher', function ($t) use ($search) {
                    $t->where('user_first_name', 'like', "%{$search}%")
                        ->orWhere('user_last_name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('category')) {
            $cats = (array) $request->input('category');
            $query->where(function ($q) use ($cats) {
                $q->whereIn('course_cate_id', $cats)
                    ->orWhereIn('course_subcate_id', $cats);
            });
        }

        if ($request->filled('level')) {
            $query->whereIn('course_level', (array) $request->input('level'));
        }

        if ($request->filled('language')) {
            $query->whereIn('course_clang_id', (array) $request->input('language'));
        }

        if ($request->filled('ratings')) {
            $query->where('course_ratings', '>=', $request->integer('ratings'));
        }

        if ($request->filled('price_from')) {
            $query->where('course_price', '>=', $request->float('price_from'));
        }

        if ($request->filled('price_till')) {
            $query->where('course_price', '<=', $request->float('price_till'));
        }

        if ($request->filled('teacher_id')) {
            $query->where('course_user_id', $request->integer('teacher_id'));
        }

        if ($request->filled('type')) {
            $query->where('course_type', $request->integer('type'));
        }

        $sort = $request->string('sort', 'newest');
        match ($sort) {
            'price_asc' => $query->orderBy('course_price'),
            'price_desc' => $query->orderByDesc('course_price'),
            'rating' => $query->orderByDesc('course_ratings'),
            'popular' => $query->orderByDesc('course_students'),
            default => $query->orderByDesc('course_created'),
        };

        $courses = $query->paginate($request->integer('per_page', 12));

        return response()->json([
            'data' => CourseResource::collection($courses),
            'meta' => [
                'current_page' => $courses->currentPage(),
                'last_page' => $courses->lastPage(),
                'per_page' => $courses->perPage(),
                'total' => $courses->total(),
            ],
        ]);
    }

    public function show(Request $request, Course $course): JsonResponse
    {
        if (!$this->isPublished($course)) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $course->load(['details', 'teacher']);
        $enrollment = $this->courses->findEnrollment($request->user(), $course->course_id);
        $langId = 1;

        $category = DB::table('tbl_categories_lang')
            ->where('catelang_cate_id', $course->course_cate_id)
            ->where('catelang_lang_id', $langId)
            ->value('cate_name');

        $subCategory = $course->course_subcate_id
            ? DB::table('tbl_categories_lang')
                ->where('catelang_cate_id', $course->course_subcate_id)
                ->where('catelang_lang_id', $langId)
                ->value('cate_name')
            : null;

        $language = DB::table('tbl_course_languages_lang')
            ->where('clanglang_clang_id', $course->course_clang_id)
            ->where('clanglang_lang_id', $langId)
            ->value('clang_name');

        $resource = (new CourseResource($course))->resolve();
        $resource['category_id'] = (int) $course->course_cate_id;
        $resource['subcategory_id'] = (int) ($course->course_subcate_id ?? 0);
        $resource['category_name'] = $category;
        $resource['subcategory_name'] = $subCategory;
        $resource['language_name'] = $language;
        $resource['level_name'] = match ((int) $course->course_level) {
            1 => 'Beginner',
            2 => 'Intermediate',
            3 => 'Advanced',
            default => '',
        };
        $resource['tags'] = $this->public->getTags($course);
        $resource['resources_count'] = $this->public->getResourcesCount($course->course_id);
        $resource['has_quiz'] = (int) $course->course_quilin_id > 0;
        $resource['teacher'] = $this->public->getTeacherDetail($course) ?? $resource['teacher'] ?? null;
        $resource['review_stats'] = $this->public->getReviewStats($course->course_id);
        $resource['more_courses'] = $this->public->getMoreCourses($course);

        return response()->json([
            'data' => $resource,
            'enrollment' => $enrollment ? [
                'order_course_id' => $enrollment->ordcrs_id,
                'status' => (int) $enrollment->ordcrs_status,
                'progress_percent' => (float) ($enrollment->progress?->crspro_progress ?? 0),
                'is_enrolled' => true,
            ] : [
                'is_enrolled' => false,
            ],
        ]);
    }

    public function showBySlug(Request $request, string $slug): JsonResponse
    {
        $course = Course::published()->where('course_slug', $slug)->first();

        if (!$course) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return $this->show($request, $course);
    }

    public function curriculum(Request $request, Course $course): JsonResponse
    {
        if (!$this->isPublished($course)) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $sections = $this->courses->getCurriculum($course, $request->user());

        return response()->json([
            'data' => $sections,
            'meta' => [
                'sections_count' => $sections->count(),
                'lectures_count' => (int) $course->course_lectures,
                'duration' => (int) $course->course_duration,
                'is_enrolled' => $this->courses->isEnrolled($request->user(), $course->course_id),
            ],
        ]);
    }

    public function intendedLearners(Course $course): JsonResponse
    {
        if (!$this->isPublished($course)) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        return response()->json(['data' => $this->courses->getIntendedLearners($course)]);
    }

    public function reviews(Request $request, Course $course): JsonResponse
    {
        if (!$this->isPublished($course)) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $result = $this->public->getReviews(
            $course->course_id,
            $request->string('sort', 'DESC'),
            $request->integer('page', 1),
            $request->integer('per_page', 10),
        );

        return response()->json($result);
    }

    public function enroll(Request $request, Course $course): JsonResponse
    {
        if (!$this->isPublished($course)) {
            return response()->json(['message' => 'Course not found'], 404);
        }

        $result = $this->enrollment->enroll($request->user(), $course);

        return response()->json($result);
    }

    private function isPublished(Course $course): bool
    {
        return $course->course_active
            && !$course->course_deleted
            && (int) $course->course_status === Course::STATUS_PUBLISHED;
    }
}
