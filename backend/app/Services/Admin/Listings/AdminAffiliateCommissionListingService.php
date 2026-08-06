<?php

namespace App\Services\Admin\Listings;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAffiliateCommissionListingService
{
    use AdminListingSupport;

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function search(Request $request): array
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = $this->adminPageSize($request);

        $query = DB::table('tbl_affiliate_commissions as afcomm')
            ->leftJoin('tbl_users as user', 'afcomm.afcomm_user_id', '=', 'user.user_id')
            ->orderByRaw('afcomm.afcomm_user_id IS NULL DESC')
            ->orderByDesc('afcomm.afcomm_id')
            ->select([
                'afcomm.afcomm_id as id',
                'afcomm.afcomm_commission as commission',
                'afcomm.afcomm_user_id as affiliate_user_id',
                'user.user_id as user_id',
                'user.user_first_name',
                'user.user_last_name',
            ]);

        $keyword = trim((string) $request->query('keyword', ''));
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('user.user_first_name', 'like', "%{$keyword}%")
                    ->orWhere('user.user_last_name', 'like', "%{$keyword}%")
                    ->orWhereRaw('CONCAT(user.user_first_name, " ", user.user_last_name) like ?', ["%{$keyword}%"]);
            });
        }

        $total = (clone $query)->count('afcomm.afcomm_id');
        $rows = $query->forPage($page, $perPage)->get()->map(function ($row) {
            $isGlobal = empty($row->affiliate_user_id);
            $name = $isGlobal
                ? ''
                : trim((string) $row->user_first_name.' '.(string) ($row->user_last_name ?? ''));

            return [
                'id' => (int) $row->id,
                'user_id' => $isGlobal ? 0 : (int) ($row->user_id ?? 0),
                'is_global' => $isGlobal,
                'affiliate_name' => $name,
                'commission' => number_format((float) $row->commission, 2, '.', ''),
            ];
        })->all();

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
