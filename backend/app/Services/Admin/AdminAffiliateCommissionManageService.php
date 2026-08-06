<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;

class AdminAffiliateCommissionManageService
{
    /** @return array<string, mixed>|null */
    public function show(int $commissionId): ?array
    {
        if ($commissionId < 1) {
            return [
                'afcomm_id' => 0,
                'afcomm_user_id' => 0,
                'afcomm_commission' => '',
                'user_name' => '',
                'is_global' => false,
            ];
        }

        $row = DB::table('tbl_affiliate_commissions as afcomm')
            ->leftJoin('tbl_users as user', 'afcomm.afcomm_user_id', '=', 'user.user_id')
            ->where('afcomm.afcomm_id', $commissionId)
            ->first([
                'afcomm.afcomm_id',
                'afcomm.afcomm_user_id',
                'afcomm.afcomm_commission',
                'user.user_first_name',
                'user.user_last_name',
            ]);

        if (! $row) {
            return null;
        }

        $isGlobal = empty($row->afcomm_user_id);
        $userName = $isGlobal
            ? ''
            : trim((string) $row->user_first_name.' '.(string) ($row->user_last_name ?? ''));

        return [
            'afcomm_id' => (int) $row->afcomm_id,
            'afcomm_user_id' => $isGlobal ? 0 : (int) $row->afcomm_user_id,
            'afcomm_commission' => (float) $row->afcomm_commission,
            'user_name' => $userName,
            'is_global' => $isGlobal,
        ];
    }

    /** @param array<string, mixed> $payload @return array{ok: bool, message?: string, afcomm_id?: int} */
    public function setup(array $payload): array
    {
        $commissionId = (int) ($payload['afcomm_id'] ?? 0);
        $userId = (int) ($payload['afcomm_user_id'] ?? 0);
        $commission = (float) ($payload['afcomm_commission'] ?? 0);

        if ($commission < 1 || $commission > 100) {
            return ['ok' => false, 'message' => 'Commission must be between 1 and 100'];
        }

        if ($commissionId < 1 && $userId < 1) {
            return ['ok' => false, 'message' => 'Please select an affiliate'];
        }

        $existing = $this->findCommissionForUser($userId);
        if ($existing) {
            $commissionId = (int) $existing->afcomm_id;
        }

        $data = [
            'afcomm_user_id' => $userId > 0 ? $userId : null,
            'afcomm_commission' => $commission,
            'afcomm_created' => now()->format('Y-m-d H:i:s'),
        ];

        if ($commissionId > 0) {
            $exists = DB::table('tbl_affiliate_commissions')->where('afcomm_id', $commissionId)->exists();
            if (! $exists) {
                return ['ok' => false, 'message' => 'Invalid request'];
            }
            DB::table('tbl_affiliate_commissions')->where('afcomm_id', $commissionId)->update($data);
        } else {
            $commissionId = (int) DB::table('tbl_affiliate_commissions')->insertGetId($data);
        }

        DB::table('tbl_affiliate_commission_history')->insert([
            'afcomhis_user_id' => $userId > 0 ? $userId : null,
            'afcomhis_commission' => $commission,
            'afcomhis_created' => now()->format('Y-m-d H:i:s'),
        ]);

        return ['ok' => true, 'afcomm_id' => $commissionId];
    }

    /** @return array<int, array<string, mixed>> */
    public function history(int $userId): array
    {
        $query = DB::table('tbl_affiliate_commission_history as h')
            ->leftJoin('tbl_users as user', 'h.afcomhis_user_id', '=', 'user.user_id')
            ->orderByDesc('h.afcomhis_id')
            ->select([
                'h.afcomhis_commission as commission',
                'h.afcomhis_created as created_at',
                'h.afcomhis_user_id as affiliate_user_id',
                'user.user_id',
                'user.user_first_name',
                'user.user_last_name',
            ]);

        if ($userId < 1) {
            $query->whereNull('h.afcomhis_user_id');
        } else {
            $query->where('h.afcomhis_user_id', $userId);
        }

        return $query->get()->map(function ($row) {
            $isGlobal = empty($row->affiliate_user_id);

            return [
                'user_id' => $isGlobal ? 0 : (int) ($row->user_id ?? 0),
                'is_global' => $isGlobal,
                'affiliate_name' => $isGlobal
                    ? ''
                    : trim((string) $row->user_first_name.' '.(string) ($row->user_last_name ?? '')),
                'commission' => number_format((float) $row->commission, 2, '.', ''),
                'created_at' => (string) ($row->created_at ?? ''),
            ];
        })->all();
    }

    /** @return array{ok: bool, message?: string} */
    public function delete(int $commissionId): array
    {
        if ($commissionId < 1) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        $row = DB::table('tbl_affiliate_commissions')
            ->where('afcomm_id', $commissionId)
            ->first(['afcomm_id', 'afcomm_user_id']);

        if (! $row) {
            return ['ok' => false, 'message' => 'Invalid request'];
        }

        if (empty($row->afcomm_user_id)) {
            return ['ok' => false, 'message' => 'Cannot delete global commission'];
        }

        $userId = (int) $row->afcomm_user_id;

        DB::table('tbl_affiliate_commissions')->where('afcomm_id', $commissionId)->delete();
        DB::table('tbl_affiliate_commission_history')->where('afcomhis_user_id', $userId)->delete();

        return ['ok' => true];
    }

    /** @return array<int, array<string, mixed>> */
    public function autocomplete(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        return DB::table('tbl_users as user')
            ->leftJoin('tbl_affiliate_commissions as afcomm', 'user.user_id', '=', 'afcomm.afcomm_user_id')
            ->where('user.user_is_affiliate', 1)
            ->whereNull('user.user_deleted')
            ->whereNull('afcomm.afcomm_user_id')
            ->where(function ($q) use ($keyword) {
                $q->where('user.user_username', 'like', "%{$keyword}%")
                    ->orWhere('user.user_email', 'like', "%{$keyword}%")
                    ->orWhereRaw('CONCAT(user.user_first_name, " ", user.user_last_name) like ?', ["%{$keyword}%"]);
            })
            ->orderByRaw('CONCAT(user.user_first_name, " ", user.user_last_name) ASC')
            ->limit(30)
            ->get([
                'user.user_id as id',
                'user.user_email as email',
                'user.user_username as username',
                DB::raw('TRIM(CONCAT(user.user_first_name, " ", COALESCE(user.user_last_name, ""))) as full_name'),
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'email' => (string) ($row->email ?? ''),
                'username' => (string) ($row->username ?? ''),
                'full_name' => (string) ($row->full_name ?? ''),
            ])
            ->all();
    }

    private function findCommissionForUser(int $userId): ?object
    {
        $query = DB::table('tbl_affiliate_commissions');
        if ($userId > 0) {
            $query->where('afcomm_user_id', $userId);
        } else {
            $query->whereNull('afcomm_user_id');
        }

        return $query->first(['afcomm_id', 'afcomm_user_id']);
    }
}
