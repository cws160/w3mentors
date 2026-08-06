<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{
    public const FILTER_ALL = 0;
    public const FILTER_GCLASS = 1;
    public const FILTER_COURSE = 2;
    public const FILTER_TEACHER = 3;
    public const FILTER_LANGUAGE = 4;

    public function autocomplete(Request $request): JsonResponse
    {
        $keyword = trim($request->string('keyword')->toString());
        $type = $request->integer('type', self::FILTER_ALL);
        $langId = $request->integer('lang_id', 1);

        if (strlen($keyword) < 3) {
            return response()->json([
                'keyword' => $keyword,
                'courses' => [],
                'teachers' => [],
                'classes' => [],
                'languages' => [],
            ]);
        }

        $coursesEnabled = Configuration::getValue('CONF_ENABLE_COURSES', '1') === '1';
        $groupClassesEnabled = Configuration::getValue('CONF_GROUP_CLASSES_DISABLED', '0') === '1';

        $courses = [];
        $teachers = [];
        $classes = [];
        $languages = [];

        if ($coursesEnabled && ($type === self::FILTER_ALL || $type === self::FILTER_COURSE)) {
            $courses = $this->courses($keyword, $langId);
        }
        if ($type === self::FILTER_ALL || $type === self::FILTER_TEACHER) {
            $teachers = $this->teachers($keyword);
        }
        if ($groupClassesEnabled && ($type === self::FILTER_ALL || $type === self::FILTER_GCLASS)) {
            $classes = $this->groupClasses($keyword, $langId);
        }
        if ($type === self::FILTER_ALL || $type === self::FILTER_LANGUAGE) {
            $languages = $this->languages($keyword, $langId);
        }

        return response()->json(compact('keyword', 'courses', 'teachers', 'classes', 'languages'));
    }

    private function courses(string $keyword, int $langId): array
    {
        return DB::table('tbl_courses as course')
            ->join('tbl_course_details as crsdetail', function ($join) use ($langId) {
                $join->on('course.course_id', '=', 'crsdetail.course_id')
                    ->where('crsdetail.course_lang_id', '=', $langId);
            })
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'course.course_user_id')
            ->where('course.course_status', 3)
            ->where('course.course_active', 1)
            ->where('teacher.user_username', '!=', '')
            ->where('crsdetail.course_title', 'like', "%{$keyword}%")
            ->orderByDesc('course.course_clicks')
            ->limit(5)
            ->get([
                'course.course_id as id',
                'crsdetail.course_title as name',
                'course.course_slug as slug',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'url' => '/courses/'.$row->slug,
            ])
            ->all();
    }

    private function teachers(string $keyword): array
    {
        return DB::table('tbl_users as teacher')
            ->where('teacher.user_active', 1)
            ->whereNull('teacher.user_deleted')
            ->where('teacher.user_is_teacher', 1)
            ->whereNotNull('teacher.user_verified')
            ->where(function ($q) use ($keyword) {
                $q->where('teacher.user_first_name', 'like', "%{$keyword}%")
                    ->orWhere('teacher.user_last_name', 'like', "%{$keyword}%")
                    ->orWhereRaw(
                        'CONCAT(teacher.user_first_name, " ", teacher.user_last_name) LIKE ?',
                        ["%{$keyword}%"]
                    );
            })
            ->orderBy('teacher.user_first_name')
            ->limit(5)
            ->get([
                'teacher.user_id as id',
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as name'),
                'teacher.user_username as slug',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => trim($row->name),
                'slug' => $row->slug,
                'url' => '/teachers/'.$row->slug,
            ])
            ->all();
    }

    private function groupClasses(string $keyword, int $langId): array
    {
        return DB::table('tbl_group_classes as grpcls')
            ->leftJoin('tbl_group_classes_lang as gclang', function ($join) use ($langId) {
                $join->on('grpcls.grpcls_id', '=', 'gclang.grpcls_id')
                    ->where('gclang.grpcls_lang_id', '=', $langId);
            })
            ->where('grpcls.grpcls_status', 1)
            ->where('grpcls.grpcls_start_datetime', '>', now())
            ->where(function ($q) use ($keyword) {
                $q->where('gclang.grpcls_title', 'like', "%{$keyword}%")
                    ->orWhere('grpcls.grpcls_title', 'like', "%{$keyword}%");
            })
            ->orderBy('grpcls.grpcls_start_datetime')
            ->limit(5)
            ->get([
                'grpcls.grpcls_id as id',
                DB::raw('IFNULL(gclang.grpcls_title, grpcls.grpcls_title) as name'),
                'grpcls.grpcls_slug as slug',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'url' => '/group-classes/'.$row->slug,
            ])
            ->all();
    }

    private function languages(string $keyword, int $langId): array
    {
        return DB::table('tbl_teach_languages as tlang')
            ->leftJoin('tbl_teach_languages_lang as tlanglang', function ($join) use ($langId) {
                $join->on('tlang.tlang_id', '=', 'tlanglang.tlanglang_tlang_id')
                    ->where('tlanglang.tlanglang_lang_id', '=', $langId);
            })
            ->where('tlang.tlang_parent', 0)
            ->where('tlang.tlang_available', 1)
            ->where(function ($q) use ($keyword) {
                $q->where('tlang.tlang_identifier', 'like', "%{$keyword}%")
                    ->orWhere('tlanglang.tlang_name', 'like', "%{$keyword}%");
            })
            ->limit(5)
            ->get([
                'tlang.tlang_id as id',
                DB::raw('IFNULL(tlanglang.tlang_name, tlang.tlang_identifier) as name'),
                'tlang.tlang_slug as slug',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'slug' => $row->slug,
                'url' => '/teachers?language='.$row->slug,
            ])
            ->all();
    }
}
