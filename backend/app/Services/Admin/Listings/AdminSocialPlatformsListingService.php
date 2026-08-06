<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminSocialPlatformsListingService
{
    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $keyword = trim((string) $request->query('keyword', ''));

        $query = DB::table('tbl_social_platforms')
            ->orderByDesc('splatform_active')
            ->orderBy('splatform_order')
            ->orderBy('splatform_id')
            ->select([
                'splatform_id as id',
                'splatform_identifier as identifier',
                'splatform_url as url',
                'splatform_active as active',
            ]);

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('splatform_identifier', 'like', "%{$keyword}%")
                    ->orWhere('splatform_url', 'like', "%{$keyword}%");
            });
        }

        $rows = $query->get()->map(function ($row) {
            return [
                'id' => (int) $row->id,
                'identifier' => (string) ($row->identifier ?? ''),
                'url' => (string) ($row->url ?? ''),
                'active' => (int) ($row->active ?? 0),
            ];
        })->all();

        $total = count($rows);

        return [
            'data' => $rows,
            'meta' => [
                'current_page' => 1,
                'per_page' => $total > 0 ? $total : 1,
                'total' => $total,
                'last_page' => 1,
            ],
        ];
    }
}
