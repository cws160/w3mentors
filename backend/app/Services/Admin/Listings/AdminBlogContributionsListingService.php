<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminBlogContributionsListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $query = DB::table('tbl_blog_contributions as c')
            ->orderByDesc('c.bcontributions_id')
            ->select([
                'c.bcontributions_id as id',
                DB::raw('TRIM(CONCAT(c.bcontributions_author_first_name, " ", c.bcontributions_author_last_name)) as author_name'),
                'c.bcontributions_author_email as author_email',
                'c.bcontributions_author_phone as author_phone',
                'c.bcontributions_status as status',
                'c.bcontributions_added_on as posted_on',
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('c.bcontributions_author_first_name', 'like', "%{$keyword}%")
                    ->orWhere('c.bcontributions_author_last_name', 'like', "%{$keyword}%")
                    ->orWhereRaw('CONCAT(c.bcontributions_author_first_name, " ", c.bcontributions_author_last_name) like ?', ["%{$keyword}%"])
                    ->orWhere('c.bcontributions_author_email', 'like', "%{$keyword}%")
                    ->orWhere('c.bcontributions_author_phone', 'like', "%{$keyword}%");
            });
        }

        $status = $request->query('bcontributions_status', $request->query('status'));
        if ($status !== null && $status !== '') {
            $query->where('c.bcontributions_status', '=', (int) $status);
        }

        $contributionId = $request->integer('bcontributions_id', 0);
        if ($contributionId > 0) {
            $query->where('c.bcontributions_id', '=', $contributionId);
        }

        $total = (clone $query)->count('c.bcontributions_id');
        $rows = $query->forPage($page, $perPage)->get()->map(fn ($row) => [
            'id' => (int) $row->id,
            'author_name' => ucfirst(trim((string) $row->author_name)),
            'author_email' => (string) ($row->author_email ?? ''),
            'author_phone' => (string) ($row->author_phone ?? ''),
            'status' => (int) $row->status,
            'posted_on' => (string) ($row->posted_on ?? ''),
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
