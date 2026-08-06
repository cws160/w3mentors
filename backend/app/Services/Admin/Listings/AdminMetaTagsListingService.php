<?php

namespace App\Services\Admin\Listings;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminMetaTagsListingService
{
    use AdminListingSupport;

    public const META_GROUP_DEFAULT = -1;

    public const META_GROUP_OTHER = 0;

    public const META_GROUP_TEACHER = 1;

    public const META_GROUP_GRP_CLASS = 2;

    public const META_GROUP_CMS_PAGE = 3;

    public const META_GROUP_BLOG_CATEGORY = 4;

    public const META_GROUP_BLOG_POST = 5;

    public const META_GROUP_COURSE = 6;

    public const META_GROUP_TEACH_LANGUAGE = 10;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>, meta_type: int} */
    public function search(Request $request): array
    {
        $metaType = $request->integer('metaType', $request->integer('meta_type', self::META_GROUP_DEFAULT));
        $langId = $this->langId($request);

        if ($metaType === self::META_GROUP_COURSE && ! $this->coursesEnabled()) {
            return $this->emptyResult($request, $metaType);
        }

        if ($metaType === self::META_GROUP_GRP_CLASS && ! $this->groupClassesEnabled()) {
            return $this->emptyResult($request, $metaType);
        }

        $query = $this->buildQuery($metaType, $langId);
        $this->applyFilters($request, $query, $metaType, $langId);

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->count();
        $rows = $query
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => $this->formatRow((array) $row, $metaType))
            ->all();

        $result = $this->paginateResult($request, $rows, $total);
        $result['meta_type'] = $metaType;

        return $result;
    }

    private function buildQuery(int $metaType, int $langId): Builder
    {
        return match ($metaType) {
            self::META_GROUP_TEACHER => $this->teacherQuery($langId),
            self::META_GROUP_GRP_CLASS => $this->groupClassQuery($langId),
            self::META_GROUP_CMS_PAGE => $this->cmsPageQuery($langId),
            self::META_GROUP_BLOG_CATEGORY => $this->blogCategoryQuery($langId),
            self::META_GROUP_BLOG_POST => $this->blogPostQuery($langId),
            self::META_GROUP_COURSE => $this->courseQuery($langId),
            self::META_GROUP_TEACH_LANGUAGE => $this->teachLanguageQuery($langId),
            default => $this->metaTagQuery($metaType, $langId),
        };
    }

    private function metaTitleSelect(int $langId): \Illuminate\Database\Query\Expression
    {
        return DB::raw(
            'COALESCE(NULLIF(mt_l.meta_title, ""), (
                SELECT ml.meta_title
                FROM tbl_meta_tags_lang ml
                WHERE ml.metalang_meta_id = mt.meta_id AND ml.meta_title != ""
                ORDER BY CASE WHEN ml.metalang_lang_id = '.$langId.' THEN 0 ELSE 1 END, ml.metalang_lang_id
                LIMIT 1
            )) as meta_title'
        );
    }

    private function metaTagQuery(int $metaType, int $langId): Builder
    {
        return DB::table('tbl_meta_tags as mt')
            ->leftJoin('tbl_meta_tags_lang as mt_l', function ($join) use ($langId) {
                $join->on('mt_l.metalang_meta_id', '=', 'mt.meta_id')
                    ->where('mt_l.metalang_lang_id', '=', $langId);
            })
            ->where('mt.meta_type', '=', $metaType)
            ->select([
                'mt.meta_id',
                'mt.meta_record_id',
                'mt.meta_identifier',
                'mt.meta_controller',
                'mt.meta_action',
                $this->metaTitleSelect($langId),
            ])
            ->orderByDesc('mt.meta_id');
    }

    private function teacherQuery(int $langId): Builder
    {
        return DB::table('tbl_users as u')
            ->leftJoin('tbl_meta_tags as mt', function ($join) {
                $join->on('mt.meta_record_id', '=', 'u.user_username')
                    ->where('mt.meta_type', '=', self::META_GROUP_TEACHER);
            })
            ->leftJoin('tbl_meta_tags_lang as mt_l', function ($join) use ($langId) {
                $join->on('mt_l.metalang_meta_id', '=', 'mt.meta_id')
                    ->where('mt_l.metalang_lang_id', '=', $langId);
            })
            ->where('u.user_is_teacher', '=', 1)
            ->whereNull('u.user_deleted')
            ->whereNotNull('u.user_username')
            ->select([
                'mt.meta_id',
                'u.user_username as meta_record_id',
                'mt.meta_identifier',
                'mt.meta_controller',
                'mt.meta_action',
                $this->metaTitleSelect($langId),
                DB::raw('TRIM(CONCAT(COALESCE(u.user_first_name, ""), " ", COALESCE(u.user_last_name, ""))) as teacher_name'),
                'u.user_id',
            ])
            ->orderByDesc('u.user_id');
    }

    private function groupClassQuery(int $langId): Builder
    {
        return DB::table('tbl_group_classes as gcls')
            ->leftJoin('tbl_meta_tags as mt', function ($join) {
                $join->on('mt.meta_record_id', '=', 'gcls.grpcls_slug')
                    ->where('mt.meta_type', '=', self::META_GROUP_GRP_CLASS);
            })
            ->leftJoin('tbl_meta_tags_lang as mt_l', function ($join) use ($langId) {
                $join->on('mt_l.metalang_meta_id', '=', 'mt.meta_id')
                    ->where('mt_l.metalang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_group_classes_lang as gcl', function ($join) use ($langId) {
                $join->on('gcl.gclang_grpcls_id', '=', 'gcls.grpcls_id')
                    ->where('gcl.gclang_lang_id', '=', $langId);
            })
            ->join('tbl_users as u', 'u.user_id', '=', 'gcls.grpcls_teacher_id')
            ->where('gcls.grpcls_start_datetime', '>', now())
            ->where('gcls.grpcls_status', '=', 1)
            ->where('gcls.grpcls_parent', '=', 0)
            ->whereNull('u.user_deleted')
            ->where('u.user_active', '=', 1)
            ->where('u.user_is_teacher', '=', 1)
            ->select([
                'mt.meta_id',
                'gcls.grpcls_slug as meta_record_id',
                'mt.meta_identifier',
                'mt.meta_controller',
                'mt.meta_action',
                $this->metaTitleSelect($langId),
                DB::raw('COALESCE(NULLIF(gcl.grpcls_title, ""), gcls.grpcls_title) as grpcls_title'),
                'gcls.grpcls_id',
                DB::raw('TRIM(CONCAT(COALESCE(u.user_first_name, ""), " ", COALESCE(u.user_last_name, ""))) as teacher_name'),
            ])
            ->orderByDesc('gcls.grpcls_id');
    }

    private function cmsPageQuery(int $langId): Builder
    {
        return DB::table('tbl_content_pages as cp')
            ->leftJoin('tbl_meta_tags as mt', function ($join) {
                $join->on(DB::raw('CAST(cp.cpage_id AS CHAR)'), '=', 'mt.meta_record_id')
                    ->where('mt.meta_type', '=', self::META_GROUP_CMS_PAGE);
            })
            ->leftJoin('tbl_meta_tags_lang as mt_l', function ($join) use ($langId) {
                $join->on('mt_l.metalang_meta_id', '=', 'mt.meta_id')
                    ->where('mt_l.metalang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_content_pages_lang as cp_l', function ($join) use ($langId) {
                $join->on('cp_l.cpagelang_cpage_id', '=', 'cp.cpage_id')
                    ->where('cp_l.cpagelang_lang_id', '=', $langId);
            })
            ->where('cp.cpage_deleted', '=', 0)
            ->select([
                'mt.meta_id',
                'cp.cpage_id as meta_record_id',
                'mt.meta_identifier',
                'mt.meta_controller',
                'mt.meta_action',
                $this->metaTitleSelect($langId),
                'cp.cpage_id',
                DB::raw('COALESCE(cp_l.cpage_title, cp.cpage_identifier) as cpage_title'),
            ])
            ->orderByDesc('cp.cpage_id');
    }

    private function blogCategoryQuery(int $langId): Builder
    {
        return DB::table('tbl_blog_post_categories as bpc')
            ->leftJoin('tbl_meta_tags as mt', function ($join) {
                $join->on(DB::raw('CAST(bpc.bpcategory_id AS CHAR)'), '=', 'mt.meta_record_id')
                    ->where('mt.meta_type', '=', self::META_GROUP_BLOG_CATEGORY);
            })
            ->leftJoin('tbl_meta_tags_lang as mt_l', function ($join) use ($langId) {
                $join->on('mt_l.metalang_meta_id', '=', 'mt.meta_id')
                    ->where('mt_l.metalang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_blog_post_categories_lang as bpcl', function ($join) use ($langId) {
                $join->on('bpcl.bpcategorylang_bpcategory_id', '=', 'bpc.bpcategory_id')
                    ->where('bpcl.bpcategorylang_lang_id', '=', $langId);
            })
            ->where('bpc.bpcategory_deleted', '=', 0)
            ->select([
                'mt.meta_id',
                'bpc.bpcategory_id as meta_record_id',
                'mt.meta_identifier',
                'mt.meta_controller',
                'mt.meta_action',
                $this->metaTitleSelect($langId),
                'bpc.bpcategory_id',
                DB::raw('COALESCE(bpcl.bpcategory_name, bpc.bpcategory_identifier) as bpcategory_identifier'),
            ])
            ->orderByDesc('bpc.bpcategory_id');
    }

    private function blogPostQuery(int $langId): Builder
    {
        return DB::table('tbl_blog_post as bp')
            ->leftJoin('tbl_meta_tags as mt', function ($join) {
                $join->on(DB::raw('CAST(bp.post_id AS CHAR)'), '=', 'mt.meta_record_id')
                    ->where('mt.meta_type', '=', self::META_GROUP_BLOG_POST);
            })
            ->leftJoin('tbl_meta_tags_lang as mt_l', function ($join) use ($langId) {
                $join->on('mt_l.metalang_meta_id', '=', 'mt.meta_id')
                    ->where('mt_l.metalang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_blog_post_lang as bpl', function ($join) use ($langId) {
                $join->on('bpl.postlang_post_id', '=', 'bp.post_id')
                    ->where('bpl.postlang_lang_id', '=', $langId);
            })
            ->where('bp.post_deleted', '=', 0)
            ->select([
                'mt.meta_id',
                'bp.post_id as meta_record_id',
                'mt.meta_identifier',
                'mt.meta_controller',
                'mt.meta_action',
                $this->metaTitleSelect($langId),
                'bp.post_id',
                DB::raw('COALESCE(bpl.post_title, bp.post_identifier) as post_identifier'),
            ])
            ->orderByDesc('bp.post_id');
    }

    private function courseQuery(int $langId): Builder
    {
        return DB::table('tbl_courses as crs')
            ->leftJoin('tbl_meta_tags as mt', function ($join) {
                $join->on('mt.meta_record_id', '=', 'crs.course_slug')
                    ->where('mt.meta_type', '=', self::META_GROUP_COURSE);
            })
            ->leftJoin('tbl_meta_tags_lang as mt_l', function ($join) use ($langId) {
                $join->on('mt_l.metalang_meta_id', '=', 'mt.meta_id')
                    ->where('mt_l.metalang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'crs.course_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'crs.course_user_id')
            ->join('tbl_categories as cate', 'cate.cate_id', '=', 'crs.course_cate_id')
            ->join('tbl_course_languages as clang', 'clang.clang_id', '=', 'crs.course_clang_id')
            ->whereNull('crs.course_deleted')
            ->where('crs.course_active', '=', 1)
            ->where('crs.course_status', '=', 3)
            ->whereNull('cate.cate_deleted')
            ->where('cate.cate_status', '=', 1)
            ->where('teacher.user_username', '!=', '')
            ->whereNull('teacher.user_deleted')
            ->whereNotNull('teacher.user_verified')
            ->where('teacher.user_active', '=', 1)
            ->where('teacher.user_is_teacher', '=', 1)
            ->select([
                'mt.meta_id',
                'crs.course_slug as meta_record_id',
                'mt.meta_identifier',
                'mt.meta_controller',
                'mt.meta_action',
                $this->metaTitleSelect($langId),
                'crs.course_id',
                'crs.course_slug',
                'crsdetail.course_title',
            ])
            ->orderByDesc('crs.course_id');
    }

    private function teachLanguageQuery(int $langId): Builder
    {
        return DB::table('tbl_teach_languages as tlang')
            ->leftJoin('tbl_meta_tags as mt', function ($join) {
                $join->on('mt.meta_record_id', '=', 'tlang.tlang_slug')
                    ->where('mt.meta_type', '=', self::META_GROUP_TEACH_LANGUAGE);
            })
            ->leftJoin('tbl_meta_tags_lang as mt_l', function ($join) use ($langId) {
                $join->on('mt_l.metalang_meta_id', '=', 'mt.meta_id')
                    ->where('mt_l.metalang_lang_id', '=', $langId);
            })
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlanglang.tlanglang_tlang_id', '=', 'tlang.tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->select([
                'mt.meta_id',
                'tlang.tlang_slug as meta_record_id',
                'mt.meta_identifier',
                'mt.meta_controller',
                'mt.meta_action',
                $this->metaTitleSelect($langId),
                'tlang.tlang_slug',
                DB::raw('COALESCE(tlanglang.tlang_name, tlang.tlang_identifier) as tlang_name'),
            ])
            ->orderBy('tlang.tlang_slug');
    }

    private function applyFilters(Request $request, Builder $query, int $metaType, int $langId): void
    {
        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function (Builder $q) use ($keyword, $metaType) {
                $q->where('mt.meta_identifier', 'like', '%'.$keyword.'%')
                    ->orWhere('mt_l.meta_title', 'like', '%'.$keyword.'%');

                match ($metaType) {
                    self::META_GROUP_TEACHER => $q->orWhereRaw(
                        'TRIM(CONCAT(COALESCE(u.user_first_name, ""), " ", COALESCE(u.user_last_name, ""))) LIKE ?',
                        ['%'.$keyword.'%'],
                    ),
                    self::META_GROUP_GRP_CLASS => $q->orWhere('gcls.grpcls_title', 'like', '%'.$keyword.'%')
                        ->orWhere('gcl.grpcls_title', 'like', '%'.$keyword.'%'),
                    self::META_GROUP_CMS_PAGE => $q->orWhere('cp.cpage_identifier', 'like', '%'.$keyword.'%'),
                    self::META_GROUP_BLOG_CATEGORY => $q->orWhere('bpc.bpcategory_identifier', 'like', '%'.$keyword.'%')
                        ->orWhere('bpcl.bpcategory_name', 'like', '%'.$keyword.'%'),
                    self::META_GROUP_BLOG_POST => $q->orWhere('bp.post_identifier', 'like', '%'.$keyword.'%')
                        ->orWhere('bpl.post_title', 'like', '%'.$keyword.'%'),
                    self::META_GROUP_COURSE => $q->orWhere('crsdetail.course_title', 'like', '%'.$keyword.'%'),
                    self::META_GROUP_TEACH_LANGUAGE => $q->orWhere('tlang.tlang_identifier', 'like', '%'.$keyword.'%')
                        ->orWhere('tlang.tlang_slug', 'like', '%'.$keyword.'%')
                        ->orWhere('tlanglang.tlang_name', 'like', '%'.$keyword.'%'),
                    default => null,
                };
            });
        }

        if ($metaType === self::META_GROUP_DEFAULT || $metaType === self::META_GROUP_OTHER) {
            return;
        }

        $hasTags = $request->query('hasTagsAssociated', $request->query('has_tags_associated', ''));
        if ($hasTags === '' || $hasTags === null) {
            return;
        }

        if ((int) $hasTags === 1) {
            $query->whereNotNull('mt.meta_id');
        } elseif ((int) $hasTags === 0) {
            $query->whereNull('mt.meta_id');
        }
    }

    /** @param  array<string, mixed>  $row */
    private function formatRow(array $row, int $metaType): array
    {
        $metaId = $row['meta_id'] ?? null;

        return [
            'id' => $metaId
                ? (int) $metaId
                : (int) ($row['grpcls_id'] ?? $row['course_id'] ?? 0),
            'meta_id' => $metaId ? (int) $metaId : null,
            'meta_record_id' => (string) ($row['meta_record_id'] ?? ''),
            'meta_identifier' => (string) ($row['meta_identifier'] ?? ''),
            'meta_title' => (string) ($row['meta_title'] ?? ''),
            'url' => $this->slugFromComponents($row),
            'teacher_name' => (string) ($row['teacher_name'] ?? ''),
            'grpcls_title' => (string) ($row['grpcls_title'] ?? ''),
            'cpage_title' => (string) ($row['cpage_title'] ?? ''),
            'bpcategory_identifier' => (string) ($row['bpcategory_identifier'] ?? ''),
            'post_identifier' => (string) ($row['post_identifier'] ?? ''),
            'course_title' => (string) ($row['course_title'] ?? ''),
            'tlang_name' => (string) ($row['tlang_name'] ?? ''),
            'has_tag_associated' => $metaId !== null,
            'meta_type' => $metaType,
        ];
    }

    /** @param  array<string, mixed>  $row */
    private function slugFromComponents(array $row): string
    {
        $parts = [];
        foreach (['meta_controller', 'meta_action', 'meta_record_id'] as $key) {
            $value = $row[$key] ?? '';
            if ($value !== '' && $value !== '0') {
                $parts[] = $value;
            }
        }

        return implode('/', $parts);
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>, meta_type: int} */
    private function emptyResult(Request $request, int $metaType): array
    {
        $result = $this->paginateResult($request, [], 0);
        $result['meta_type'] = $metaType;

        return $result;
    }

    private function coursesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')->where('conf_name', 'CONF_ENABLE_COURSES')->value('conf_val') === 1;
    }

    private function groupClassesEnabled(): bool
    {
        return (int) DB::table('tbl_configurations')->where('conf_name', 'CONF_GROUP_CLASSES_DISABLED')->value('conf_val') === 1;
    }
}
