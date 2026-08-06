<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminCourseEditRequestService
{
    private const STATUS_PENDING = 0;

    private const STATUS_APPROVED = 1;

    private const STATUS_DECLINED = 2;

    /** @return array<string, mixed>|null */
    public function show(int $requestId): ?array
    {
        $row = DB::table('tbl_course_edit_requests as coedre')
            ->where('coedre.coedre_id', $requestId)
            ->select([
                'coedre.coedre_id as id',
                'coedre.coedre_status as status',
                'coedre.coedre_reason as reason',
                'coedre.coedre_created as created_at',
            ])
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id' => (int) $row->id,
            'status' => (int) $row->status,
            'status_label' => $this->statusLabel((int) $row->status),
            'reason' => (string) ($row->reason ?? ''),
            'created_at' => (string) ($row->created_at ?? ''),
        ];
    }

    public function updateStatus(int $requestId, int $status, string $comment = ''): void
    {
        if (! in_array($status, [self::STATUS_APPROVED, self::STATUS_DECLINED], true)) {
            throw new RuntimeException('Invalid status', 422);
        }

        if ($status === self::STATUS_DECLINED && trim($comment) === '') {
            throw new RuntimeException('Comment is required when declining a request', 422);
        }

        $request = DB::table('tbl_course_edit_requests')
            ->where('coedre_id', $requestId)
            ->where('coedre_status', self::STATUS_PENDING)
            ->first(['coedre_id']);

        if (! $request) {
            throw new RuntimeException('Invalid request', 404);
        }

        $duration = (int) (DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_COURSE_EDIT_DURATION')
            ->value('conf_val') ?? 0);

        DB::table('tbl_course_edit_requests')
            ->where('coedre_id', $requestId)
            ->update([
                'coedre_status' => $status,
                'coedre_comment' => $comment,
                'coedre_updated' => now(),
                'coedre_duration' => $duration > 0 ? $duration : 2,
            ]);
    }

    public function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Edit request pending',
            self::STATUS_APPROVED => 'Edit request approved',
            self::STATUS_DECLINED => 'Edit request declined',
            default => 'Unknown',
        };
    }
}
