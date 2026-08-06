<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminQuizManageService
{
    private const TYPE_AUTO_GRADED = 1;

    private const TYPE_NON_GRADED = 2;

    private const STATUS_DRAFTED = 1;

    private const STATUS_PUBLISHED = 2;

    /** @return array<string, mixed> */
    public function show(int $quizId): array
    {
        $row = DB::table('tbl_quizzes as quiz')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'quiz.quiz_user_id')
            ->where('quiz.quiz_id', $quizId)
            ->whereNull('quiz.quiz_deleted')
            ->first([
                'quiz.quiz_id',
                'quiz.quiz_title',
                'quiz.quiz_detail',
                'quiz.quiz_type',
                'quiz.quiz_duration',
                'quiz.quiz_attempts',
                'quiz.quiz_passmark',
                'quiz.quiz_validity',
                'quiz.quiz_certificate',
                'quiz.quiz_questions',
                'quiz.quiz_passmsg',
                'quiz.quiz_failmsg',
                'quiz.quiz_active',
                'quiz.quiz_status',
                'quiz.quiz_created',
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
            ]);

        if (! $row) {
            throw new \RuntimeException('Quiz not found', 404);
        }

        $duration = (int) $row->quiz_duration;
        $passmark = (float) $row->quiz_passmark;

        return [
            'id' => (int) $row->quiz_id,
            'title' => (string) $row->quiz_title,
            'detail' => (string) ($row->quiz_detail ?? ''),
            'type' => (int) $row->quiz_type,
            'type_label' => $this->typeLabel((int) $row->quiz_type),
            'teacher_name' => ucwords(trim((string) $row->teacher_name)),
            'active' => (int) $row->quiz_active,
            'active_label' => (int) $row->quiz_active === 1 ? 'Yes' : 'No',
            'status' => (int) $row->quiz_status,
            'status_label' => $this->statusLabel((int) $row->quiz_status),
            'created_at' => (string) ($row->quiz_created ?? ''),
            'duration' => $duration,
            'duration_label' => $duration > 0 ? $this->formatDuration($duration) : null,
            'attempts' => (int) $row->quiz_attempts,
            'passmark' => $passmark,
            'passmark_label' => $passmark > 0 ? rtrim(rtrim(number_format($passmark, 2, '.', ''), '0'), '.').'%' : null,
            'validity' => (int) ($row->quiz_validity ?? 0),
            'certificate' => (int) $row->quiz_certificate,
            'certificate_label' => (int) $row->quiz_certificate === 1 ? 'Yes' : 'No',
            'questions_count' => (int) $row->quiz_questions,
            'pass_message' => (string) ($row->quiz_passmsg ?? ''),
            'fail_message' => (string) ($row->quiz_failmsg ?? ''),
        ];
    }

    private function typeLabel(int $type): string
    {
        return match ($type) {
            self::TYPE_AUTO_GRADED => 'Auto graded',
            self::TYPE_NON_GRADED => 'Non graded',
            default => 'N/A',
        };
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_DRAFTED => 'Drafted',
            self::STATUS_PUBLISHED => 'Published',
            default => 'N/A',
        };
    }

    private function formatDuration(int $seconds): string
    {
        $parts = [];
        $hrs = intdiv($seconds, 3600);
        if ($hrs > 0) {
            $parts[] = $hrs.'h';
        }
        $min = (int) gmdate('i', $seconds);
        if ($min > 0) {
            $parts[] = $min.'m';
        }

        return count($parts) > 0 ? implode(' ', $parts) : '00m';
    }
}
