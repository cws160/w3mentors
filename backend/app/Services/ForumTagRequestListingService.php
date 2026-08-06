<?php

namespace App\Services;

use Database\Seeders\TeacherDemoForumTagRequestsSeeder;
use Illuminate\Support\Facades\DB;

class ForumTagRequestListingService
{
    public const STATUS_PENDING = 0;

    public const STATUS_APPROVED = 1;

    public const STATUS_REJECTED = 2;

    /**
     * @return array{items: array<int, array<string, mixed>>}
     */
    public function list(int $userId): array
    {
        $rows = DB::table('tbl_forum_tag_requests as ftagreq')
            ->where('ftagreq.ftagreq_user_id', $userId)
            ->orderBy('ftagreq.ftagreq_status')
            ->orderByDesc('ftagreq.ftagreq_id')
            ->get([
                'ftagreq.ftagreq_id',
                'ftagreq.ftagreq_name',
                'ftagreq.ftagreq_language_id',
                'ftagreq.ftagreq_status',
            ]);

        $languages = DB::table('tbl_languages')
            ->pluck('language_name', 'language_id')
            ->all();

        $items = [];
        $serial = 1;
        foreach ($rows as $row) {
            $status = (int) $row->ftagreq_status;
            $langId = (int) ($row->ftagreq_language_id ?? 0);
            $items[] = [
                'id' => (int) $row->ftagreq_id,
                'serial' => $serial++,
                'name' => (string) $row->ftagreq_name,
                'language_id' => $langId,
                'language_label' => (string) ($languages[$langId] ?? 'N/A'),
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'status_class' => $this->statusClass($status),
                'can_edit' => ! in_array($status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true),
            ];
        }

        if ($items === []) {
            $this->ensureDemoRecords($userId);

            $rows = DB::table('tbl_forum_tag_requests as ftagreq')
                ->where('ftagreq.ftagreq_user_id', $userId)
                ->orderBy('ftagreq.ftagreq_status')
                ->orderByDesc('ftagreq.ftagreq_id')
                ->get([
                    'ftagreq.ftagreq_id',
                    'ftagreq.ftagreq_name',
                    'ftagreq.ftagreq_language_id',
                    'ftagreq.ftagreq_status',
                ]);

            $serial = 1;
            foreach ($rows as $row) {
                $status = (int) $row->ftagreq_status;
                $langId = (int) ($row->ftagreq_language_id ?? 0);
                $items[] = [
                    'id' => (int) $row->ftagreq_id,
                    'serial' => $serial++,
                    'name' => (string) $row->ftagreq_name,
                    'language_id' => $langId,
                    'language_label' => (string) ($languages[$langId] ?? 'N/A'),
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'status_class' => $this->statusClass($status),
                    'can_edit' => ! in_array($status, [self::STATUS_APPROVED, self::STATUS_REJECTED], true),
                ];
            }
        }

        return ['items' => $items];
    }

    private function ensureDemoRecords(int $userId): void
    {
        $demoTeacherId = DB::table('tbl_users')
            ->where('user_email', config('demo.teacher.email'))
            ->value('user_id');

        if (! $demoTeacherId || (int) $demoTeacherId !== $userId) {
            return;
        }

        TeacherDemoForumTagRequestsSeeder::seedForUser($userId);
    }

    private function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'N/A',
        };
    }

    private function statusClass(int $status): string
    {
        return match ($status) {
            self::STATUS_APPROVED => 'color-success',
            self::STATUS_REJECTED => 'color-danger',
            default => '',
        };
    }
}
