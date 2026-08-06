<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminBlogContributionManageService
{
    private const FILE_TYPE_BLOG_CONTRIBUTION = 36;

    /** @return array<string, mixed>|null */
    public function show(int $contributionId): ?array
    {
        $row = DB::table('tbl_blog_contributions')
            ->where('bcontributions_id', $contributionId)
            ->first();

        if (! $row) {
            return null;
        }

        $file = DB::table('tbl_attached_files')
            ->where('file_type', self::FILE_TYPE_BLOG_CONTRIBUTION)
            ->where('file_record_id', $contributionId)
            ->orderBy('file_id')
            ->first(['file_id', 'file_name', 'file_record_id']);

        return [
            'bcontributions_id' => (int) $row->bcontributions_id,
            'bcontributions_author_first_name' => (string) $row->bcontributions_author_first_name,
            'bcontributions_author_last_name' => (string) $row->bcontributions_author_last_name,
            'bcontributions_author_email' => (string) ($row->bcontributions_author_email ?? ''),
            'bcontributions_author_phone' => (string) ($row->bcontributions_author_phone ?? ''),
            'bcontributions_status' => (int) $row->bcontributions_status,
            'bcontributions_added_on' => (string) $row->bcontributions_added_on,
            'bcontributions_user_id' => (int) $row->bcontributions_user_id,
            'attached_file' => $file ? [
                'file_id' => (int) $file->file_id,
                'file_name' => (string) $file->file_name,
                'file_record_id' => (int) $file->file_record_id,
                'download_url' => '/api/v1/image/show-by-id/'.$file->file_id,
            ] : null,
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string} */
    public function updateStatus(int $contributionId, array $payload): array
    {
        if ($contributionId < 1) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $exists = DB::table('tbl_blog_contributions')->where('bcontributions_id', $contributionId)->exists();
        if (! $exists) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $status = (int) ($payload['bcontributions_status'] ?? -1);
        if (! in_array($status, [0, 1, 2, 3], true)) {
            return ['ok' => false, 'message' => 'Invalid status'];
        }

        DB::table('tbl_blog_contributions')
            ->where('bcontributions_id', $contributionId)
            ->update(['bcontributions_status' => $status]);

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function delete(int $contributionId): array
    {
        $exists = DB::table('tbl_blog_contributions')->where('bcontributions_id', $contributionId)->exists();
        if (! $exists) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $files = DB::table('tbl_attached_files')
            ->where('file_type', self::FILE_TYPE_BLOG_CONTRIBUTION)
            ->where('file_record_id', $contributionId)
            ->get(['file_path']);

        DB::table('tbl_attached_files')
            ->where('file_type', self::FILE_TYPE_BLOG_CONTRIBUTION)
            ->where('file_record_id', $contributionId)
            ->delete();

        DB::table('tbl_blog_contributions')->where('bcontributions_id', $contributionId)->delete();

        foreach ($files as $file) {
            if (! empty($file->file_path)) {
                $path = base_path('../user-uploads/'.$file->file_path);
                if (is_file($path)) {
                    @unlink($path);
                }
            }
        }

        return ['ok' => true];
    }
}
