<?php

namespace App\Services\Admin;

use App\Services\CoursePublicService;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCoursePreviewService
{
    private const STATUS_PUBLISHED = 3;

    private const RESOURCE_EXTERNAL_URL = 1;

    private const PAGE_SIZE = 12;

    public function __construct(
        private CoursePublicService $coursePublic,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function preview(int $courseId, int $langId = 1): ?array
    {
        $course = $this->courseRow($courseId, $langId);
        if (! $course) {
            return null;
        }

        $sections = $this->sectionsWithLectures($courseId);
        if ($sections === []) {
            return null;
        }

        $firstLectureId = null;
        foreach ($sections as $section) {
            if (! empty($section['lectures'])) {
                $firstLectureId = (int) $section['lectures'][0]['id'];
                break;
            }
        }

        $quiz = null;
        if ((int) ($course['quiz_id'] ?? 0) > 0) {
            $quizTitle = (string) (DB::table('tbl_quiz_linked')
                ->where('quilin_id', (int) $course['quiz_id'])
                ->value('quilin_title') ?? '');
            if ($quizTitle !== '') {
                $quiz = [
                    'id' => (int) $course['quiz_id'],
                    'title' => $quizTitle,
                ];
            }
        }

        $teacher = DB::table('tbl_users as u')
            ->leftJoin('tbl_teacher_stats as ts', 'ts.testat_user_id', '=', 'u.user_id')
            ->leftJoin('tbl_users_lang as ul', function ($join) use ($langId) {
                $join->on('ul.userlang_user_id', '=', 'u.user_id')
                    ->where('ul.userlang_lang_id', '=', $langId);
            })
            ->where('u.user_id', (int) $course['teacher_id'])
            ->select([
                'u.user_id',
                'u.user_first_name',
                'u.user_last_name',
                'u.user_username',
                'u.user_country_id',
                'u.user_active',
                'ts.testat_teachlang',
                'ts.testat_speaklang',
                'ts.testat_preference',
                'ts.testat_availability',
                'ts.testat_qualification',
                DB::raw('IFNULL(ul.user_biography, "") as biography'),
                DB::raw('IFNULL(ts.testat_ratings, 0) as ratings'),
                DB::raw('IFNULL(ts.testat_reviewes, 0) as reviews'),
                DB::raw('IFNULL(ts.testat_courses, 0) as courses'),
            ])
            ->first();

        return [
            'course' => $course,
            'teacher' => $teacher ? [
                'id' => (int) $teacher->user_id,
                'username' => (string) ($teacher->user_username ?? ''),
                'first_name' => (string) ($teacher->user_first_name ?? ''),
                'last_name' => (string) ($teacher->user_last_name ?? ''),
                'biography' => (string) ($teacher->biography ?? ''),
                'ratings' => (float) ($teacher->ratings ?? 0),
                'reviews' => (int) ($teacher->reviews ?? 0),
                'courses' => (int) ($teacher->courses ?? 0),
                'profile_complete' => $this->isTeacherProfileComplete($teacher),
            ] : null,
            'sections' => $sections,
            'quiz' => $quiz,
            'first_lecture_id' => $firstLectureId,
        ];
    }

    /** @return array<string, mixed>|null */
    public function previewLecture(int $courseId, int $lectureId, int $langId = 1): ?array
    {
        if (! $this->courseRow($courseId, $langId)) {
            return null;
        }

        $lecture = DB::table('tbl_lectures as lecture')
            ->join('tbl_sections as section', 'section.section_id', '=', 'lecture.lecture_section_id')
            ->where('lecture.lecture_course_id', $courseId)
            ->where('lecture.lecture_id', $lectureId)
            ->whereNull('lecture.lecture_deleted')
            ->where('lecture.lecture_archived', 0)
            ->whereNull('section.section_deleted')
            ->select([
                'lecture.lecture_id as id',
                'lecture.lecture_title as title',
                'lecture.lecture_details as details',
                'lecture.lecture_duration as duration',
                'lecture.lecture_order as order',
                'lecture.lecture_is_trial as is_trial',
                'lecture.lecture_section_id as section_id',
                'section.section_title as section_title',
                'section.section_order as section_order',
            ])
            ->first();

        if (! $lecture) {
            return null;
        }

        $resources = DB::table('tbl_lectures_resources as lecsrc')
            ->leftJoin('tbl_resources as resrc', function ($join) {
                $join->on('resrc.resrc_id', '=', 'lecsrc.lecsrc_resrc_id')
                    ->whereNull('resrc.resrc_deleted');
            })
            ->where('lecsrc.lecsrc_lecture_id', $lectureId)
            ->whereNull('lecsrc.lecsrc_deleted')
            ->orderBy('lecsrc.lecsrc_id')
            ->get([
                'lecsrc.lecsrc_id as id',
                'lecsrc.lecsrc_type as type',
                'lecsrc.lecsrc_link as link',
                'lecsrc.lecsrc_link_name as link_name',
                'lecsrc.lecsrc_duration as duration',
                'resrc.resrc_name as resrc_name',
            ]);

        $hasVideo = false;
        $videoLink = '';
        $videoName = '';
        $attachments = [];
        foreach ($resources as $row) {
            $type = (int) $row->type;
            if ($type === self::RESOURCE_EXTERNAL_URL) {
                $videoLink = (string) ($row->link ?? '');
                $videoName = (string) ($row->link_name ?? '');
                if ($videoLink !== '') {
                    $hasVideo = true;
                }
                continue;
            }
            $attachments[] = [
                'id' => (int) $row->id,
                'type' => $type,
                'name' => (string) (($row->resrc_name ?? '') !== '' ? $row->resrc_name : ($row->link_name ?? 'Resource')),
            ];
        }

        $orderedIds = $this->orderedLectureIds($courseId);
        $index = array_search($lectureId, $orderedIds, true);
        $prevId = ($index !== false && $index > 0) ? $orderedIds[$index - 1] : null;
        $nextId = ($index !== false && isset($orderedIds[$index + 1])) ? $orderedIds[$index + 1] : null;

        $quizLinkId = 0;
        if ($nextId === null) {
            $quizLinkId = (int) (DB::table('tbl_courses')
                ->where('course_id', $courseId)
                ->value('course_quilin_id') ?? 0);
        }

        $titles = DB::table('tbl_lectures')
            ->whereIn('lecture_id', array_filter([$prevId, $nextId]))
            ->pluck('lecture_title', 'lecture_id');

        return [
            'id' => (int) $lecture->id,
            'title' => (string) ($lecture->title ?? ''),
            'details' => (string) ($lecture->details ?? ''),
            'duration' => (int) ($lecture->duration ?? 0),
            'order' => (int) ($lecture->order ?? 0),
            'is_trial' => (int) ($lecture->is_trial ?? 0) === 1,
            'section' => [
                'id' => (int) $lecture->section_id,
                'title' => (string) ($lecture->section_title ?? ''),
                'order' => (int) ($lecture->section_order ?? 0),
            ],
            'has_video' => $hasVideo,
            'video_link' => $videoLink,
            'video_name' => $videoName,
            'attachments' => $attachments,
            'quiz_link_id' => $quizLinkId > 0 ? $quizLinkId : null,
            'previous_lecture_id' => $prevId,
            'next_lecture_id' => $nextId,
            'previous_lecture_title' => $prevId ? (string) ($titles[$prevId] ?? '') : null,
            'next_lecture_title' => $nextId ? (string) ($titles[$nextId] ?? '') : null,
        ];
    }

    /** @return array<string, mixed> */
    public function previewNotes(
        int $courseId,
        int $teacherUserId,
        string $keyword = '',
        int $page = 1,
    ): array {
        $query = DB::table('tbl_lecture_notes as lecnote')
            ->join('tbl_lectures as lec', 'lecnote.lecnote_lecture_id', '=', 'lec.lecture_id')
            ->where('lecnote.lecnote_course_id', $courseId)
            ->where('lecnote.lecnote_ordcrs_id', 0)
            ->where('lecnote.lecnote_user_id', $teacherUserId)
            ->whereNull('lecnote.lecnote_deleted')
            ->when($keyword !== '', function ($q) use ($keyword) {
                $q->where('lecnote.lecnote_notes', 'LIKE', '%'.$keyword.'%');
            })
            ->orderByDesc('lecnote.lecnote_id')
            ->select([
                'lecnote.lecnote_id as id',
                'lecnote.lecnote_notes as notes',
                'lec.lecture_title as lecture_title',
                'lec.lecture_order as lecture_order',
            ]);

        $paginated = $query->paginate(self::PAGE_SIZE, ['*'], 'page', max(1, $page));

        return [
            'data' => collect($paginated->items())->map(fn ($row) => [
                'id' => (int) $row->id,
                'notes' => (string) ($row->notes ?? ''),
                'lecture_title' => (string) ($row->lecture_title ?? ''),
                'lecture_order' => (int) ($row->lecture_order ?? 0),
            ])->all(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function previewReviews(int $courseId, string $sort = 'DESC', int $page = 1): array
    {
        $course = $this->courseRow($courseId);
        if (! $course) {
            return [];
        }

        $reviews = $this->coursePublic->getReviews($courseId, $sort, $page, self::PAGE_SIZE);

        return [
            'course' => [
                'ratings' => (float) ($course['ratings'] ?? 0),
                'reviews' => (int) ($course['reviews'] ?? 0),
            ],
            'stats' => $this->coursePublic->getReviewStats($courseId),
            'reviews' => collect($reviews['data'] ?? [])->map(fn ($row) => [
                'id' => (int) ($row->id ?? 0),
                'user_id' => (int) ($row->user_id ?? 0),
                'first_name' => (string) ($row->first_name ?? ''),
                'last_name' => (string) ($row->last_name ?? ''),
                'title' => (string) ($row->title ?? ''),
                'detail' => (string) ($row->detail ?? ''),
                'rating' => (float) ($row->rating ?? 0),
                'created_at' => (string) ($row->created_at ?? ''),
            ])->all(),
            'meta' => $reviews['meta'] ?? [],
        ];
    }

    public function downloadResource(int $courseId, int $resourceId): ?StreamedResponse
    {
        $resource = DB::table('tbl_lectures_resources as lecsrc')
            ->join('tbl_resources as resrc', 'resrc.resrc_id', '=', 'lecsrc.lecsrc_resrc_id')
            ->join('tbl_courses as course', 'course.course_id', '=', 'lecsrc.lecsrc_course_id')
            ->where('lecsrc.lecsrc_id', $resourceId)
            ->where('lecsrc.lecsrc_course_id', $courseId)
            ->whereNull('lecsrc.lecsrc_deleted')
            ->whereNull('resrc.resrc_deleted')
            ->whereIn('lecsrc.lecsrc_type', [2, 3])
            ->select([
                'resrc.resrc_path',
                'resrc.resrc_name',
            ])
            ->first();

        if (! $resource) {
            return null;
        }

        $filePath = base_path('../user-uploads/'.(string) $resource->resrc_path);
        if (! is_file($filePath)) {
            return null;
        }

        $mime = mime_content_type($filePath) ?: 'application/octet-stream';

        return response()->streamDownload(function () use ($filePath) {
            readfile($filePath);
        }, (string) $resource->resrc_name, [
            'Content-Type' => $mime,
        ]);
    }

    /** @return array<string, mixed>|null */
    private function courseRow(int $courseId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_courses as course')
            ->join('tbl_course_details as crsdetail', 'crsdetail.course_id', '=', 'course.course_id')
            ->join('tbl_users as teacher', 'teacher.user_id', '=', 'course.course_user_id')
            ->join('tbl_course_approval_requests as coapre', 'coapre.coapre_course_id', '=', 'course.course_id')
            ->whereNull('course.course_deleted')
            ->where('course.course_id', $courseId)
            ->where('course.course_status', self::STATUS_PUBLISHED)
            ->select([
                'course.course_id as id',
                'course.course_slug as slug',
                'course.course_user_id as teacher_id',
                'crsdetail.course_title as title',
                DB::raw('CONCAT(teacher.user_first_name, " ", COALESCE(teacher.user_last_name, "")) as teacher_name'),
                'course.course_quilin_id as quiz_id',
                'course.course_reviews as reviews',
                'course.course_ratings as ratings',
                'course.course_lectures as lectures',
            ])
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'slug' => (string) ($row->slug ?? ''),
            'teacher_id' => (int) $row->teacher_id,
            'title' => (string) ($row->title ?? ''),
            'teacher_name' => trim((string) ($row->teacher_name ?? '')),
            'quiz_id' => (int) ($row->quiz_id ?? 0),
            'reviews' => (int) ($row->reviews ?? 0),
            'ratings' => (float) ($row->ratings ?? 0),
            'lectures' => (int) ($row->lectures ?? 0),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function sectionsWithLectures(int $courseId): array
    {
        $sections = DB::table('tbl_sections')
            ->where('section_course_id', $courseId)
            ->whereNull('section_deleted')
            ->orderBy('section_order')
            ->get([
                'section_id as id',
                'section_title as title',
                'section_order as order',
                'section_lectures as lectures_count',
                'section_duration as duration',
            ]);

        if ($sections->isEmpty()) {
            return [];
        }

        $sectionIds = $sections->pluck('id')->all();
        $lectures = DB::table('tbl_lectures')
            ->whereIn('lecture_section_id', $sectionIds)
            ->whereNull('lecture_deleted')
            ->where('lecture_archived', 0)
            ->orderBy('lecture_order')
            ->get([
                'lecture_id as id',
                'lecture_section_id as section_id',
                'lecture_title as title',
                'lecture_order as order',
                'lecture_duration as duration',
            ]);

        $resourceCounts = DB::table('tbl_lectures_resources')
            ->whereIn('lecsrc_lecture_id', $lectures->pluck('id')->all())
            ->whereNull('lecsrc_deleted')
            ->groupBy('lecsrc_lecture_id')
            ->selectRaw('lecsrc_lecture_id, COUNT(*) as total')
            ->pluck('total', 'lecsrc_lecture_id');

        $lecturesBySection = [];
        foreach ($lectures as $lecture) {
            $lecturesBySection[(int) $lecture->section_id][] = [
                'id' => (int) $lecture->id,
                'title' => (string) ($lecture->title ?? ''),
                'order' => (int) ($lecture->order ?? 0),
                'duration' => (int) ($lecture->duration ?? 0),
                'resources_count' => (int) ($resourceCounts[(int) $lecture->id] ?? 0),
            ];
        }

        return $sections->map(function ($section) use ($lecturesBySection) {
            return [
                'id' => (int) $section->id,
                'title' => (string) ($section->title ?? ''),
                'order' => (int) ($section->order ?? 0),
                'lectures_count' => (int) ($section->lectures_count ?? 0),
                'duration' => (int) ($section->duration ?? 0),
                'lectures' => $lecturesBySection[(int) $section->id] ?? [],
            ];
        })->all();
    }

    private function isTeacherProfileComplete(object $teacher): bool
    {
        if ($teacher->user_username === '' || $teacher->user_username === null) {
            return false;
        }

        if ((int) ($teacher->user_country_id ?? 0) <= 0) {
            return false;
        }

        foreach (
            [
                'user_active',
                'testat_teachlang',
                'testat_speaklang',
                'testat_preference',
                'testat_availability',
                'testat_qualification',
            ] as $field
        ) {
            if ((int) ($teacher->$field ?? 0) === 0) {
                return false;
            }
        }

        return true;
    }

    /** @return array<int, int> */
    private function orderedLectureIds(int $courseId): array
    {
        return DB::table('tbl_lectures as lecture')
            ->join('tbl_sections as section', 'section.section_id', '=', 'lecture.lecture_section_id')
            ->where('lecture.lecture_course_id', $courseId)
            ->whereNull('lecture.lecture_deleted')
            ->where('lecture.lecture_archived', 0)
            ->whereNull('section.section_deleted')
            ->orderBy('section.section_order')
            ->orderBy('lecture.lecture_order')
            ->pluck('lecture.lecture_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
