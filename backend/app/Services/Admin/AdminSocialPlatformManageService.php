<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminSocialPlatformManageService
{
    /** @return array<string, mixed> */
    public function form(int $platformId): array
    {
        if ($platformId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $row = DB::table('tbl_social_platforms')->where('splatform_id', $platformId)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Invalid request');
        }

        return [
            'platform' => [
                'splatform_id' => (int) $row->splatform_id,
                'splatform_identifier' => (string) $row->splatform_identifier,
                'splatform_url' => (string) $row->splatform_url,
                'splatform_active' => (int) $row->splatform_active,
            ],
            'status_options' => [
                ['value' => 1, 'label' => 'Active'],
                ['value' => 0, 'label' => 'Inactive'],
            ],
        ];
    }

    /** @param array<string, mixed> $payload */
    public function setup(array $payload): int
    {
        $platformId = (int) ($payload['splatform_id'] ?? 0);
        if ($platformId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $row = DB::table('tbl_social_platforms')->where('splatform_id', $platformId)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $url = trim((string) ($payload['splatform_url'] ?? ''));
        if ($url === '') {
            throw new \InvalidArgumentException('URL is required');
        }

        $active = (int) ($payload['splatform_active'] ?? 0);

        DB::table('tbl_social_platforms')->where('splatform_id', $platformId)->update([
            'splatform_url' => $url,
            'splatform_active' => $active === 1 ? 1 : 0,
        ]);

        return $platformId;
    }

    public function changeStatus(int $platformId, int $status): void
    {
        if ($platformId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $row = DB::table('tbl_social_platforms')->where('splatform_id', $platformId)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Invalid request');
        }

        if ($status === 1 && trim((string) $row->splatform_url) === '') {
            throw new \InvalidArgumentException('Please add link first');
        }

        DB::table('tbl_social_platforms')->where('splatform_id', $platformId)->update([
            'splatform_active' => $status === 1 ? 1 : 0,
        ]);
    }
}
