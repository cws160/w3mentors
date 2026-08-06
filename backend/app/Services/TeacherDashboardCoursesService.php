<?php

namespace App\Services;

use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TeacherDashboardCoursesService
{
    public function filters(int $langId): array
    {
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

        return [
            'categories' => $categories,
            'sub_categories' => $subCategories,
            'statuses' => [
                ['id' => Course::STATUS_DRAFT, 'label' => 'Drafted'],
                ['id' => Course::STATUS_SUBMITTED, 'label' => 'Submitted for approval'],
                ['id' => Course::STATUS_PUBLISHED, 'label' => 'Published'],
            ],
            'types' => [
                ['id' => Course::TYPE_FREE, 'label' => 'Free'],
                ['id' => Course::TYPE_PAID, 'label' => 'Paid'],
            ],
        ];
    }

    public function search(int $teacherId, int $langId, array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = DB::table('tbl_courses as course')
            ->join('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->leftJoin('tbl_categories_lang as catelang', function ($join) use ($langId) {
                $join->on('catelang.catelang_cate_id', '=', 'course.course_cate_id')
                    ->where('catelang.catelang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_categories_lang as subcatelang', function ($join) use ($langId) {
                $join->on('subcatelang.catelang_cate_id', '=', 'course.course_subcate_id')
                    ->where('subcatelang.catelang_lang_id', '=', $langId);
            })
            ->where('course.course_user_id', $teacherId)
            ->whereNull('course.course_deleted')
            ->select([
                'course.course_id as id',
                'course.course_slug as slug',
                'course.course_status as status',
                'course.course_active as active',
                'course.course_price as price',
                'course.course_type as type',
                'course.course_lectures as lectures',
                'course.course_students as students',
                'course.course_ratings as ratings',
                'course.course_reviews as reviews',
                'course.course_sections as sections',
                'course.course_updated as updated_at',
                'crsdetail.course_title as title',
                DB::raw('IFNULL(catelang.cate_name, "") as category_name'),
                DB::raw('IFNULL(subcatelang.cate_name, "") as subcategory_name'),
            ])
            ->orderByDesc('course.course_id');

        if (! empty($filters['keyword'])) {
            $keyword = '%'.trim($filters['keyword']).'%';
            $query->where('crsdetail.course_title', 'like', $keyword);
        }
        if (! empty($filters['course_cateid'])) {
            $query->where('course.course_cate_id', (int) $filters['course_cateid']);
        }
        if (! empty($filters['course_subcateid'])) {
            $query->where('course.course_subcate_id', (int) $filters['course_subcateid']);
        }
        if (isset($filters['course_status']) && $filters['course_status'] !== '') {
            $query->where('course.course_status', (int) $filters['course_status']);
        }
        if (isset($filters['course_type']) && $filters['course_type'] !== '') {
            $query->where('course.course_type', (int) $filters['course_type']);
        }

        return $query->paginate($perPage);
    }

    public function formatCourseRow(object $row): array
    {
        $status = (int) $row->status;
        $statusClass = 'color-warning';
        if ($status === Course::STATUS_PUBLISHED) {
            $statusClass = 'color-success';
        } elseif ($status === Course::STATUS_SUBMITTED) {
            $statusClass = 'color-info';
        }

        return [
            'id' => (int) $row->id,
            'slug' => $row->slug,
            'title' => $row->title,
            'category_name' => $row->category_name,
            'subcategory_name' => $row->subcategory_name,
            'price' => (float) $row->price,
            'type' => (int) $row->type,
            'lectures' => (int) $row->lectures,
            'students' => (int) $row->students,
            'ratings' => (float) $row->ratings,
            'reviews' => (int) $row->reviews,
            'sections' => (int) $row->sections,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'status_class' => $statusClass,
            'active' => (bool) $row->active,
            'can_preview' => (int) $row->sections > 0 && (int) $row->lectures > 0,
            'updated_at' => $row->updated_at,
        ];
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            Course::STATUS_PUBLISHED => 'Published',
            Course::STATUS_SUBMITTED => 'Submitted for approval',
            default => 'Drafted',
        };
    }
}
