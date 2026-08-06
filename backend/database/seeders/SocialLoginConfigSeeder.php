<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SocialLoginConfigSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('demo.login_enabled')) {
            return;
        }

        $rows = [
            'CONF_FACEBOOK_APP_ID' => 'demo-facebook-app-id',
            'CONF_FACEBOOK_APP_SECRET' => 'demo-facebook-app-secret',
            'CONF_GOOGLE_CLIENT_JSON' => '{"web":{"client_id":"demo-google-client-id.apps.googleusercontent.com","project_id":"demo"}}',
        ];

        foreach ($rows as $name => $value) {
            $exists = DB::table('tbl_configurations')->where('conf_name', $name)->exists();
            if ($exists) {
                DB::table('tbl_configurations')->where('conf_name', $name)->update(['conf_val' => $value]);
            } else {
                DB::table('tbl_configurations')->insert([
                    'conf_name' => $name,
                    'conf_val' => $value,
                    'conf_common' => 0,
                ]);
            }
            Cache::forget("conf_{$name}");
        }
    }
}
