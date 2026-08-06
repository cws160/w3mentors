<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherDemoForumTagRequestsSeeder extends Seeder
{
    /**
     * Demo forum tag requests for the reference teacher account.
     */
    public function run(): void
    {
        $email = config('demo.teacher.email');
        $teacher = DB::table('tbl_users')->where('user_email', $email)->first();
        if (! $teacher) {
            return;
        }

        $this->seedForUser((int) $teacher->user_id);
    }

    public static function seedForUser(int $userId): void
    {
        $now = now()->format('Y-m-d H:i:s');
        $langId = (int) (DB::table('tbl_users')->where('user_id', $userId)->value('user_lang_id') ?: 1);

        $requests = [
            ['name' => 'chemistry', 'status' => 0],
            ['name' => 'physics', 'status' => 1],
            ['name' => 'biology', 'status' => 2],
        ];

        foreach ($requests as $request) {
            $exists = DB::table('tbl_forum_tag_requests')
                ->where('ftagreq_user_id', $userId)
                ->where('ftagreq_name', $request['name'])
                ->where('ftagreq_language_id', $langId)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('tbl_forum_tag_requests')->insert([
                'ftagreq_user_id' => $userId,
                'ftagreq_language_id' => $langId,
                'ftagreq_name' => $request['name'],
                'ftagreq_status' => $request['status'],
                'ftagreq_added_on' => $now,
            ]);
        }
    }
}
