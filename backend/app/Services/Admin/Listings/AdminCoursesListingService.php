<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCoursesListingService
{
    use AdminListingSupport;

    private const STATUS_PUBLISHED = 3;
    private const REQUEST_APPROVED = 1;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request, int $langId = 1): array
    {
        $langId = $this->langId($request) ?: $langId;

        $query = DB::table('tbl_courses as course')
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
            ->whereNull('course.course_deleted')
            ->where('course.course_status', '=', self::STATUS_PUBLISHED)
            ->where('coapre.coapre_status', '=', self::REQUEST_APPROVED)
            ->select([
                'course.course_id as id',
                'crsdetail.course_title as title',
                'course.course_active as active',
                'coapre.coapre_updated as published_at',
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as teacher_name'),
                DB::raw('course.course_user_id as course_teacher_id'),
                DB::raw('IFNULL(catelang.cate_name, "") as category_name'),
                DB::raw('IFNULL(subcatelang.cate_name, "") as subcategory_name'),
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('crsdetail.course_title', 'like', "%{$keyword}%")
                    ->orWhereRaw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) LIKE ?', ["%{$keyword}%"]);
            });
        }

        $clangId = $request->integer('course_clang_id', 0);
        if ($clangId > 0) {
            $query->where('course.course_clang_id', '=', $clangId);
        }

        $cateId = $request->integer('course_cateid', 0);
        if ($cateId > 0) {
            $query->where('course.course_cate_id', '=', $cateId);
        }

        $subCateId = $request->integer('course_subcateid', 0);
        if ($subCateId > 0) {
            $query->where('course.course_subcate_id', '=', $subCateId);
        }

        $dateFrom = trim((string) $request->query('course_addedon_from', ''));
        if ($dateFrom !== '') {
            $query->where('coapre.coapre_updated', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) $request->query('course_addedon_till', ''));
        if ($dateTo !== '') {
            $query->where('coapre.coapre_updated', '<=', $dateTo.' 23:59:59');
        }

        $total = (clone $query)->count();
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $rows = $query
            ->orderByDesc('course.course_active')
            ->orderByDesc('course.course_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => (string) ($row->title ?? ''),
                'teacher_name' => trim((string) ($row->teacher_name ?? '')),
                'course_teacher_id' => (int) ($row->course_teacher_id ?? 0),
                'category_name' => (string) ($row->category_name ?? ''),
                'subcategory_name' => (string) ($row->subcategory_name ?? ''),
                'published_at' => (string) ($row->published_at ?? ''),
                'active' => (int) ($row->active ?? 0),
            ])
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

    /** @return array<int, array<string, mixed>> */
    public function exportRows(Request $request): array
    {
        $request->merge(['page' => 1, 'per_page' => 5000]);

        return $this->search($request)['data'];
    }
}
