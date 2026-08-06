<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class QuizFormService
{
    public const TYPE_AUTO_GRADED = 1;

    public const TYPE_NON_GRADED = 2;

    public const STATUS_DRAFTED = 1;

    public const ACTIVE = 1;

    /**
     * @return array<string, mixed>|null
     */
    public function getBasic(int $teacherId, int $quizId): ?array
    {
        if ($quizId < 1) {
            return [
                'id' => 0,
                'title' => '',
                'type' => self::TYPE_AUTO_GRADED,
                'detail' => '',
            ];
        }

        $row = DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->where('quiz_user_id', $teacherId)
            ->whereNull('quiz_deleted')
            ->first([
                'quiz_id',
                'quiz_title',
                'quiz_type',
                'quiz_detail',
            ]);

        if (! $row) {
            return null;
        }

        return [
            'id' => (int) $row->quiz_id,
            'title' => (string) $row->quiz_title,
            'type' => (int) $row->quiz_type,
            'detail' => (string) ($row->quiz_detail ?? ''),
        ];
    }

    /**
     * @param  array{quiz_id?: int, title: string, type: int, detail: string}  $input
     * @return array{id: int}|null Returns null on validation failure; error message in $error
     */
    public function saveBasic(int $teacherId, array $input, ?string &$error = null): ?array
    {
        $quizId = (int) ($input['quiz_id'] ?? 0);
        $title = trim((string) ($input['title'] ?? ''));
        $type = (int) ($input['type'] ?? 0);
        $detail = trim((string) ($input['detail'] ?? ''));

        if (strlen($title) < 10 || strlen($title) > 120) {
            $error = 'Title must be between 10 and 120 characters';

            return null;
        }
        if (! in_array($type, [self::TYPE_AUTO_GRADED, self::TYPE_NON_GRADED], true)) {
            $error = 'Invalid quiz type';

            return null;
        }
        if ($detail === '') {
            $error = 'Instructions are required';

            return null;
        }

        $now = now()->format('Y-m-d H:i:s');

        if ($quizId > 0) {
            $existing = DB::table('tbl_quizzes')
                ->where('quiz_id', $quizId)
                ->where('quiz_user_id', $teacherId)
                ->whereNull('quiz_deleted')
                ->first(['quiz_id', 'quiz_type']);

            if (! $existing) {
                $error = 'Quiz not found';

                return null;
            }
            if ((int) $existing->quiz_type !== $type) {
                $error = 'Quiz type cannot be modified';

                return null;
            }

            DB::table('tbl_quizzes')
                ->where('quiz_id', $quizId)
                ->update([
                    'quiz_title' => $title,
                    'quiz_detail' => $detail,
                    'quiz_updated' => $now,
                ]);

            return ['id' => $quizId];
        }

        $quizId = (int) DB::table('tbl_quizzes')->insertGetId([
            'quiz_type' => $type,
            'quiz_title' => $title,
            'quiz_detail' => $detail,
            'quiz_user_id' => $teacherId,
            'quiz_duration' => 0,
            'quiz_attempts' => 0,
            'quiz_marks' => 0,
            'quiz_passmark' => 0,
            'quiz_validity' => 0,
            'quiz_certificate' => 0,
            'quiz_questions' => 0,
            'quiz_failmsg' => '',
            'quiz_passmsg' => '',
            'quiz_active' => self::ACTIVE,
            'quiz_status' => self::STATUS_DRAFTED,
            'quiz_created' => $now,
            'quiz_updated' => $now,
            'quiz_deleted' => null,
        ]);

        return ['id' => $quizId];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getSettings(int $teacherId, int $quizId, int $langId): ?array
    {
        $row = DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->where('quiz_user_id', $teacherId)
            ->whereNull('quiz_deleted')
            ->first([
                'quiz_id',
                'quiz_duration',
                'quiz_attempts',
                'quiz_passmark',
                'quiz_validity',
                'quiz_certificate',
                'quiz_failmsg',
                'quiz_passmsg',
            ]);

        if (! $row) {
            return null;
        }

        $offerCertificate = $this->offerCertificateAvailable($langId);

        return [
            'id' => (int) $row->quiz_id,
            'duration' => (int) $row->quiz_duration > 0 ? (int) $row->quiz_duration / 60 : 0,
            'attempts' => (int) ($row->quiz_attempts ?? 0),
            'pass_percent' => (float) ($row->quiz_passmark ?? 0),
            'validity' => (int) ($row->quiz_validity ?? 0),
            'certificate' => $offerCertificate ? (int) ($row->quiz_certificate ?? 0) : 0,
            'fail_message' => (string) ($row->quiz_failmsg ?? ''),
            'pass_message' => (string) ($row->quiz_passmsg ?? ''),
            'offer_certificate' => $offerCertificate,
        ];
    }

    /**
     * @param  array{quiz_id: int, duration?: int, attempts: int, pass_percent: float, validity: int, certificate?: int, fail_message: string, pass_message: string}  $input
     */
    public function saveSettings(int $teacherId, int $langId, array $input, ?string &$error = null): bool
    {
        $quizId = (int) ($input['quiz_id'] ?? 0);
        if ($quizId < 1 || ! DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->where('quiz_user_id', $teacherId)
            ->whereNull('quiz_deleted')
            ->exists()) {
            $error = 'Quiz not found';

            return false;
        }

        $duration = max(0, (int) ($input['duration'] ?? 0));
        $attempts = (int) ($input['attempts'] ?? 0);
        $passPercent = (float) ($input['pass_percent'] ?? 0);
        $validity = (int) ($input['validity'] ?? 0);
        $failMessage = trim((string) ($input['fail_message'] ?? ''));
        $passMessage = trim((string) ($input['pass_message'] ?? ''));
        $offerCertificate = $this->offerCertificateAvailable($langId);
        $certificate = $offerCertificate ? (int) ($input['certificate'] ?? 0) : 0;

        if ($duration < 0 || $duration > 9999) {
            $error = 'Invalid duration';

            return false;
        }
        if ($attempts < 1 || $attempts > 10) {
            $error = 'Attempts must be between 1 and 10';

            return false;
        }
        if ($passPercent < 1 || $passPercent > 100) {
            $error = 'Pass percentage must be between 1 and 100';

            return false;
        }
        if ($validity < 1 || $validity > 9999) {
            $error = 'Validity must be between 1 and 9999 hours';

            return false;
        }
        if (strlen($failMessage) < 10 || strlen($failMessage) > 255) {
            $error = 'Fail message must be between 10 and 255 characters';

            return false;
        }
        if (strlen($passMessage) < 10 || strlen($passMessage) > 255) {
            $error = 'Pass message must be between 10 and 255 characters';

            return false;
        }
        if ($offerCertificate && $certificate === 1 && ! $this->offerCertificateAvailable($langId)) {
            $error = 'Offer certificate option has been disabled by admin';

            return false;
        }

        $status = $this->getCompletedStatus($teacherId, $quizId);
        $quizStatus = ($status['is_complete'] ?? false) ? self::STATUS_PUBLISHED : self::STATUS_DRAFTED;

        DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->update([
                'quiz_duration' => $duration * 60,
                'quiz_attempts' => $attempts,
                'quiz_passmark' => $passPercent,
                'quiz_validity' => $validity,
                'quiz_certificate' => $certificate,
                'quiz_failmsg' => $failMessage,
                'quiz_passmsg' => $passMessage,
                'quiz_status' => $quizStatus,
                'quiz_updated' => now()->format('Y-m-d H:i:s'),
            ]);

        return true;
    }

    /**
     * @return array{general: bool, questions: bool, settings: bool, is_complete: bool}
     */
    public function getCompletedStatus(int $teacherId, int $quizId): array
    {
        $row = DB::table('tbl_quizzes')
            ->where('quiz_id', $quizId)
            ->where('quiz_user_id', $teacherId)
            ->whereNull('quiz_deleted')
            ->first([
                'quiz_type',
                'quiz_attempts',
                'quiz_passmark',
                'quiz_validity',
                'quiz_failmsg',
                'quiz_passmsg',
            ]);

        if (! $row) {
            return ['general' => false, 'questions' => false, 'settings' => false, 'is_complete' => false];
        }

        $general = (int) ($row->quiz_type ?? 0) > 0;
        $questions = DB::table('tbl_quizzes_questions as quique')
            ->join('tbl_questions as ques', 'ques.ques_id', '=', 'quique.quique_ques_id')
            ->join('tbl_categories as cate', 'cate.cate_id', '=', 'ques.ques_cate_id')
            ->where('quique.quique_quiz_id', $quizId)
            ->where('ques.ques_status', 1)
            ->whereNull('ques.ques_deleted')
            ->where('cate.cate_status', 1)
            ->whereNull('cate.cate_deleted')
            ->exists();

        $settings = ! empty($row->quiz_attempts)
            && ! empty($row->quiz_passmark)
            && ! empty($row->quiz_validity)
            && ! empty($row->quiz_failmsg)
            && ! empty($row->quiz_passmsg);

        return [
            'general' => $general,
            'questions' => $questions,
            'settings' => $settings,
            'is_complete' => $general && $questions && $settings,
        ];
    }

    public function offerCertificateAvailable(int $langId): bool
    {
        return DB::table('tbl_certificate_templates')
            ->where('certpl_code', 'evaluation_certificate')
            ->where('certpl_status', 1)
            ->where(function ($q) use ($langId) {
                $q->where('certpl_lang_id', $langId)->orWhere('certpl_lang_id', 0);
            })
            ->exists();
    }
}
