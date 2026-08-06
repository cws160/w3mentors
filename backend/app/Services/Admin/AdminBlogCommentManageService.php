<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminBlogCommentManageService
{
    /** @return array<string, mixed>|null */
    public function show(int $commentId, int $langId = 1): ?array
    {
        $row = DB::table('tbl_blog_post_comments as c')
            ->leftJoin('tbl_blog_post as post', 'post.post_id', '=', 'c.bpcomment_post_id')
            ->leftJoin('tbl_blog_post_lang as plang', function ($join) use ($langId) {
                $join->on('plang.postlang_post_id', '=', 'post.post_id')
                    ->where('plang.postlang_lang_id', '=', $langId);
            })
            ->where('c.bpcomment_id', $commentId)
            ->where('c.bpcomment_deleted', 0)
            ->first([
                'c.bpcomment_id',
                'c.bpcomment_author_name',
                'c.bpcomment_author_email',
                'c.bpcomment_content',
                'c.bpcomment_approved',
                'c.bpcomment_added_on',
                'c.bpcomment_user_ip',
                'c.bpcomment_user_agent',
                DB::raw('IFNULL(plang.post_title, post.post_identifier) as post_title'),
            ]);

        if (! $row) {
            return null;
        }

        return [
            'bpcomment_id' => (int) $row->bpcomment_id,
            'bpcomment_author_name' => (string) $row->bpcomment_author_name,
            'bpcomment_author_email' => (string) $row->bpcomment_author_email,
            'bpcomment_content' => (string) $row->bpcomment_content,
            'bpcomment_approved' => (int) $row->bpcomment_approved,
            'bpcomment_added_on' => (string) $row->bpcomment_added_on,
            'bpcomment_user_ip' => (string) $row->bpcomment_user_ip,
            'bpcomment_user_agent' => (string) $row->bpcomment_user_agent,
            'post_title' => (string) ($row->post_title ?: ''),
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string} */
    public function updateStatus(int $commentId, array $payload): array
    {
        if ($commentId < 1) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $exists = DB::table('tbl_blog_post_comments')
            ->where('bpcomment_id', $commentId)
            ->where('bpcomment_deleted', 0)
            ->exists();

        if (! $exists) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $approved = (int) ($payload['bpcomment_approved'] ?? -1);
        if (! in_array($approved, [0, 1], true)) {
            return ['ok' => false, 'message' => 'Invalid status'];
        }

        DB::table('tbl_blog_post_comments')
            ->where('bpcomment_id', $commentId)
            ->update(['bpcomment_approved' => $approved]);

        return ['ok' => true];
    }

    /** @return array{ok: bool, message?: string} */
    public function delete(int $commentId): array
    {
        $exists = DB::table('tbl_blog_post_comments')
            ->where('bpcomment_id', $commentId)
            ->where('bpcomment_deleted', 0)
            ->exists();

        if (! $exists) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        DB::table('tbl_blog_post_comments')
            ->where('bpcomment_id', $commentId)
            ->update(['bpcomment_deleted' => 1]);

        return ['ok' => true];
    }
}
