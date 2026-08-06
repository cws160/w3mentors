<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_ENABLE_LIVECHAT')
            ->update(['conf_val' => '0']);

        DB::table('tbl_configurations')
            ->where('conf_name', 'CONF_LIVE_CHAT_CODE')
            ->update(['conf_val' => '']);

        Cache::forget('conf_CONF_ENABLE_LIVECHAT');
        Cache::forget('conf_CONF_LIVE_CHAT_CODE');
    }

    public function down(): void
    {
        // Live chat (e.g. Tawk.to) was removed intentionally; no restore.
    }
};
