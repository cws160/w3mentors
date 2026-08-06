<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseProgress;
use App\Models\IntendedLearner;
use App\Models\Lecture;
use App\Models\OrderCourse;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Collection;

class CourseService
{
    public function findEnrollment(?User $user, int $courseId): ?OrderCourse
    {
        if (!$user) {
            return null;
        }

        return OrderCourse::query()
            ->with(['progress', 'order'])
            ->active()
            ->where('ordcrs_course_id', $courseId)
            ->whereHas('order', fn ($q) => $q->where('order_user_id', $user->user_id))
            ->first();
    }

    public function isEnrolled(?User $user, int $courseId): bool
    {
        return $this->findEnrollment($user, $courseId) !== null;
    }

    public function canAccessLecture(?User $user, Lecture $lecture): bool
    {
        if ($lecture->lecture_is_trial) {
            return true;
        }

        return $this->isEnrolled($user, (int) $lecture->lecture_course_id);
    }

    public function getCurriculum(Course $course, ?User $user = null): Collection
    {
        $enrolled = $this->isEnrolled($user, $course->course_id);
        $covered = $this->findEnrollment($user, $course->course_id)?->progress?->coveredLectureIds() ?? [];

        return Section::query()
            ->active()
            ->where('section_course_id', $course->course_id)
            ->orderBy('section_order')
            ->with(['lectures' => function ($q) {
                $q->active()->orderBy('lecture_order');
            }])
            ->get()
            ->map(function ($section) use ($enrolled, $covered) {
                return [
                    'id' => $section->section_id,
                    'title' => $section->section_title,
                    'details' => $section->section_details,
                    'order' => $section->section_order,
                    'lectures_count' => $section->section_lectures,
                    'duration' => $section->section_duration,
                    'lectures' => $section->lectures->map(function ($lecture) use ($enrolled, $covered) {
                        $accessible = $enrolled || $lecture->lecture_is_trial;

                        return [
                            'id' => $lecture->lecture_id,
                            'title' => $lecture->lecture_title,
                            'details' => $lecture->lecture_details,
                            'duration' => $lecture->lecture_duration,
                            'order' => $lecture->lecture_order,
                            'is_trial' => (bool) $lecture->lecture_is_trial,
                            'is_accessible' => $accessible,
                            'is_completed' => in_array($lecture->lecture_id, $covered, true),
                        ];
                    })->values(),
                ];
            });
    }

    public function getIntendedLearners(Course $course): array
    {
        $items = IntendedLearner::query()
            ->active()
            ->where('coinle_course_id', $course->course_id)
            ->orderBy('coinle_type')
            ->orderBy('coinle_order')
            ->get()
            ->groupBy('coinle_type');

        return [
            'learning_outcomes' => $this->mapIntendedGroup($items->get(IntendedLearner::TYPE_LEARNING)),
            'requirements' => $this->mapIntendedGroup($items->get(IntendedLearner::TYPE_REQUIREMENTS)),
            'target_audience' => $this->mapIntendedGroup($items->get(IntendedLearner::TYPE_LEARNERS)),
        ];
    }

    public function ensureProgress(OrderCourse $orderCourse): CourseProgress
    {
        $progress = $orderCourse->progress;

        if ($progress) {
            return $progress;
        }

        $progress = CourseProgress::create([
            'crspro_ordcrs_id' => $orderCourse->ordcrs_id,
            'crspro_lecture_id' => 0,
            'crspro_progress' => 0,
            'crspro_status' => CourseProgress::STATUS_PENDING,
        ]);

        if ((int) $orderCourse->ordcrs_status === OrderCourse::STATUS_PENDING) {
            $orderCourse->ordcrs_status = OrderCourse::STATUS_IN_PROGRESS;
            $orderCourse->save();
        }

        return $progress;
    }

    public function markLectureComplete(CourseProgress $progress, Course $course, int $lectureId): CourseProgress
    {
        $lectureExists = Lecture::query()
            ->active()
            ->where('lecture_course_id', $course->course_id)
            ->where('lecture_id', $lectureId)
            ->exists();

        if (!$lectureExists) {
            abort(422, 'Lecture does not exist in this course.');
        }

        $covered = $progress->coveredLectureIds();
        if (!in_array($lectureId, $covered, true)) {
            $covered[] = $lectureId;
        }

        $progress->setCoveredLectureIds($covered);
        $progress->crspro_lecture_id = $lectureId;
        $progress->crspro_started = $progress->crspro_started ?? now();

        $totalLectures = max(1, (int) $course->course_lectures);
        $percent = round((count($covered) * 100) / $totalLectures, 2);
        $progress->crspro_progress = $percent;
        $progress->crspro_status = $percent >= 100
            ? CourseProgress::STATUS_COMPLETED
            : CourseProgress::STATUS_IN_PROGRESS;

        if ($percent >= 100 && !$progress->crspro_completed) {
            $progress->crspro_completed = now();
            $orderCourse = $progress->orderCourse;
            if ($orderCourse) {
                $orderCourse->ordcrs_status = OrderCourse::STATUS_COMPLETED;
                $orderCourse->save();
            }
        }

        $progress->save();

        return $progress->fresh();
    }

    private function mapIntendedGroup(?Collection $group): array
    {
        if (!$group) {
            return [];
        }

        return $group->map(fn ($item) => [
            'id' => $item->coinle_id,
            'text' => $item->coinle_response,
            'order' => $item->coinle_order,
        ])->values()->all();
    }
}
