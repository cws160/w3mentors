<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AdminMeetingToolManageService
{
    private const ZOOM_CODE = 'ZoomMeeting';

    private const ZOOM_ACC_NOT_SYNCED = 1;

    /** @return array<string, mixed> */
    public function form(int $toolId): array
    {
        $row = $this->findOrFail($toolId);
        $settings = $this->decodeSettings((string) ($row->metool_settings ?? ''));

        return [
            'metool_id' => (int) $row->metool_id,
            'metool_code' => (string) $row->metool_code,
            'metool_info' => (string) ($row->metool_info ?? ''),
            'fields' => $settings,
        ];
    }

    /** @param array<string, mixed> $payload */
    public function setup(array $payload): int
    {
        $toolId = (int) ($payload['metool_id'] ?? 0);
        $row = $this->findOrFail($toolId);

        $incoming = $payload['metool_settings'] ?? [];
        if (! is_array($incoming) || $incoming === []) {
            throw new \InvalidArgumentException('Nothing to save');
        }

        $settings = json_decode((string) ($row->metool_settings ?? ''), true);
        if (! is_array($settings)) {
            throw new \InvalidArgumentException('Invalid request');
        }

        foreach ($settings as &$group) {
            if (! is_array($group)) {
                continue;
            }
            foreach ($group as $name => &$field) {
                if (! is_array($field)) {
                    continue;
                }
                if (array_key_exists($name, $incoming)) {
                    $value = $incoming[$name];
                    if (($field['type'] ?? '') === 'checkbox') {
                        $value = (int) (bool) $value;
                    } else {
                        $value = trim((string) $value);
                    }
                    $field['value'] = $value;
                }
            }
        }
        unset($group, $field);

        DB::table('tbl_meeting_tools')->where('metool_id', $toolId)->update([
            'metool_settings' => json_encode($settings),
        ]);

        return $toolId;
    }

    public function changeStatus(int $toolId, int $status): void
    {
        if ($status !== 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $row = $this->findOrFail($toolId);
        if ((int) $row->metool_status === 1) {
            return;
        }

        DB::table('tbl_meeting_tools')->where('metool_id', $toolId)->update([
            'metool_status' => 1,
        ]);

        DB::table('tbl_meeting_tools')
            ->where('metool_id', '!=', $toolId)
            ->update(['metool_status' => 0]);

        if ((string) $row->metool_code === self::ZOOM_CODE) {
            $this->resetZoomUserState();
        }
    }

    private function resetZoomUserState(): void
    {
        if (Schema::hasTable('tbl_zoom_users') && Schema::hasColumn('tbl_zoom_users', 'zmusr_verified')) {
            DB::table('tbl_zoom_users')
                ->where('zmusr_verified', '!=', 0)
                ->update(['zmusr_verified' => 0]);
        }

        if (Schema::hasTable('tbl_user_settings') && Schema::hasColumn('tbl_user_settings', 'user_zoom_status')) {
            DB::table('tbl_user_settings')
                ->where('user_zoom_status', '!=', self::ZOOM_ACC_NOT_SYNCED)
                ->update(['user_zoom_status' => self::ZOOM_ACC_NOT_SYNCED]);
        } elseif (Schema::hasTable('tbl_users') && Schema::hasColumn('tbl_users', 'user_zoom_status')) {
            DB::table('tbl_users')
                ->where('user_zoom_status', '!=', self::ZOOM_ACC_NOT_SYNCED)
                ->update(['user_zoom_status' => self::ZOOM_ACC_NOT_SYNCED]);
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function decodeSettings(string $json): array
    {
        if ($json === '') {
            return [];
        }

        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $fields = [];
        foreach ($decoded as $group) {
            if (! is_array($group)) {
                continue;
            }
            foreach ($group as $name => $field) {
                if (! is_array($field)) {
                    continue;
                }
                $fields[] = [
                    'name' => (string) $name,
                    'type' => (string) ($field['type'] ?? 'text'),
                    'label_key' => (string) ($field['label'] ?? ''),
                    'value' => $field['value'] ?? '',
                    'placeholder' => (string) ($field['placeholder'] ?? ''),
                    'helptext' => (string) ($field['helptext'] ?? ''),
                    'options' => is_array($field['options'] ?? null) ? $field['options'] : [],
                ];
            }
        }

        return $fields;
    }

    private function findOrFail(int $toolId): object
    {
        if ($toolId < 1) {
            throw new \InvalidArgumentException('Invalid request');
        }

        $row = DB::table('tbl_meeting_tools')->where('metool_id', $toolId)->first();
        if (! $row) {
            throw new \InvalidArgumentException('Invalid request');
        }

        return $row;
    }
}
