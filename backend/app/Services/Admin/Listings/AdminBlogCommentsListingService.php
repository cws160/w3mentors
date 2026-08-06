<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBlogCommentsListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $langId = $this->langId($request);
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $query = DB::table('tbl_blog_post_comments as c')
            ->leftJoin('tbl_blog_post as post', 'post.post_id', '=', 'c.bpcomment_post_id')
            ->leftJoin('tbl_blog_post_lang as plang', function ($join) use ($langId) {
                $join->on('plang.postlang_post_id', '=', 'post.post_id')
                    ->where('plang.postlang_lang_id', '=', $langId);
            })
            ->where('c.bpcomment_deleted', '=', 0)
            ->orderByDesc('c.bpcomment_added_on')
            ->select([
                'c.bpcomment_id as id',
                'c.bpcomment_author_name as author_name',
                'c.bpcomment_author_email as author_email',
                'c.bpcomment_content as comment',
                'c.bpcomment_approved as approved',
                'c.bpcomment_added_on as posted_on',
                DB::raw('IFNULL(plang.post_title, post.post_identifier) as post_title'),
            ]);

        $this->applyKeyword($request, $query, ['c.bpcomment_author_name', 'c.bpcomment_author_email']);

        $approved = $request->query('bpcomment_approved', $request->query('approved'));
        if ($approved !== null && $approved !== '') {
            $query->where('c.bpcomment_approved', '=', (int) $approved);
        }

        $commentId = $request->integer('bpcomment_id', 0);
        if ($commentId > 0) {
            $query->where('c.bpcomment_id', '=', $commentId);
        }

        $total = (clone $query)->count('c.bpcomment_id');
        $rows = $query->forPage($page, $perPage)->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'author_name' => ucfirst((string) $row->author_name),
            'author_email' => (string) $row->author_email,
            'comment' => (string) $row->comment,
            'approved' => (int) $row->approved,
            'posted_on' => (string) ($row->posted_on ?? ''),
            'post_title' => ucfirst((string) $row->post_title),
        ])->all();

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => (int) ceil($total / $perPage) ?: 1,
            ],
        ];
    }
}
