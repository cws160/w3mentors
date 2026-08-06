<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminQuizzesListingService
{
    use AdminListingSupport;

    private const TYPE_AUTO_GRADED = 1;

    private const TYPE_NON_GRADED = 2;

    private const STATUS_DRAFTED = 1;

    private const STATUS_PUBLISHED = 2;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $query = DB::table('tbl_quizzes as quiz')
            ->leftJoin('tbl_users as teacher', 'teacher.user_id', '=', 'quiz.quiz_user_id')
            ->whereNull('quiz.quiz_deleted')
            ->select([
                'quiz.quiz_id as id',
                'quiz.quiz_title as title',
                'quiz.quiz_type as type',
                'quiz.quiz_duration as duration',
                'quiz.quiz_attempts as attempts',
                'quiz.quiz_passmark as passmark',
                'quiz.quiz_active as active',
                'quiz.quiz_status as status',
                'quiz.quiz_questions as questions_count',
                'quiz.quiz_created as created_at',
                DB::raw('TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) as teacher_name'),
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where('quiz.quiz_title', 'like', '%'.$keyword.'%');
        }

        $quizType = $request->integer('quiz_type', 0);
        if ($quizType > 0) {
            $query->where('quiz.quiz_type', '=', $quizType);
        }

        $quizStatus = $request->query('quiz_status');
        if ($quizStatus !== null && $quizStatus !== '') {
            $query->where('quiz.quiz_status', '=', (int) $quizStatus);
        }

        $quizActive = $request->query('quiz_active');
        if ($quizActive !== null && $quizActive !== '') {
            $query->where('quiz.quiz_active', '=', (int) $quizActive);
        }

        $teacher = trim((string) $request->query('teacher', ''));
        if ($teacher !== '') {
            $query->whereRaw(
                'TRIM(CONCAT(COALESCE(teacher.user_first_name, ""), " ", COALESCE(teacher.user_last_name, ""))) LIKE ?',
                ['%'.$teacher.'%'],
            );
        }

        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);
        $total = (clone $query)->distinct('quiz.quiz_id')->count('quiz.quiz_id');

        $rows = $query
            ->orderByDesc('quiz.quiz_active')
            ->orderByDesc('quiz.quiz_id')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'title' => (string) $row->title,
                'type' => (int) $row->type,
                'type_label' => $this->typeLabel((int) $row->type),
                'teacher_name' => ucwords(trim((string) $row->teacher_name)),
                'questions_count' => (int) $row->questions_count,
                'duration' => (int) $row->duration,
                'duration_label' => $this->formatDuration((int) $row->duration),
                'attempts' => (int) $row->attempts,
                'passmark' => (float) $row->passmark,
                'passmark_label' => $this->formatPassmark((float) $row->passmark),
                'active' => (int) $row->active,
                'active_label' => (int) $row->active === 1 ? 'Yes' : 'No',
                'status' => (int) $row->status,
                'status_label' => $this->statusLabel((int) $row->status),
                'created_at' => (string) ($row->created_at ?? ''),
            ])
            ->all();

        return $this->paginateResult($request, $rows, $total);
    }

    /** @return array<int, array<string, mixed>> */
    public function exportRows(Request $request): array
    {
        $request->merge(['export' => true, 'page' => 1]);

        return $this->search($request)['data'];
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
        if ($seconds <= 0) {
            return '00m';
        }

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

    private function formatPassmark(float $value): string
    {
        return $value > 0 ? rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.').'%' : '-';
    }
}
