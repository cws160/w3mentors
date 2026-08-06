<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminGdprRequestService
{
    public const STATUS_PENDING = 1;

    public const STATUS_DECLINED = 2;

    public const STATUS_APPROVED = 3;

    /** @return array<string, mixed> */
    public function show(int $requestId): array
    {
        $row = DB::table('tbl_gdpr_requests as g')
            ->join('tbl_users as u', 'u.user_id', '=', 'g.gdpreq_user_id')
            ->where('g.gdpreq_id', $requestId)
            ->first([
                'g.gdpreq_id as id',
                'g.gdpreq_user_id as user_id',
                'g.gdpreq_reason as reason',
                'g.gdpreq_status as status',
                'g.gdpreq_comment as comment',
                'g.gdpreq_added_on as created_at',
                'g.gdpreq_updated_on as updated_at',
                'u.user_first_name',
                'u.user_last_name',
                'u.user_deleted',
            ]);

        if (! $row) {
            throw new RuntimeException('Invalid request', 404);
        }

        $data = (array) $row;
        $data['full_name'] = trim($data['user_first_name'].' '.($data['user_last_name'] ?? ''));
        $data['status_label'] = $this->statusLabel((int) $data['status']);

        return $data;
    }

    public function updateStatus(int $requestId, int $status, string $comment = ''): void
    {
        if (! in_array($status, [self::STATUS_DECLINED, self::STATUS_APPROVED], true)) {
            throw new RuntimeException('Invalid request', 422);
        }

        if ($status === self::STATUS_DECLINED && trim($comment) === '') {
            throw new RuntimeException('Comment is required when declining a request', 422);
        }

        $request = DB::table('tbl_gdpr_requests')
            ->where('gdpreq_id', $requestId)
            ->first();

        if (! $request || (int) $request->gdpreq_status !== self::STATUS_PENDING) {
            throw new RuntimeException('Invalid request', 404);
        }

        $userId = (int) $request->gdpreq_user_id;
        $now = now()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($requestId, $status, $comment, $userId, $now) {
            DB::table('tbl_gdpr_requests')
                ->where('gdpreq_id', $requestId)
                ->update([
                    'gdpreq_status' => $status,
                    'gdpreq_comment' => $status === self::STATUS_DECLINED ? $comment : 0,
                    'gdpreq_updated_on' => $now,
                ]);

            if ($status === self::STATUS_APPROVED) {
                $this->anonymizeUser($userId);
            }
        });
    }

    public function statusLabel(int $status): string
    {
        return match ($status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_DECLINED => 'Declined',
            self::STATUS_APPROVED => 'Approved',
            default => (string) $status,
        };
    }

    private function anonymizeUser(int $userId): void
    {
        $now = now()->format('Y-m-d H:i:s');

        DB::table('tbl_users')->where('user_id', $userId)->update([
            'user_first_name' => 'Deleted User',
            'user_last_name' => '',
            'user_email' => null,
            'user_username' => null,
            'user_password' => null,
            'user_gender' => null,
            'user_active' => 0,
            'user_country_id' => 0,
            'user_deleted' => $now,
        ]);

        if (DB::getSchemaBuilder()->hasTable('tbl_user_settings')) {
            DB::table('tbl_user_settings')->where('user_id', $userId)->update([
                'user_trial_enabled' => 0,
                'user_book_before' => 0,
                'user_wallet_balance' => 0,
                'user_video_link' => null,
                'user_apple_id' => null,
                'user_google_id' => null,
                'user_facebook_id' => null,
                'user_apple_token' => null,
                'user_google_token' => null,
                'user_facebook_token' => null,
                'user_referral_code' => null,
            ]);
        }

        DB::table('tbl_users_lang')->where('userlang_user_id', $userId)->delete();
        DB::table('tbl_user_addresses')->where('usradd_user_id', $userId)->delete();

        if (DB::getSchemaBuilder()->hasTable('tbl_user_bank_details')) {
            DB::table('tbl_user_bank_details')->where('ub_user_id', $userId)->delete();
        }

        if (DB::getSchemaBuilder()->hasTable('tbl_user_qualifications')) {
            DB::table('tbl_user_qualifications')->where('uqualification_user_id', $userId)->delete();
        }

        if (DB::getSchemaBuilder()->hasTable('tbl_user_email_change_requests')) {
            DB::table('tbl_user_email_change_requests')->where('uecreq_user_id', $userId)->delete();
        }

        DB::table('tbl_user_withdrawal_requests')
            ->where('withdrawal_user_id', $userId)
            ->update([
                'withdrawal_bank' => '',
                'withdrawal_account_holder_name' => '',
                'withdrawal_account_number' => '',
                'withdrawal_ifc_swift_code' => '',
                'withdrawal_bank_address' => '',
                'withdrawal_comments' => '',
                'withdrawal_paypal_email_id' => '',
            ]);

        if (DB::getSchemaBuilder()->hasTable('tbl_teacher_requests')) {
            DB::table('tbl_teacher_requests')->where('tereq_user_id', $userId)->update([
                'tereq_first_name' => 'Delete',
                'tereq_last_name' => 'User',
                'tereq_phone_code' => null,
                'tereq_phone_number' => null,
            ]);
        }

        if (DB::getSchemaBuilder()->hasTable('tbl_attached_files')) {
            DB::table('tbl_attached_files')
                ->where('file_record_id', $userId)
                ->whereIn('file_type', [1, 2, 3])
                ->delete();
        }
    }

    /** @return array{data: array<int, array<string, mixed>>, meta: array<string, int>} */
    public function exportList(\Illuminate\Http\Request $request): array
    {
        $request->merge(['export' => true, 'per_page' => 5000]);

        return app(AdminModuleRegistry::class)->search('gdpr-requests', $request);
    }
}
