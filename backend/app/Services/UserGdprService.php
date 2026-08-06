<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class UserGdprService
{
    public const TYPE_TRUNCATE = 1;

    public const STATUS_PENDING = 1;

    public function hasPendingRequest(int $userId): bool
    {
        return DB::table('tbl_gdpr_requests')
            ->where('gdpreq_user_id', $userId)
            ->where('gdpreq_status', self::STATUS_PENDING)
            ->exists();
    }

    public function submitDeleteRequest(int $userId, string $reason): void
    {
        if ($this->hasPendingRequest($userId)) {
            throw new \InvalidArgumentException('A request to delete this account is already pending.');
        }

        $now = now()->format('Y-m-d H:i:s');
        DB::table('tbl_gdpr_requests')->insert([
            'gdpreq_user_id' => $userId,
            'gdpreq_reason' => $reason,
            'gdpreq_added_on' => $now,
            'gdpreq_updated_on' => $now,
            'gdpreq_type' => self::TYPE_TRUNCATE,
            'gdpreq_status' => self::STATUS_PENDING,
            'gdpreq_comment' => 0,
        ]);
    }
}
