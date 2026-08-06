<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AttachQuizService
{
    public const RECORD_LESSON = 1;

    public const RECORD_GCLASS = 2;

    public const RECORD_COURSE = 3;

    public const QUIZ_TYPE_AUTO = 1;

    public const QUIZ_TYPE_NON_GRADED = 2;

    public const QUIZ_STATUS_PUBLISHED = 2;

    public const QUIZ_ACTIVE = 1;

    /**
     * @return array<int, string>
     */
    public static function quizTypeLabels(): array
    {
        return [
            self::QUIZ_TYPE_AUTO => 'Auto graded',
            self::QUIZ_TYPE_NON_GRADED => 'Non graded',
        ];
    }

    /**
     * @param  array{keyword?: string, quiz_type?: int, page?: int, per_page?: int}  $filters
     * @return array{items: array<int, array<string, mixed>>, meta: array<string, int>}
     */
    public function searchAvailable(
        int $teacherId,
        int $recordId,
        int $recordType,
        array $filters
    ): array {
        $this->assertTeacherOwnsRecord($teacherId, $recordId, $recordType);

        $perPage = max(1, min(50, (int) ($filters['per_page'] ?? 10)));
        $page = max(1, (int) ($filters['page'] ?? 1));

        $attachedIds = DB::table('tbl_quiz_linked')
            ->where('quilin_record_id', $recordId)
            ->where('quilin_record_type', $recordType)
            ->whereNull('quilin_deleted')
            ->pluck('quilin_quiz_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $query = DB::table('tbl_quizzes as quiz')
            ->where('quiz.quiz_user_id', $teacherId)
            ->whereNull('quiz.quiz_deleted')
            ->where('quiz.quiz_active', self::QUIZ_ACTIVE)
            ->where('quiz.quiz_status', self::QUIZ_STATUS_PUBLISHED);

        if ($recordType === self::RECORD_COURSE) {
            $query->where('quiz.quiz_type', self::QUIZ_TYPE_AUTO);
        }

        if ($attachedIds !== []) {
            $query->whereNotIn('quiz.quiz_id', $attachedIds);
        }

        $keyword = trim((string) ($filters['keyword'] ?? ''));
        if ($keyword !== '') {
            $query->where('quiz.quiz_title', 'like', '%'.$keyword.'%');
        }

        if (! empty($filters['quiz_type'])) {
            $query->where('quiz.quiz_type', (int) $filters['quiz_type']);
        }

        $total = (clone $query)->count('quiz.quiz_id');

        $rows = $query
            ->orderByDesc('quiz.quiz_active')
            ->orderByDesc('quiz.quiz_id')
            ->forPage($page, $perPage)
            ->get([
                'quiz.quiz_id',
                'quiz.quiz_title',
                'quiz.quiz_type',
            ]);

        $types = self::quizTypeLabels();
        $items = $rows->map(fn ($row) => [
            'id' => (int) $row->quiz_id,
            'title' => (string) $row->quiz_title,
            'type' => (int) $row->quiz_type,
            'type_label' => $types[(int) $row->quiz_type] ?? '',
        ])->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $page,
                'last_page' => (int) max(1, ceil($total / $perPage)),
                'per_page' => $perPage,
                'total' => $total,
            ],
        ];
    }

    /**
     * @param  array<int>  $quizIds
     */
    public function attach(int $teacherId, int $recordId, int $recordType, array $quizIds): void
    {
        $quizIds = array_values(array_unique(array_map('intval', $quizIds)));
        if ($quizIds === []) {
            throw new RuntimeException('Invalid data sent');
        }

        $this->assertTeacherOwnsRecord($teacherId, $recordId, $recordType);

        $attached = DB::table('tbl_quiz_linked')
            ->where('quilin_record_id', $recordId)
            ->where('quilin_record_type', $recordType)
            ->whereNull('quilin_deleted')
            ->whereIn('quilin_quiz_id', $quizIds)
            ->exists();
        if ($attached) {
            throw new RuntimeException('Quiz already attached');
        }

        $quizzes = DB::table('tbl_quizzes')
            ->where('quiz_user_id', $teacherId)
            ->whereNull('quiz_deleted')
            ->where('quiz_active', self::QUIZ_ACTIVE)
            ->where('quiz_status', self::QUIZ_STATUS_PUBLISHED)
            ->whereIn('quiz_id', $quizIds)
            ->get();

        if ($quizzes->count() !== count($quizIds)) {
            throw new RuntimeException('Some quizzes are not available. Please try again.');
        }

        DB::transaction(function () use ($quizzes, $teacherId, $recordId, $recordType) {
            foreach ($quizzes as $quiz) {
                $validityHours = (int) ($quiz->quiz_validity ?? 0);
                $quilinId = DB::table('tbl_quiz_linked')->insertGetId([
                    'quilin_quiz_id' => (int) $quiz->quiz_id,
                    'quilin_type' => (int) $quiz->quiz_type,
                    'quilin_title' => (string) $quiz->quiz_title,
                    'quilin_detail' => (string) ($quiz->quiz_detail ?? ''),
                    'quilin_user_id' => $teacherId,
                    'quilin_record_id' => $recordId,
                    'quilin_record_type' => $recordType,
                    'quilin_duration' => (int) $quiz->quiz_duration,
                    'quilin_attempts' => (int) $quiz->quiz_attempts,
                    'quilin_marks' => $quiz->quiz_marks,
                    'quilin_passmark' => $quiz->quiz_passmark,
                    'quilin_failmsg' => (string) ($quiz->quiz_failmsg ?? ''),
                    'quilin_passmsg' => (string) ($quiz->quiz_passmsg ?? ''),
                    'quilin_validity' => date('Y-m-d H:i:s', strtotime('+'.$validityHours.' hours')),
                    'quilin_certificate' => (int) ($quiz->quiz_certificate ?? 0),
                    'quilin_questions' => (int) ($quiz->quiz_questions ?? 0),
                    'quilin_created' => now(),
                ]);

                $this->copyQuizQuestions((int) $quiz->quiz_id, $quilinId);
            }
        });
    }

    private function copyQuizQuestions(int $quizId, int $quilinId): void
    {
        $questions = DB::table('tbl_quizzes_questions as quique')
            ->join('tbl_questions as ques', 'ques.ques_id', '=', 'quique.quique_ques_id')
            ->where('quique.quique_quiz_id', $quizId)
            ->whereNull('ques.ques_deleted')
            ->orderBy('quique.quique_order')
            ->get([
                'ques.ques_id',
                'ques.ques_type',
                'ques.ques_title',
                'ques.ques_detail',
                'ques.ques_hint',
                'ques.ques_marks',
                'ques.ques_answer',
                'quique.quique_order',
            ]);

        if ($questions->isEmpty()) {
            throw new RuntimeException('Questions not found');
        }

        $questionIds = $questions->pluck('ques_id')->map(fn ($id) => (int) $id)->all();
        $options = DB::table('tbl_question_options')
            ->whereIn('queopt_ques_id', $questionIds)
            ->orderBy('queopt_order')
            ->get(['queopt_id', 'queopt_title', 'queopt_ques_id']);

        $optionsByQuestion = [];
        foreach ($options as $opt) {
            $optionsByQuestion[(int) $opt->queopt_ques_id][] = [
                'queopt_id' => (int) $opt->queopt_id,
                'queopt_title' => (string) $opt->queopt_title,
                'queopt_ques_id' => (int) $opt->queopt_ques_id,
            ];
        }

        $order = 1;
        foreach ($questions as $question) {
            $quesId = (int) $question->ques_id;
            $opts = $optionsByQuestion[$quesId] ?? [];
            if ((int) $question->ques_type !== 3 && $opts === []) {
                throw new RuntimeException('Question options are not available');
            }

            DB::table('tbl_quiz_linked_questions')->insert([
                'qulinqu_type' => (int) $question->ques_type,
                'qulinqu_quilin_id' => $quilinId,
                'qulinqu_ques_id' => $quesId,
                'qulinqu_title' => (string) $question->ques_title,
                'qulinqu_detail' => (string) ($question->ques_detail ?? ''),
                'qulinqu_hint' => $question->ques_hint,
                'qulinqu_marks' => (int) $question->ques_marks,
                'qulinqu_answer' => (string) ($question->ques_answer ?? ''),
                'qulinqu_options' => json_encode($opts),
                'qulinqu_order' => $order++,
            ]);
        }
    }

    private function assertTeacherOwnsRecord(int $teacherId, int $recordId, int $recordType): void
    {
        if ($recordId < 1 || ! in_array($recordType, [self::RECORD_LESSON, self::RECORD_GCLASS, self::RECORD_COURSE], true)) {
            throw new RuntimeException('Invalid data sent');
        }

        if ($recordType === self::RECORD_GCLASS) {
            $ok = DB::table('tbl_group_classes')
                ->where('grpcls_id', $recordId)
                ->where('grpcls_teacher_id', $teacherId)
                ->exists();
            if (! $ok) {
                throw new RuntimeException('Invalid class');
            }

            return;
        }

        if ($recordType === self::RECORD_LESSON) {
            $ok = DB::table('tbl_order_lessons as ordles')
                ->where('ordles.ordles_id', $recordId)
                ->where('ordles.ordles_teacher_id', $teacherId)
                ->exists();
            if (! $ok) {
                throw new RuntimeException('Invalid lesson');
            }

            return;
        }

        $ok = DB::table('tbl_courses')
            ->where('course_id', $recordId)
            ->where('course_user_id', $teacherId)
            ->exists();
        if (! $ok) {
            throw new RuntimeException('Invalid course');
        }
    }
}
